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
    public $documentStatus;

    /**
     * Line columns from xmlview.
     *
     * @var array
     */
    private $lineOptions;

    /**
     * Lines of document, the body.
     *
     * @var BusinessDocumentLine[]
     */
    public $lines;

    /**
     * DocumentView constructor and initialization.
     *
     * @param string $title
     * @param string $modelName
     * @param string $lineXMLView
     * @param string $icon
     */
    public function __construct(string $title, string $modelName, string $lineXMLView, string $icon)
    {
        parent::__construct($title, $modelName, $icon);
        $this->documentStatus = [];

        // Loads the view configuration for the user
        //$this->pageOption->getForUser($lineXMLView, $userNick);

        $this->lineOptions = [];
        foreach ($this->pageOption->columns['root']->columns as $col) {
            $this->lineOptions[] = $col;
        }

        $this->lines = [];

        // Loads document states
        $estadoDocModel = new EstadoDocumento();
        $modelClass = explode('\\', $modelName);
        $this->documentStatus = $estadoDocModel->all([new DataBaseWhere('tipodoc', end($modelClass))], ['nombre' => 'ASC'], 0, 0);
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
     * Load the data in the cursor property, according to the where filter specified.
     * Adds an empty row/model at the end of the loaded data.
     *
     * @param string $code
     */
    public function loadData(string $code)
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
                $line[$col->widget->fieldName] = isset($data[$col->widget->fieldName]) ? $data[$col->widget->fieldName] : null;
            }
            $newLines[] = $line;
            $order--;
        }

        return $newLines;
    }
}
