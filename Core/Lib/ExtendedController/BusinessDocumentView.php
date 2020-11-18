<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Model\Base\TransformerDocument;
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

    const DEFAULT_TEMPLATE = 'Master/BusinessDocumentView.html.twig';
    const ITEM_SELECT_LIMIT = 500;
    const MODEL_NAMESPACE = '\\FacturaScripts\\Dinamic\\Model\\';
    const MODEL_NAMESPACE_LIB = '\\FacturaScripts\\Dinamic\\Lib\\';

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
     * Model to use in this view.
     *
     * @var TransformerDocument
     */
    public $model;

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
    }

    /**
     * Method to export the view data.
     *
     * @param ExportManager $exportManager
     *
     * @return bool
     */
    public function export(&$exportManager): bool
    {
        return $exportManager->addBusinessDocPage($this->model);
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
     * 
     * @return int
     */
    public function getMaxLines()
    {
        return \intval(\ini_get('max_input_vars') / \count($this->getColumns()));
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
                'className' => $this->getCellAlign($col->display),
                'data' => $col->widget->fieldname,
                'type' => $col->widget->getType(),
                'readOnly' => ($col->widget->readonly == 'true' || !$this->model->editable)
            ];

            if ($item['type'] === 'autocomplete') {
                $item['source'] = $col->widget->getDataSource();
                $item['strict'] = false;
                $item['visibleRows'] = 5;
                $item['trimDropdown'] = false;
            } elseif (\in_array($item['type'], ['money', 'number', 'percentage'], true)) {
                $item['type'] = 'numeric';
                $item['numericFormat'] = $col->widget->gridFormat();
            }

            $data['columns'][] = $item;
            $data['headers'][] = $this->toolBox()->i18n()->trans($col->title);
        }

        $fixColumns = ['descripcion', 'referencia'];
        foreach ($this->lines as $line) {
            $lineArray = [];
            foreach (\array_keys($line->getModelFields()) as $key) {
                $lineArray[$key] = \in_array($key, $fixColumns) ? $this->toolBox()->utils()->fixHtml($line->{$key}) : $line->{$key};
            }
            $data['rows'][] = $lineArray;
        }

        return \json_encode($data);
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
        $classModel = self::MODEL_NAMESPACE . $modelName;
        if (\class_exists($classModel)) {
            $values = [];
            $model = new $classModel();

            $order = [$model->primaryDescriptionColumn() => 'ASC'];
            foreach ($model->all([], $order, 0, self::ITEM_SELECT_LIMIT) as $newModel) {
                $values[$newModel->primaryColumnValue()] = $newModel->primaryDescription();
            }

            return $values;
        }

        $classLib = self::MODEL_NAMESPACE_LIB . $modelName;
        return \class_exists($classLib) ? $classLib::all() : [];
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
        $order = \count($formLines);
        foreach ($formLines as $line) {
            if (is_array($line)) {
                $line['orden'] = $order;
                $newLines[] = $line;
                $order--;
                continue;
            }

            /// empty line
            $newLines[] = ['orden' => $order];
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

    /**
     * 
     * @param string $code
     *
     * @return string
     */
    protected function getCellAlign($code): string
    {
        switch ($code) {
            case 'center':
                return 'htCenter';

            case 'right':
                return 'htRight';

            default:
                return 'htLeft';
        }
    }
}
