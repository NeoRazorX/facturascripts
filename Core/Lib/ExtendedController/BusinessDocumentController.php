<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base;
use FacturaScripts\Dinamic\Lib\BusinessDocumentTools;

/**
 * Description of BusinessDocumentController
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class BusinessDocumentController extends PanelController
{

    /**
     * Default item limit for selects.
     */
    const ITEM_SELECT_LIMIT = 500;

    /**
     *
     * @var BusinessDocumentTools
     */
    private $documentTools;

    /**
     * Returns the document class name.
     *
     * @return string
     */
    abstract protected function getModelClassName();

    /**
     * Retuns an url to create a new subject.
     */
    abstract public function getNewSubjectUrl();

    /**
     * Returns an array of columns needed for subject.
     */
    abstract public function getSubjectColumns();

    /**
     * 
     */
    abstract protected function loadCustomContactsWidget(&$view);

    /**
     * Sets subject for this document.
     */
    abstract protected function setSubject(&$view, $formData);

    /**
     * Starts all the objects and properties.
     *
     * @param Base\Cache      $cache
     * @param Base\Translator $i18n
     * @param Base\MiniLog    $miniLog
     * @param string          $className
     * @param string          $uri
     */
    public function __construct(&$cache, &$i18n, &$miniLog, $className, $uri = '')
    {
        parent::__construct($cache, $i18n, $miniLog, $className, $uri);
        $this->documentTools = new BusinessDocumentTools();
    }

    /**
     * Returns an array with all data from selected model.
     *
     * @param string $modelName
     *
     * @return mixed
     */
    public function getSelectValues($modelName)
    {
        $values = [];
        $modelName = self::MODEL_NAMESPACE . $modelName;
        $model = new $modelName();

        $order = [$model->primaryDescriptionColumn() => 'ASC'];
        foreach ($model->all([], $order, 0, self::ITEM_SELECT_LIMIT) as $newModel) {
            $values[$newModel->primaryColumnValue()] = $newModel->primaryDescription();
        }

        return $values;
    }

    /**
     * Load views and document.
     */
    protected function createViews()
    {
        /// document tab
        $fullModelName = self::MODEL_NAMESPACE . $this->getModelClassName();
        $view = new BusinessDocumentView($this->getLineXMLView(), 'new', $fullModelName);
        $this->addCustomView($view->getViewName(), $view);

        /// edit tab
        $viewName = 'Edit' . $this->getModelClassName();
        $this->addEditView($viewName, $this->getModelClassName(), 'detail', 'fas fa-edit');

        /// tabs on top
        $this->setTabsPosition('top');
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
            case 'recalculate-document':
                return $this->recalculateDocumentAction();

            case 'save-document':
                return $this->saveDocumentAction();
        }

        return parent::execPreviousAction($action);
    }

    /**
     * Run the controller after actions
     *
     * @param string $action
     */
    protected function execAfterAction($action)
    {
        switch ($action) {
            case 'export':
                $this->setTemplate(false);
                $this->exportManager->newDoc($this->request->get('option'));
                foreach ($this->views as $selectedView) {
                    $selectedView->export($this->exportManager);
                    break;
                }
                $this->exportManager->show($this->response);
                break;

            case 'save-ok':
                $this->miniLog->notice($this->i18n->trans('record-updated-correctly'));
                break;
        }
    }

    /**
     * 
     * @return array
     */
    protected function getBusinessFormData()
    {
        $data = ['exclude' => [], 'form' => [], 'lines' => []];
        foreach ($this->request->request->all() as $field => $value) {
            switch ($field) {
                case 'codcliente':
                case 'codproveedor':
                case 'fecha':
                case 'idestado':
                    $data['exclude'][$field] = $value;
                    break;

                case 'lines':
                    $data['lines'] = $this->views[$this->active]->processFormLines($value);
                    break;

                default:
                    $data['form'][$field] = $value;
                    break;
            }
        }

        return $data;
    }

    /**
     * Return the name of the xml file with the column configuration por lines.
     *
     * @return string
     */
    protected function getLineXMLView()
    {
        return 'BusinessDocumentLine';
    }

    /**
     * Load view data procedure
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $primaryKey = $this->request->request->get($view->model->primaryColumn());
        $code = $this->request->query->get('code', $primaryKey);
        if (empty($code)) {
            return;
        }

        $documentView = $this->getLineXMLView();
        $editViewName = 'Edit' . $this->getModelClassName();
        switch ($viewName) {
            case $editViewName:
                $view->loadData($code);
                $this->loadCustomContactsWidget($view);
                break;

            case $documentView:
                $view->loadData($code);
                /// data not found?
                $action = $this->request->request->get('action', '');
                if (!empty($code) && !$view->model->exists() && '' === $action) {
                    $this->miniLog->warning($this->i18n->trans('record-not-found'));
                }
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
        $this->views[$this->active]->loadFromData($data['form']);

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
        if (!$this->permissions->allowUpdate) {
            $this->response->setContent($this->i18n->trans('not-allowed-modify'));
            return false;
        }

        /// loads model
        $data = $this->getBusinessFormData();
        $this->views[$this->active]->loadFromData($data['form']);
        $this->views[$this->active]->lines = $this->views[$this->active]->model->getLines();

        /// save
        $result = $this->saveDocumentResult($this->views[$this->active], $data);
        $this->response->setContent($result);
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
        foreach ($this->miniLog->read() as $msg) {
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
        if (!$view->model->exists()) {
            $view->model->nick = $this->user->nick;
        }

        /// sets date, hour and accounting exercise
        if (!$view->model->setDate($data['exclude']['fecha'], $view->model->hora)) {
            return $this->saveDocumentError('ERROR: BAD DATE');
        }

        /// sets subjects
        $result = $this->setSubject($view, $data['exclude']);
        if ('OK' !== $result) {
            return $this->saveDocumentError($result);
        }

        /// start transaction
        $this->dataBase->beginTransaction();

        if ($view->model->save() && $this->saveLines($view, $data['lines'])) {
            $this->documentTools->recalculate($view->model);
            $view->model->idestado = $data['exclude']['idestado'];
            return $view->model->save() && $this->dataBase->commit() ? 'OK:' . $view->model->url() : $this->saveDocumentError('ERROR');
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
        if (!$view->model->editable) {
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
                if (!$this->updateLine($oldLine, $newLine)) {
                    $this->miniLog->warning('ERROR IN LINE: ' . $oldLine->idlinea);
                    return false;
                }
                break;
            }

            if (!$found) {
                $oldLine->delete();
            }
        }

        /// add new lines
        $skip = true;
        foreach (array_reverse($newLines) as $fLine) {
            if (empty($fLine['referencia']) && empty($fLine['descripcion']) && $skip) {
                continue;
            }

            if (empty($fLine['idlinea'])) {
                $skip = false;

                $newDocLine = $view->model->getNewLine($fLine);
                if ($newDocLine->save()) {
                    continue;
                }

                $this->miniLog->warning('ERROR IN NEW LINE');
                return false;
            }
        }

        return true;
    }

    /**
     * Updates oldLine with newLine data.
     *
     * @param mixed $oldLine
     * @param array $newLine
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
