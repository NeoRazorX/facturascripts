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

use Exception;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\DivisaTools;
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Lib\ExportManager;
use FacturaScripts\Dinamic\Lib\Widget\ColumnItem;
use FacturaScripts\Dinamic\Lib\Widget\WidgetAutocomplete;
use FacturaScripts\Dinamic\Lib\Widget\WidgetSelect;

/**
 * Description of GridView
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class GridView extends EditView
{

    const GRIDVIEW_TEMPLATE = 'Master/GridView.html.twig';

    /**
     *
     * @var ModelClass
     */
    public $detailModel;

    /**
     * Detail view
     *
     * @var BaseView
     */
    public $detailView;

    /**
     * Template for edit master data
     *
     * @var string
     */
    public $editTemplate = self::EDIT_TEMPLATE;

    /**
     * Grid data configuration and data
     *
     * @var array
     */
    private $gridData;

    /**
     * GridView constructor and initialization.
     * Master/Detail params:
     *   ['name' = 'viewName', 'model' => 'modelName']
     *
     * @param array   $master
     * @param array   $detail
     * @param string  $title
     * @param string  $icon
     */
    public function __construct($master, $detail, $title, $icon)
    {
        parent::__construct($master['name'], $title, $master['model'], $icon);

        // Create detail view
        $this->detailView = new EditView($detail['name'], $title, $detail['model'], $icon);
        $this->detailModel = $this->detailView->model;

        // custom template
        $this->template = self::GRIDVIEW_TEMPLATE;
    }

    /**
     * 
     * @param ExportManager $exportManager
     */
    public function export(&$exportManager)
    {
        parent::export($exportManager);
        $headers = $this->gridData['headers'];
        $formattedRows = [];
        foreach ($this->gridData['rows'] as $row) {
            $formattedRow = [];
            foreach ($this->gridData['columns'] as $column) {
                $formattedRow[] = isset($row[$column['data']]) ? $row[$column['data']] : '';
            }
            $formattedRows[] = array_combine($headers, $formattedRow);
        }
        $exportManager->generateTablePage($headers, $formattedRows);
    }

    /**
     * Returns detail column configuration
     *
     * @param string $key
     *
     * @return ColumnItem[]
     */
    public function getDetailColumns($key = '')
    {
        if (!array_key_exists($key, $this->detailView->columns)) {
            if ($key == 'master') {
                return [];
            }
            $key = array_keys($this->detailView->columns)[0];
        }

        return $this->detailView->columns[$key]->columns;
    }

    /**
     * Returns JSON into string with Grid view data
     *
     * @return string
     */
    public function getGridData(): string
    {
        return json_encode($this->gridData);
    }

    /**
     * Load the data in the model property, according to the code specified.
     *
     * @param string          $code
     * @param DataBaseWhere[] $where
     * @param array           $order
     * @param int             $offset
     * @param int             $limit
     */
    public function loadData($code = '', $where = [], $order = [], $offset = 0, $limit = \FS_ITEM_LIMIT)
    {
        parent::loadData($code, $where, $order, $offset, $limit);

        if ($this->count == 0) {
            $this->template = self::EDIT_TEMPLATE;
            return;
        }

        if ($this->newCode !== null) {
            $code = $this->newCode;
        }

        $where[] = new DataBaseWhere($this->model->primaryColumn(), $code);
        $order[$this->detailModel->primaryColumn()] = 'ASC';
        $this->loadGridData($where, $order);
    }

    /**
     * Load detail data and set grid configuration
     *
     * @param DataBaseWhere[] $where
     * @param array           $order
     */
    public function loadGridData($where = [], $order = [])
    {
        // load columns configuration
        $this->gridData = $this->getGridColumns();

        // load detail model data
        $this->gridData['rows'] = [];
        $this->detailView->count = $this->detailView->model->count($where);
        if ($this->detailView->count == 0) {
            return;
        }

        foreach ($this->detailModel->all($where, $order, 0, 0) as $line) {
            /// do not change to (array) $line
            $row = [];
            foreach (array_keys($line->getModelFields()) as $field) {
                $row[$field] = $line->{$field};
            }

            $this->gridData['rows'][] = $row;
        }
    }

    /**
     *
     * @param array $lines
     * @return array
     */
    public function processFormLines(&$lines): array
    {
        $result = [];
        $primaryKey = $this->detailModel->primaryColumn();
        foreach ($lines as $data) {
            if (!isset($data[$primaryKey])) {
                foreach ($this->getDetailColumns('detail') as $col) {
                    if (!isset($data[$col->widget->fieldname])) {
                        // TODO: maybe the widget can have a default value method instead of null
                        $data[$col->widget->fieldname] = null;
                    }
                }
            }
            $result[] = $data;
        }

        return $result;
    }

    /**
     * 
     * @param array $data
     *
     * @return array
     */
    public function saveData($data): array
    {
        $result = [
            'error' => false,
            'message' => '',
            'url' => ''
        ];

        try {
            // load master document data and test it's ok
            if (!$this->loadDocumentDataFromArray('code', $data['document'])) {
                throw new Exception(self::$i18n->trans('parent-document-test-error'));
            }

            // load detail document data (old)
            $documentFieldKey = $this->model->primaryColumn();
            $documentFieldValue = $this->model->primaryColumnValue();
            $linesOld = $this->detailModel->all([new DataBaseWhere($documentFieldKey, $documentFieldValue)]);

            // start transaction
            $dataBase = new DataBase();
            $dataBase->beginTransaction();

            // delete old lines not used
            if (!$this->deleteLinesOld($linesOld, $data['lines'])) {
                throw new Exception(self::$i18n->trans('lines-delete-error'));
            }

            // Proccess detail document data (new)
            $this->model->initTotals(); // Master Model must implement GridModelInterface
            foreach ($data['lines'] as $newLine) {
                if (!$this->saveLines($documentFieldKey, $documentFieldValue, $newLine)) {
                    throw new Exception(self::$i18n->trans('lines-save-error'));
                }
                $this->model->accumulateAmounts($newLine); // Master Model must implement GridModelInterface
            }

            // save master document
            if (!$this->model->save()) {
                throw new Exception(self::$i18n->trans('parent-document-save-error'));
            }

            // confirm save data into database
            $dataBase->commit();

            // URL for refresh data
            $result['url'] = $this->model->url('edit') . '&action=save-ok';
        } catch (Exception $err) {
            $result['error'] = true;
            $result['message'] = $err->getMessage();
        } finally {
            if ($dataBase->inTransaction()) {
                $dataBase->rollback();
            }
            return $result;
        }
    }

    protected function assets()
    {
        AssetManager::add('css', \FS_ROUTE . '/node_modules/handsontable/dist/handsontable.full.min.css');
        AssetManager::add('js', \FS_ROUTE . '/node_modules/handsontable/dist/handsontable.full.min.js');
        AssetManager::add('js', \FS_ROUTE . '/Dinamic/Assets/JS/GridView.js');
    }

    /**
     * Removes from the database the non-existent detail
     *
     * @param ModelClass[] $linesOld
     * @param array        $linesNew
     *
     * @return bool
     */
    private function deleteLinesOld(&$linesOld, &$linesNew): bool
    {
        if (empty($linesOld)) {
            return true;
        }

        $fieldPK = $this->detailModel->primaryColumn();
        foreach ($linesOld as $lineOld) {
            $found = false;
            foreach ($linesNew as $lineNew) {
                if ($lineOld->{$fieldPK} == $lineNew[$fieldPK]) {
                    $found = true;
                    break;
                }
            }

            if (!$found && !$lineOld->delete()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Configure autocomplete column with data to Grid component
     *
     * @param WidgetAutocomplete $widget
     *
     * @return array
     */
    private function getAutocompleteSource($widget): array
    {
        $url = $this->model->url('edit');
        $datasource = $widget->getDataSource();

        return [
            'url' => $url,
            'source' => $datasource['source'],
            'field' => $datasource['fieldcode'],
            'title' => $datasource['fieldtitle']
        ];
    }

    /**
     * Return grid columns configuration
     * from pages_options of columns
     *
     * @return array
     */
    private function getGridColumns(): array
    {
        $data = [
            'headers' => [],
            'columns' => [],
            'hidden' => [],
            'colwidths' => []
        ];

        foreach ($this->getDetailColumns('detail') as $col) {
            $item = $this->getItemForColumn($col);
            if ($col->hidden()) {
                $data['hidden'][] = $item;
            } else {
                $data['columns'][] = $item;
                $data['colwidths'][] = $col->htmlWidth();
                $data['headers'][] = self::$i18n->trans($col->title);
            }
        }

        return $data;
    }

    /**
     * Return grid column configuration
     *
     * @param ColumnItem $column
     *
     * @return array
     */
    private function getItemForColumn($column): array
    {
        $item = [
            'data' => $column->widget->fieldname,
            'type' => $column->widget->getType()
        ];
        switch ($item['type']) {
            case 'autocomplete':
                $item['visibleRows'] = 5;
                $item['allowInvalid'] = true;
                $item['trimDropdown'] = false;
                $item['strict'] = $column->widget->strict;
                $item['data-source'] = $this->getAutocompleteSource($column->widget);
                break;

            case 'number':
            case 'money':
                $item['type'] = 'numeric';
                $item['numericFormat'] = DivisaTools::gridMoneyFormat();
                break;

            case 'select':
                $item['editor'] = 'select';
                $item['selectOptions'] = $this->getSelectSource($column->widget);
                break;
        }

        return $item;
    }

    /**
     * Return array of values to select
     *
     * @param WidgetSelect $widget
     *
     * @return array
     */
    private function getSelectSource($widget): array
    {
        $result = [];
        if (!$widget->required) {
            $result[] = '';
        }

        foreach ($widget->values as $value) {
            $result[] = $value['title'];
        }
        return $result;
    }

    /**
     * Load data of master document and set data from array
     *
     * @param string $field
     * @param array  $data
     *
     * @return bool
     */
    private function loadDocumentDataFromArray($field, &$data): bool
    {
        if ($this->model->loadFromCode($data[$field])) {    // old data
            $this->model->loadFromData($data, ['action', 'activetab', 'code']);  // new data (the web form may be not have all the fields)
            return $this->model->test();
        }
        return false;
    }

    /**
     * 
     * @param string $documentFieldKey
     * @param int $documentFieldValue
     * @param array $data
     * @return bool
     */
    private function saveLines($documentFieldKey, $documentFieldValue, &$data)
    {
        // load old data, if exits
        $field = $this->detailModel->primaryColumn();
        $this->detailModel->loadFromCode($data[$field]);

        // set new data from user form
        $this->detailModel->loadFromData($data);

        // if new record, save field relation with master document
        if (empty($this->detailModel->primaryColumnValue())) {
            $this->detailModel->{$documentFieldKey} = $documentFieldValue;
        }

        return $this->detailModel->save();
    }
}
