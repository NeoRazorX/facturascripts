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

use FacturaScripts\Core\Base\DivisaTools;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Model\Base\BusinessDocumentLine;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Lib\ExportManager;
use FacturaScripts\Dinamic\Lib\Widget\ColumnItem;
use FacturaScripts\Dinamic\Model\EstadoDocumento;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description of BusinessDocumentView
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class BusinessDocumentView extends BaseView
{

    const ITEM_SELECT_LIMIT = 500;
    const MODEL_NAMESPACE = '\\FacturaScripts\\Dinamic\\Model\\';

    /**
     *
     * @var EstadoDocumento[]
     */
    public $documentStatus = [];

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
    public function __construct($name, $title, $modelName, $icon = 'fas fa-file')
    {
        parent::__construct($name, $title, $modelName, $icon);
        $this->documentStatus = $this->model->getAvaliableStatus();
        $this->template = 'Master/BusinessDocumentView.html.twig';
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
     * 
     * @return ColumnItem[]
     */
    public function getColumns()
    {
        foreach ($this->columns as $group) {
            return $group->columns;
        }

        return [];
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

        foreach ($this->getColumns() as $col) {
            if ($col->hidden()) {
                continue;
            }

            $item = [
                'data' => $col->widget->fieldname,
                'type' => $col->widget->getType(),
            ];

            if ($item['type'] === 'number' || $item['type'] === 'money') {
                $item['type'] = 'numeric';
                $item['numericFormat'] = DivisaTools::gridMoneyFormat();
            } elseif ($item['type'] === 'autocomplete') {
                $item['source'] = $col->widget->getDataSource();
                $item['strict'] = false;
                $item['visibleRows'] = 5;
                $item['trimDropdown'] = false;
            }

            $data['columns'][] = $item;
            $data['headers'][] = self::$i18n->trans($col->title);
        }

        $fixColumns = ['descripcion', 'referencia'];
        foreach ($this->lines as $line) {
            $lineArray = [];
            foreach ($line->getModelFields() as $key => $field) {
                $lineArray[$key] = in_array($key, $fixColumns) ? Utils::fixHtml($line->{$key}) : $line->{$key};
            }
            $data['rows'][] = $lineArray;
        }

        return json_encode($data);
    }

    /**
     * Returns an array with all data from selected model.
     *
     * @param string $modelName
     *
     * @return array
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
     * 
     * @param string $code
     * @param array  $where
     * @param int    $order
     * @param int    $offset
     * @param int    $limit
     */
    public function loadData($code = '', $where = [], $order = [], $offset = 0, $limit = \FS_ITEM_LIMIT)
    {
        if ($this->newCode !== null) {
            $code = $this->newCode;
        }

        if (empty($code) && empty($where)) {
            return;
        }

        $this->model->loadFromCode($code);
        $this->lines = empty($this->model->primaryColumnValue()) ? [] : $this->model->getLines();

        $this->count = count($this->lines);
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
        foreach ($formLines as $line) {
            $line['orden'] = $order;
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
        switch ($case) {
            case 'load':
                foreach ($request->query->all() as $key => $value) {
                    if ($key == 'code') {
                        continue;
                    }

                    $this->model->{$key} = $value;
                    if ($key == $this->model->subjectColumn()) {
                        $this->model->updateSubject();
                    }
                }
                break;
        }
    }

    /**
     * Adds assets to the asset manager.
     */
    protected function assets()
    {
        AssetManager::add('css', \FS_ROUTE . '/node_modules/handsontable/dist/handsontable.full.min.css');
        AssetManager::add('js', \FS_ROUTE . '/node_modules/handsontable/dist/handsontable.full.min.js');
        AssetManager::add('js', \FS_ROUTE . '/Dinamic/Assets/JS/BusinessDocumentView.js');
    }
}
