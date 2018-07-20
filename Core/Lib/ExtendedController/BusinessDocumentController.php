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
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Proveedor;

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
     * Return the document class name.
     *
     * @return string
     */
    abstract protected function getModelClassName();

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
        $modelName = '\FacturaScripts\Dinamic\Model\\' . $modelName;
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
        $modelName = '\\FacturaScripts\\Dinamic\\Model\\' . $this->getModelClassName();
        $view = new BusinessDocumentView('new', $modelName, $this->getLineXMLView(), $this->user->nick);
        $this->addView('Document', $view, 'fa-file');

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
        if ($viewName === 'Document' && !empty($iddoc)) {
            $view->loadData($iddoc);
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
        if (!empty($codcliente)) {
            $idContactoEnv = $this->getContactoIdBy($codcliente, 'idcontactoenv');
            $idContactoFact = $this->getContactoIdBy($codcliente, 'idcontactofact');
        }

        $codproveedor = isset($data['codproveedor']) ? $data['codproveedor'] : '';
        $fecha = isset($data['fecha']) ? $data['fecha'] : $view->model->fecha;
        $hora = isset($data['hora']) ? $data['hora'] : $view->model->hora;
        $newLines = isset($data['lines']) ? $view->processFormLines($data['lines']) : [];
        unset($data['fecha'], $data['hora'], $data['codcliente'], $data['codproveedor'], $data['lines']);

        /// loads model and lines
        $view->loadFromData($data);
        $view->lines = empty($view->model->primaryColumnValue()) ? [] : $view->model->getLines();

        /// save
        $data['codcliente'] = $codcliente;
        $data['idcontactoenv'] = $idContactoEnv;
        $data['idcontactofact'] = $idContactoFact;
        $data['codproveedor'] = $codproveedor;
        $data['fecha'] = $fecha;
        $data['hora'] = $hora;
        $result = $this->saveDocumentResult($view, $data, $newLines);
        $this->response->setContent($result);
        return false;
    }

    /**
     * Return contact ID based on parameters
     *
     * @param string $code code of codcliente or codproveedor
     * @param string $field 'idcontactoenv', 'idcontactofact' or empty to ignore
     * @param string $codType 'codcliente' or 'codproveedor'
     *
     * @return null|int
     */
    private function getContactoIdBy($code, $field = '', $codType = 'codcliente')
    {
        $where = [new Base\DataBase\DataBaseWhere($codType, $code)];
        switch ($field) {
            case 'idcontactoenv':
            case 'idcontactofact':
                $where[] = new Base\DataBase\DataBaseWhere($field, null, 'IS NOT');
                break;
            default:
                return null;
        }

        $cliente = new Cliente();
        if ($cliente->loadFromCode('', $where)) {
            return $cliente->{$field} ?? null;
        }
        return null;
    }

    /**
     * Saves the document data.
     *
     * @param BusinessDocumentView $view
     * @param array $data
     * @param array $newLines
     *
     * @return string
     */
    protected function saveDocumentResult(BusinessDocumentView &$view, array &$data, array &$newLines): string
    {
        if (!$view->model->setDate($data['fecha'], $data['hora'])) {
            return 'ERROR: BAD DATE';
        }

        /// sets subjects
        $result = 'OK';
        if (in_array('codcliente', $view->model->getSubjectColumns())) {
            $result = $this->setCustomer($view, $data['codcliente'], $data['new_cliente'], $data['new_cifnif']);
            $view->model->idcontactoenv = $data['idcontactoenv'];
            $view->model->idcontactofact = $data['idcontactofact'];
        }
        if (in_array('codproveedor', $view->model->getSubjectColumns())) {
            $result = $this->setSupplier($view, $data['codproveedor'], $data['new_proveedor'], $data['new_cifnif']);
        }

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
     * Set customer data to document, based on codcliente.
     *
     * @param BusinessDocumentView $view
     * @param string $codcliente
     * @param string $newCliente
     * @param string $newCifnif
     *
     * @return string
     */
    protected function setCustomer(BusinessDocumentView &$view, string $codcliente, string $newCliente = '', string $newCifnif = ''): string
    {
        if ($view->model->codcliente === $codcliente && !empty($view->model->codcliente)) {
            return 'OK';
        }

        $cliente = new Cliente();
        if ($cliente->loadFromCode($codcliente)) {
            $view->model->setSubject([$cliente]);
            return 'OK';
        }

        if ($newCliente !== '') {
            $cliente->nombre = $cliente->razonsocial = $newCliente;
            $cliente->cifnif = $newCifnif;
            if ($cliente->save()) {
                return $this->setCustomer($view, $cliente->codcliente);
            }
        }

        return 'ERROR: NO CUSTOMER';
    }

    /**
     * Set supplier data to document, based on codproveedor.
     *
     * @param BusinessDocumentView $view
     * @param string $codproveedor
     * @param string $newProveedor
     * @param string $newCifnif
     *
     * @return string
     */
    protected function setSupplier(BusinessDocumentView &$view, string $codproveedor, string $newProveedor = '', string $newCifnif = ''): string
    {
        if ($view->model->codproveedor === $codproveedor && !empty($view->model->codproveedor)) {
            return 'OK';
        }

        $proveedor = new Proveedor();
        if ($proveedor->loadFromCode($codproveedor)) {
            $view->model->setSubject([$proveedor]);
            return 'OK';
        }

        if ($newProveedor !== '') {
            $proveedor->nombre = $proveedor->razonsocial = $newProveedor;
            $proveedor->cifnif = $newCifnif;
            if ($proveedor->save()) {
                return $this->setSupplier($view, $proveedor->codproveedor);
            }
        }

        return 'ERROR: NO SUPPLIER';
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
        }

        $oldLine->pvpsindto = $oldLine->pvpunitario * $oldLine->cantidad;
        $oldLine->pvptotal = $oldLine->pvpsindto * (100 - $oldLine->dtopor) / 100;

        if ($oldLine->save()) {
            return $oldLine->updateStock($this->views[$this->active]->model->codalmacen);
        }

        return false;
    }

    /**
     * Load custom contacts data for additional address details.
     *
     * @param string $viewName
     * @param string $field
     */
    protected function loadCustomContactsWidget($viewName, $field = 'codcliente')
    {
        $commonWhere = [new DataBaseWhere($field, $this->views[$viewName]->model->{$field})];

        // Fill billingaddr select widget with contacts
        $billingAddresses = [];
        $billingWhere = array_merge([new DataBaseWhere('idcontactofact', null, 'IS NOT')], $commonWhere);
        foreach ($this->codeModel->all('contactos', $field, 'nombre', false, $billingWhere) as $code) {
            $billingAddresses[] = ['value' => $code->code, 'title' => $code->description];
        }
        $columnBilling = $this->views[$viewName]->columnForName('billingaddr');
        $columnBilling->widget->setValuesFromArray($billingAddresses, false);

        // Fill shippingaddr select widget with contacts
        $shippingAddresses = [];
        $shippingWhere = array_merge([new DataBaseWhere('idcontactoenv', null, 'IS NOT')], $commonWhere);
        foreach ($this->codeModel->all('contactos', $field, 'nombre', false, $shippingWhere) as $code) {
            $shippingAddresses[] = ['value' => $code->code, 'title' => $code->description];
        }
        $columnShipping = $this->views[$viewName]->columnForName('shippingaddr');
        $columnShipping->widget->setValuesFromArray($shippingAddresses, false);
    }
}
