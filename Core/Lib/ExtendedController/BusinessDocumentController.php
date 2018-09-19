<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Lib\BusinessDocumentTools;
use FacturaScripts\Dinamic\Model\Impuesto;

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
        /// doc tab
        $fullModelName = self::MODEL_NAMESPACE . $this->getModelClassName();
        $view = new BusinessDocumentView('new', $fullModelName, $this->getLineXMLView(), $this->user->nick);
        $this->addView('Document', $view, 'fa-file');

        /// edita tab
        $viewName = 'Edit' . $this->getModelClassName();
        $this->addEditView($viewName, $this->getModelClassName(), 'detail', 'fa-edit');

        /// template
        $this->setTemplate('Master/BusinessDocumentController');
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

            default:
                return parent::execPreviousAction($action);
        }
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

            case 'insert':
                parent::execAfterAction($action);
                $this->views['Document']->model->updateSubject();
                break;

            default:
                parent::execAfterAction($action);
        }
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
        $iddoc = $this->request->get('code', '');
        if (empty($iddoc)) {
            return;
        }

        $editViewName = 'Edit' . $this->getModelClassName();
        switch ($viewName) {
            case $editViewName:
                $view->loadData($iddoc);
                $this->loadCustomContactsWidget($view);
                return;

            case 'Document':
                return $view->loadData($iddoc);
        }
    }

    /**
     * Recalculate the document total based on lines.
     *
     * @return bool
     */
    protected function recalculateDocumentAction(): bool
    {
        $this->setTemplate(false);
        $view = $this->views[$this->active];

        /// gets data form and separate lines data
        $data = $this->getFormData();
        $newLines = isset($data['lines']) ? $view->processFormLines($data['lines']) : [];
        unset($data['lines']);

        /// loads model
        $view->loadFromData($data);

        /// recalculate
        $result = $this->documentTools->recalculateForm($view->model, $newLines);
        $this->response->setContent($result);
        return false;
    }

    /**
     * Saves the document.
     *
     * @return bool
     */
    protected function saveDocumentAction(): bool
    {
        $this->setTemplate(false);
        if (!$this->permissions->allowUpdate) {
            $this->response->setContent($this->i18n->trans('not-allowed-modify'));
            return false;
        }

        $view = $this->views[$this->active];

        /// gets data form and separate date, hour, codcliente, codproveedor and lines data
        $data = $this->getFormData();
        $codcliente = isset($data['codcliente']) ? $data['codcliente'] : '';
        $codproveedor = isset($data['codproveedor']) ? $data['codproveedor'] : '';
        $fecha = isset($data['fecha']) ? $data['fecha'] : $view->model->fecha;
        $hora = isset($data['hora']) ? $data['hora'] : $view->model->hora;
        $newLines = isset($data['lines']) ? $view->processFormLines($data['lines']) : [];
        unset($data['fecha'], $data['hora'], $data['codcliente'], $data['codproveedor'], $data['lines']);

        /// loads model and lines
        $view->loadFromData($data);
        $view->lines = empty($view->model->primaryColumnValue()) ? [] : $view->model->getLines();

        /// save
        $data['fecha'] = $fecha;
        $data['hora'] = $hora;
        if (!empty($codcliente)) {
            $data['codcliente'] = $codcliente;
        }
        if (!empty($codproveedor)) {
            $data['codproveedor'] = $codproveedor;
        }

        $result = $this->saveDocumentResult($view, $data, $newLines);
        $this->response->setContent($result);
        return false;
    }

    protected function saveDocumentResult(BusinessDocumentView &$view, array &$data, array &$newLines): string
    {
        if (!$view->model->setDate($data['fecha'], $data['hora'])) {
            return 'ERROR: BAD DATE';
        }

        /// sets subjects
        $result = $this->setSubject($view, $data);
        if ($result !== 'OK') {
            return $result;
        }

        $exists = $view->model->exists();
        if ($view->model->save()) {
            $result = ($view->model->editable || !$exists) ? $this->saveLines($view, $newLines) : 'OK';
        } else {
            $result = 'ERROR';
        }

        if ($result === 'OK') {
            $this->documentTools->recalculate($view->model);
            return $view->model->save() ? 'OK:' . $view->model->url() : 'ERROR';
        }

        foreach ($this->miniLog->read() as $msg) {
            $result = $msg['message'];
        }

        return $result;
    }

    /**
     * Save the lines of the document.
     *
     * @param BusinessDocumentView $view
     * @param array $newLines
     *
     * @return string
     */
    protected function saveLines(BusinessDocumentView &$view, array &$newLines): string
    {
        $result = 'OK';

        /// remove or modify old lines
        foreach ($view->lines as $oldLine) {
            $found = false;
            foreach ($newLines as $newLine) {
                if ($newLine['idlinea'] != $oldLine->idlinea) {
                    continue;
                }

                $found = true;
                if (!$this->updateLine($oldLine, $newLine)) {
                    $result = 'ERROR ON LINE: ' . $oldLine->idlinea;
                }
                break;
            }

            if (!$found) {
                $oldLine->delete();
                $oldLine->updateStock($view->model->codalmacen);
            }
        }

        /// add new lines
        $skip = true;
        foreach (array_reverse($newLines) as $fLine) {
            if (empty($fLine['referencia']) && empty($fLine['descripcion']) && $skip) {
                continue;
            }

            if (empty($fLine['idlinea'])) {
                $newDocLine = $view->model->getNewLine($fLine);
                $newDocLine->pvpsindto = $newDocLine->pvpunitario * $newDocLine->cantidad;
                $newDocLine->pvptotal = $newDocLine->pvpsindto * (100 - $newDocLine->dtopor) / 100;

                if ($newDocLine->save()) {
                    $newDocLine->updateStock($view->model->codalmacen);
                } else {
                    $result = "ERROR ON NEW LINE";
                }
                $skip = false;
            }
        }

        return $result;
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
        foreach ($newLine as $key => $value) {
            $oldLine->{$key} = $value;
            if ($key === 'iva') {
                $impuesto = new Impuesto();
                $where = [new DataBaseWhere('iva', (int) $value)];
                $impuesto->loadFromCode('', $where);
                $oldLine->codimpuesto = $impuesto->codimpuesto;
            }
        }

        $oldLine->pvpsindto = $oldLine->pvpunitario * $oldLine->cantidad;
        $oldLine->pvptotal = $oldLine->pvpsindto * (100 - $oldLine->dtopor) / 100;

        if ($oldLine->save()) {
            return $oldLine->updateStock($this->views[$this->active]->model->codalmacen);
        }

        return false;
    }
}
