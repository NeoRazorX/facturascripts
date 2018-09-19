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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\DivisaTools;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Lib\ExportManager;
use FacturaScripts\Dinamic\Model\Base\BusinessDocumentLine;
use FacturaScripts\Dinamic\Model\EstadoDocumento;

/**
 * Description of BusinessDocumentView
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class BusinessDocumentView extends BaseView
{

    /**
     *
     * @var array
     */
    public $documentStatus = [];

    /**
     * Line columns from xmlview.
     *
     * @var array
     */
    private $lineOptions = [];

    /**
     * Lines of document, the body.
     *
     * @var BusinessDocumentLine[]
     */
    public $lines = [];

    /**
     * 
     * @param string $name
     * @param string $title
     * @param string $modelName
     * @param string $icon
     */
    public function __construct($name, $title, $modelName, $icon = 'fa-file-alt')
    {
        parent::__construct($name, $title, $modelName, $icon);
        foreach ($this->getColumns() as $group) {
            foreach ($group->columns as $col) {
                $this->lineOptions[] = $col;
            }
        }

        // Loads document states
        $estadoDocModel = new EstadoDocumento();
        $modelClass = explode('\\', $modelName);
        $this->documentStatus = $estadoDocModel->all([new DataBaseWhere('tipodoc', end($modelClass))], ['nombre' => 'ASC'], 0, 0);

        // custom template
        $this->template = 'Master/BusinessDocumentView.html.twig';
        static::$assets['css'][] = FS_ROUTE . '/node_modules/handsontable/dist/handsontable.full.min.css';
        static::$assets['js'][] = FS_ROUTE . '/node_modules/handsontable/dist/handsontable.full.min.js';
        static::$assets['js'][] = FS_ROUTE . '/Dinamic/Assets/JS/BusinessDocumentController.js';
    }

    /**
     * Method to export the view data.
     *
     * @param ExportManager $exportManager
     */
    public function export(&$exportManager)
    {
        $exportManager->generateBusinessDocPage($this->model);
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
                'data' => $col->widget->fieldname,
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
            $lineArray = [];
            foreach ($line->getModelFields() as $key => $field) {
                $lineArray[$key] = $line->{$key};
            }
            $lineArray['descripcion'] = Utils::fixHtml($lineArray['descripcion']);
            $data['rows'][] = $lineArray;
        }

        return json_encode($data);
    }

    /**
     * 
     * @param type $code
     * @param type $where
     * @param type $order
     * @param type $offset
     * @param type $limit
     */
    public function loadData($code = false, $where = array(), $order = array(), $offset = 0, $limit = FS_ITEM_LIMIT)
    {
        if ($this->newCode !== null) {
            $code = $this->newCode;
        }

        $this->model->loadFromCode($code);
        $this->count = empty($this->model->primaryColumnValue()) ? 0 : 1;
        $this->lines = empty($this->model->primaryColumnValue()) ? [] : $this->model->getLines();
        $this->title = $this->model->codigo;
    }

    /**
     * Process form lines to add missing data from data form.
     * Also adds order column.
     *
     * @param array $formLines
     *
     * @return array
     */
    public function processFormLines(array $formLines)
    {
        $newLines = [];
        $order = count($formLines);
        foreach ($formLines as $data) {
            $line = ['orden' => $order];
            foreach ($this->lineOptions as $col) {
                $line[$col->widget->fieldname] = isset($data[$col->widget->fieldname]) ? $data[$col->widget->fieldname] : null;
            }
            $newLines[] = $line;
            $order--;
        }

        return $newLines;
    }

    /**
     * 
     * @param Request $request
     * @param string  $case
     */
    public function processFormData($request, $case)
    {
        ;
    }
}
