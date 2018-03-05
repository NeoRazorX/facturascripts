<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Lib\ExtendedController;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\DivisaTools;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Lib\ExportManager;
use FacturaScripts\Core\Model\Base\SalesDocumentLine;
use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Core\Model\Proveedor;
use FacturaScripts\Dinamic\Lib\DocumentCalculator;

/**
 * Description of DocumentView
 *
 * @author Carlos García Gómez
 */
class DocumentView extends BaseView
{

    /**
     * Document calculator object.
     *
     * @var DocumentCalculator
     */
    private $calculator;

    /**
     * Document type (sale/purchase)
     *
     * @var string
     */
    public $documentType;

    /**
     * Model name for the line of this document type.
     *
     * @var string
     */
    private $lineModelName;

    /**
     * Line columns from xmlview.
     *
     * @var array
     */
    private $lineOptions;

    /**
     * Lines of document, the body.
     *
     * @var SalesDocumentLine[]
     */
    public $lines;

    /**
     * DocumentView constructor and initialization.
     *
     * @param string $title
     * @param string $modelName
     * @param string $lineModelName
     * @param string $lineXMLView
     * @param string $userNick
     */
    public function __construct($title, $modelName, $lineModelName, $lineXMLView, $userNick)
    {
        parent::__construct($title, $modelName);
        $this->calculator = new DocumentCalculator();
        $this->documentType = 'sale';

        // Loads the view configuration for the user
        $this->pageOption->getForUser($lineXMLView, $userNick);

        $this->lineModelName = $lineModelName;
        $this->lineOptions = [];
        foreach ($this->pageOption->columns['root']->columns as $col) {
            $this->lineOptions[] = $col;
        }

        $this->lines = [];
    }

    /**
     * Returns the data of lines to the view.
     *
     * @return string
     */
    public function getLineData()
    {
        $data = [
            'headers' => [],
            'columns' => [],
            'rows' => []
        ];

        foreach ($this->lineOptions as $col) {
            $item = [
                'data' => $col->widget->fieldName,
                'type' => $col->widget->type,
            ];

            if ($item['type'] === 'number' || $item['type'] === 'money') {
                $item['type'] = 'numeric';
                $item['numericFormat'] = DivisaTools::gridMoneyFormat();
            } elseif ($item['type'] === 'autocomplete') {
                $item['source'] = $col->widget->values[0];
                $item['strict'] = false;
                $item['visibleRows'] = 5;
                $item['trimDropdown'] = false;
            }

            if ($col->display !== 'none') {
                $data['columns'][] = $item;
                $data['headers'][] = self::$i18n->trans($col->title);
            }
        }

        foreach ($this->lines as $line) {
            $lineArray = (array) $line;
            $lineArray['descripcion'] = Utils::fixHtml($lineArray['descripcion']);
            $data['rows'][] = $lineArray;
        }

        return json_encode($data);
    }

    /**
     * Method to export the view data
     *
     * @param ExportManager $exportManager
     */
    public function export(&$exportManager)
    {
        $exportManager->generateDocumentPage($this->model);
    }

    /**
     * Load the data in the cursor property, according to the where filter specified.
     * Adds an empty row/model at the end of the loaded data.
     *
     * @param bool  $code
     * @param array $where
     */
    public function loadData($code = false, $where = [])
    {
        if ($this->newCode !== null) {
            $code = $this->newCode;
        }

        if (is_array($code)) {
            $where = [];
            foreach ($code as $fieldName => $value) {
                $where[] = new DataBaseWhere($fieldName, $value);
            }
            $this->model->loadFromCode('', $where);
        } else {
            $this->model->loadFromCode($code);
        }

        $fieldName = $this->model->primaryColumn();
        $this->count = empty($this->model->primaryColumnValue()) ? 0 : 1;
        $this->lines = empty($this->model->primaryColumnValue()) ? [] : $this->model->getLineas();
        $this->title = $this->model->codigo;
    }

    /**
     * Load data, calculate the needed document values and return the total.
     *
     * @param mixed $data
     *
     * @return string
     */
    public function calculateDocument(&$data)
    {
        $newLines = isset($data['lines']) ? $this->processFormLines($data['lines']) : [];
        unset($data['lines']);
        $this->loadFromData($data);

        return $this->calculator->calculateForm($this->model, $newLines);
    }

    /**
     * Save all document related data.
     *
     * @param $data
     *
     * @return string
     */
    public function saveDocument(&$data)
    {
        $result = 'OK';
        $codcliente = isset($data['codcliente']) ? $data['codcliente'] : '';
        $codproveedor = isset($data['codproveedor']) ? $data['codproveedor'] : '';
        $newLines = isset($data['lines']) ? $this->processFormLines($data['lines']) : [];
        unset($data['codcliente']);
        unset($data['codproveedor']);
        unset($data['lines']);
        $this->loadFromData($data);
        $this->model->setFecha($this->model->fecha);
        $this->lines = empty($this->model->primaryColumnValue()) ? [] : $this->model->getLineas();

        if ($this->documentType === 'sale') {
            $result = $this->setCustomer($codcliente, $data['new_cliente'], $data['new_cifnif']);
        } elseif ($this->documentType === 'purchase') {
            $result = $this->setSupplier($codproveedor, $data['new_proveedor'], $data['new_cifnif']);
        }

        if ($result !== 'OK') {
            return $result;
        }

        $new = empty($this->model->primaryColumnValue());
        if ($this->save()) {
            $result = $this->saveLines($newLines);
        } else {
            $result = 'ERROR';
        }

        if ($result === 'OK') {
            $this->calculator->calculate($this->model);
            $result = $this->model->save() ? 'OK' : 'ERROR';
            return $new ? 'NEW:' . $this->model->url() : $result;
        }

        $miniLog = new MiniLog();
        foreach ($miniLog->read() as $msg) {
            $result = $msg['message'];
        }

        return $result;
    }

    /**
     * Set the customer for this model.
     *
     * @param string $codcliente
     * @param string $newCliente
     * @param string $newCifnif
     *
     * @return string
     */
    private function setCustomer($codcliente, $newCliente = '', $newCifnif = '')
    {
        if ($this->model->codcliente === $codcliente && !empty($this->model->codcliente)) {
            return 'OK';
        }

        $cliente = new Cliente();
        if ($cliente->loadFromCode($codcliente)) {
            $this->model->setCliente($cliente);
            return 'OK';
        }

        if ($newCliente !== '') {
            $cliente->nombre = $cliente->razonsocial = $newCliente;
            $cliente->cifnif = $newCifnif;
            if ($cliente->save()) {
                return $this->setCustomer($cliente->codcliente);
            }
        }

        return 'ERROR: NO CUSTOMER';
    }

    /**
     * Set the supplier for this model.
     *
     * @param string $codproveedor
     * @param string $newProveedor
     * @param string $newCifnif
     *
     * @return string
     */
    private function setSupplier($codproveedor, $newProveedor = '', $newCifnif = '')
    {
        if ($this->model->codproveedor === $codproveedor && !empty($this->model->codproveedor)) {
            return 'OK';
        }

        $proveedor = new Proveedor();
        if ($proveedor->loadFromCode($codproveedor)) {
            $this->model->setProveedor($proveedor);
            return 'OK';
        }

        if ($newProveedor !== '') {
            $proveedor->nombre = $proveedor->razonsocial = $newProveedor;
            $proveedor->cifnif = $newCifnif;
            if ($proveedor->save()) {
                return $this->setSupplier($proveedor->codproveedor);
            }
        }

        return 'ERROR: NO SUPPLIER';
    }

    /**
     * Saves the lines for the document.
     *
     * @param $newLines
     *
     * @return string
     */
    private function saveLines(&$newLines)
    {
        $result = 'OK';

        /// remove or modify old lines
        foreach ($this->lines as $oldLine) {
            $found = false;
            foreach ($newLines as $newLine) {
                if ($newLine['idlinea'] == $oldLine->idlinea) {
                    $found = true;
                    if (!$this->updateLine($oldLine, $newLine)) {
                        $result = 'ERROR ON LINE: ' . $oldLine->idlinea;
                    }
                    break;
                }
            }

            if (!$found) {
                $oldLine->delete();
            }
        }

        /// add new lines
        $lineClass = $this->lineModelName;
        foreach ($newLines as $newLine) {
            if (empty($newLine['idlinea']) && !empty($newLine['descripcion'])) {
                $newDocLine = new $lineClass($newLine);
                $newDocLine->idlinea = null;
                $newDocLine->{$this->model->primaryColumn()} = $this->model->primaryColumnValue();
                $newDocLine->pvpsindto = $newDocLine->pvpunitario * $newDocLine->cantidad;
                $newDocLine->pvptotal = $newDocLine->pvpsindto * (100 - $newDocLine->dtopor) / 100;

                if (!$newDocLine->save()) {
                    $result = "ERROR ON NEW LINE";
                }
            }
        }

        return $result;
    }

    /**
     * Set new code to the document.
     */
    public function setNewCode()
    {
        /// this can be eliminated when the error is fixed when calculating
        /// a new code when the primary key is numeric
    }

    /**
     * Updates oldLine with newLine data.
     *
     * @param mixed $oldLine
     * @param array $newLine
     *
     * @return bool
     */
    protected function updateLine($oldLine, $newLine)
    {
        foreach ($newLine as $key => $value) {
            $oldLine->{$key} = $value;
        }

        $oldLine->pvpsindto = $oldLine->pvpunitario * $oldLine->cantidad;
        $oldLine->pvptotal = $oldLine->pvpsindto * (100 - $oldLine->dtopor) / 100;

        return $oldLine->save();
    }

    /**
     * Process form lines to assign only configurated columns.
     * Also adds order column.
     *
     * @param array $formLines
     *
     * @return array
     */
    protected function processFormLines($formLines)
    {
        $newLines = [];
        $columns = [];
        foreach ($this->lineOptions as $col) {
            $columns[] = $col->widget->fieldName;
        }

        $order = count($formLines);
        foreach ($formLines as $data) {
            $line = ['orden' => $order];
            foreach ($this->lineOptions as $col) {
                $line[$col->widget->fieldName] = $data[$col->widget->fieldName];
            }
            $newLines[] = $line;
            $order--;
        }

        return $newLines;
    }
}
