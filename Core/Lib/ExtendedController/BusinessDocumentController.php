<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Lib\ExtendedController;

use FacturaScripts\Core\Model\Base\BusinessDocumentLine;
use FacturaScripts\Dinamic\Lib\BusinessDocumentFormTools;

/**
 * Description of BusinessDocumentController
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class BusinessDocumentController extends PanelController
{

    use DocFilesTrait;

    /**
     *
     * @var BusinessDocumentFormTools
     */
    protected $documentTools;

    /**
     * Shows the document opertation type selector.
     *
     * @var bool
     */
    public $showDocOperation = false;

    /**
     * Shows the document sub-type selector.
     *
     * @var bool
     */
    public $showDocSubType = false;

    /**
     * Returns an array of custom fields to add on the header.
     */
    abstract public function getCustomFields();

    /**
     * Returns the document class name.
     */
    abstract public function getModelClassName();

    /**
     * Retuns an url to create a new subject.
     */
    abstract public function getNewSubjectUrl();

    /**
     * Returns the name of the XMLView file for lines.
     */
    abstract protected function getLineXMLView();

    /**
     * Sets subject for this document.
     *
     * @param mixed $view
     * @param mixed $formData
     */
    abstract protected function setSubject(&$view, $formData);

    /**
     * Starts all the objects and properties.
     *
     * @param string $className
     * @param string $uri
     */
    public function __construct(string $className, string $uri = '')
    {
        parent::__construct($className, $uri);
        $this->documentTools = new BusinessDocumentFormTools();
    }

    /**
     * Load views and document.
     */
    protected function createViews()
    {
        /// tabs on top
        $this->setTabsPosition('top');

        /// document tab
        $fullModelName = self::MODEL_NAMESPACE . $this->getModelClassName();
        $view = new BusinessDocumentView($this->getLineXMLView(), 'new', $fullModelName);
        $this->addCustomView($view->getViewName(), $view);
        $this->setSettings($view->getViewName(), 'btnPrint', true);

        /// edit tab
        $viewName = 'Edit' . $this->getModelClassName();
        $this->addEditView($viewName, $this->getModelClassName(), 'detail', 'fas fa-edit');

        /// disable delete button
        $this->setSettings($viewName, 'btnDelete', false);

        /// files tab
        $this->createViewDocFiles();
    }

    /**
     * Run the actions that alter data before reading it.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'add-file':
                return $this->addFileAction();

            case 'delete-file':
                return $this->deleteFileAction();

            case 'edit-file':
                return $this->editFileAction();

            case 'recalculate-document':
                return $this->recalculateDocumentAction();

            case 'save-document':
                return $this->saveDocumentAction();

            case 'subject-changed':
                return $this->subjectChangedAction();

            case 'unlink-file':
                return $this->unlinkFileAction();
        }

        return parent::execPreviousAction($action);
    }

    /**
     * 
     * @return array
     */
    protected function getBusinessFormData()
    {
        $data = ['custom' => [], 'final' => [], 'form' => [], 'lines' => [], 'subject' => []];
        foreach ($this->request->request->all() as $field => $value) {
            switch ($field) {
                case 'codpago':
                case 'codserie':
                    $data['custom'][$field] = $value;
                    break;

                case 'dtopor1':
                case 'dtopor2':
                case 'idestado':
                    $data['final'][$field] = $value;
                    break;

                case 'lines':
                    $data['lines'] = $this->views[$this->active]->processFormLines($value);
                    break;

                case $this->views[$this->active]->model->subjectColumn():
                    $data['subject'][$field] = $value;
                    break;

                default:
                    $data['form'][$field] = $value;
            }
        }

        return $data;
    }

    /**
     * Load view data procedure
     *
     * @param string               $viewName
     * @param BusinessDocumentView $view
     */
    protected function loadData($viewName, $view)
    {
        $primaryKey = $this->request->request->get($view->model->primaryColumn());
        $code = $this->request->query->get('code', $primaryKey);

        switch ($viewName) {
            case 'docfiles':
                $this->loadDataDocFiles($view, $this->getModelClassName(), $code);
                break;

            case 'Edit' . $this->getModelClassName():
                $view->loadData($code);
                break;

            case $this->getLineXMLView():
                if (empty($code)) {
                    $view->model->setAuthor($this->user);
                    break;
                }

                /// data not found?
                $view->loadData($code);
                $action = $this->request->request->get('action', '');
                if ('' === $action && false === $view->model->exists()) {
                    $this->toolBox()->i18nLog()->warning('record-not-found');
                    break;
                }

                $this->title .= ' ' . $view->model->primaryDescription();
                break;
        }
    }

    /**
     * Recalculate the document total based on lines.
     *
     * @return bool
     */
    protected function recalculateDocumentAction()
    {
        $this->setTemplate(false);

        /// loads model
        $data = $this->getBusinessFormData();
        $merged = \array_merge($data['custom'], $data['final'], $data['form'], $data['subject']);
        $this->views[$this->active]->loadFromData($merged);

        /// update subject data?
        if (false === $this->views[$this->active]->model->exists()) {
            $this->views[$this->active]->model->updateSubject();
        }

        /// recalculate
        $result = $this->documentTools->recalculateForm($this->views[$this->active]->model, $data['lines']);
        $this->response->setContent($result);
        return false;
    }

    /**
     * Saves the document.
     *
     * @return bool
     */
    protected function saveDocumentAction()
    {
        $this->setTemplate(false);
        if (false === $this->permissions->allowUpdate) {
            $this->response->setContent($this->toolBox()->i18n()->trans('not-allowed-modify'));
            return false;
        }

        /// duplicated request?
        if ($this->multiRequestProtection->tokenExist($this->request->request->get('multireqtoken', ''))) {
            $this->response->setContent($this->toolBox()->i18n()->trans('duplicated-request'));
            return false;
        }

        /// loads model
        $data = $this->getBusinessFormData();
        $this->views[$this->active]->model->setAuthor($this->user);
        $this->views[$this->active]->loadFromData($data['form']);
        $this->views[$this->active]->lines = $this->views[$this->active]->model->getLines();

        /// save
        $result = $this->saveDocumentResult($this->views[$this->active], $data);
        $this->response->setContent($result);

        /// event finish
        $this->views[$this->active]->model->pipe('finish');
        return false;
    }

    /**
     * 
     * @param string $message
     *
     * @return string
     */
    protected function saveDocumentError($message)
    {
        foreach ($this->toolBox()->log()->readAll() as $msg) {
            $message .= "\n" . $msg['message'];
        }

        /// undo transaction
        $this->dataBase->rollback();

        return $message;
    }

    /**
     * 
     * @param BusinessDocumentView $view
     * @param array                $data
     *
     * @return string
     */
    protected function saveDocumentResult(BusinessDocumentView &$view, array &$data)
    {
        /// start transaction
        $this->dataBase->beginTransaction();

        /// sets subjects
        $result = $this->setSubject($view, $data['subject']);
        if ('OK' !== $result) {
            return $this->saveDocumentError($result);
        }

        /// custom data fields
        $view->model->loadFromData($data['custom']);
        if ($view->model->save() && $this->saveLines($view, $data['lines'])) {
            /// final data fields
            $view->model->loadFromData($data['final']);

            $this->documentTools->recalculate($view->model);
            return $view->model->save() && $this->dataBase->commit() ?
                'OK:' . $view->model->url() :
                $this->saveDocumentError('ERROR');
        }

        return $this->saveDocumentError('ERROR');
    }

    /**
     * Save the lines of the document.
     *
     * @param BusinessDocumentView $view
     * @param array                $newLines
     *
     * @return bool
     */
    protected function saveLines(BusinessDocumentView &$view, array &$newLines)
    {
        if (false === $view->model->editable) {
            return true;
        }

        /// remove or modify old lines
        foreach ($view->lines as $oldLine) {
            $found = false;
            foreach ($newLines as $newLine) {
                if ($newLine['idlinea'] != $oldLine->idlinea) {
                    continue;
                }

                $found = true;
                if (false === $this->updateLine($oldLine, $newLine)) {
                    $this->toolBox()->log()->warning('ERROR IN LINE: ' . $oldLine->idlinea);
                    return false;
                }
                break;
            }

            if (false === $found) {
                $oldLine->delete();
            }
        }

        /// add new lines
        foreach (\array_reverse($newLines) as $fLine) {
            if ($fLine['idlinea']) {
                continue;
            }

            if (false === $view->model->getNewLine($fLine)->save()) {
                $this->toolBox()->log()->warning('ERROR IN NEW LINE');
                return false;
            }
        }

        return true;
    }

    /**
     * 
     * @return bool
     */
    protected function subjectChangedAction()
    {
        $this->setTemplate(false);

        /// loads model
        $data = $this->getBusinessFormData();
        $merged = \array_merge($data['custom'], $data['final'], $data['form'], $data['subject']);
        $this->views[$this->active]->loadFromData($merged);

        /// update subject data?
        if (false === $this->views[$this->active]->model->exists()) {
            $this->views[$this->active]->model->updateSubject();
        }

        $this->response->setContent(\json_encode($this->views[$this->active]->model));
        return false;
    }

    /**
     * Updates oldLine with newLine data.
     *
     * @param BusinessDocumentLine $oldLine
     * @param array                $newLine
     *
     * @return bool
     */
    protected function updateLine($oldLine, array $newLine)
    {
        /// reload line data from database to get last changes
        $oldLine->loadFromCode($oldLine->primaryColumnValue());

        $oldLine->loadFromData($newLine, ['actualizastock']);
        return $oldLine->save();
    }
}
