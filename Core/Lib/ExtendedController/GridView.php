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

use Exception;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
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
    public $editTemplate = self::DEFAULT_TEMPLATE;

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
     *
     * @return bool
     */
    public function export(&$exportManager): bool
    {
        if (false === parent::export($exportManager)) {
            return false;
        }

        $headers = $this->gridData['headers'];
        $formattedRows = [];
        foreach ($this->gridData['rows'] as $row) {
            $formattedRow = [];
            foreach ($this->gridData['columns'] as $column) {
                $formattedRow[] = isset($row[$column['data']]) ? $row[$column['data']] : '';
            }
            $formattedRows[] = array_combine($headers, $formattedRow);
        }
        return $exportManager->addTablePage($headers, $formattedRows);
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
        if (false === \array_key_exists($key, $this->detailView->columns)) {
            if ($key == 'master') {
                return [];
            }
            $key = \array_keys($this->detailView->columns)[0];
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
        return \json_encode($this->gridData);
    }

    /**
     *
     * @return int
     */
    public function getMaxLines(): int
    {
        $numColumns = 0;
        foreach ($this->detailView->columns as $group) {
            $numColumns += \count($group->columns);
        }
        return \intval(\ini_get('max_input_vars') / $numColumns);
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
            $this->template = self::DEFAULT_TEMPLATE;
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
            foreach (\array_keys($line->getModelFields()) as $field) {
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
        foreach ($lines as $data) {
            if (false === \is_array($data)) {
                $result[] = [];
                continue;
            }

            if (!isset($data[$this->detailModel->primaryColumn()])) {
                $this->initLineData($data);
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

        if (empty($data['lines'])) {
            $data['lines'] = [];
        }

        try {
            // load master document data and test it's ok
            if (false === $this->loadDocumentDataFromArray('code', $data['document'])) {
                throw new Exception($this->toolBox()->i18n()->trans('parent-document-test-error'));
            }

            // load detail document data (old)
            $documentFieldKey = $this->model->primaryColumn();
            $documentFieldValue = $this->model->primaryColumnValue();
            $linesOld = $this->detailModel->all([new DataBaseWhere($documentFieldKey, $documentFieldValue)]);

            // start transaction
            $dataBase = new DataBase();
            $dataBase->beginTransaction();

            // delete old lines not used
            if (false === $this->deleteLinesOld($linesOld, $data['lines'])) {
                throw new Exception($this->toolBox()->i18n()->trans('error-deleting-lines'));
            }

            // Proccess detail document data (new)
            // Master Model must implement GridModelInterface
            $this->model->initTotals();
            foreach ($data['lines'] as $newLine) {
                if (false === $this->saveLines($documentFieldKey, $documentFieldValue, $newLine)) {
                    throw new Exception($this->toolBox()->i18n()->trans('error-saving-lines'));
                }
                // Master Model must implement GridModelInterface
                $this->model->accumulateAmounts($newLine);
            }

            // save master document
            if (false === $this->model->save()) {
                throw new Exception($this->toolBox()->i18n()->trans('record-save-error'));
            }

            // confirm save data into database
            $dataBase->commit();

            // URL for refresh data
            $result['url'] = $this->model->url('edit') . '&action=save-ok';
        } catch (Exception $err) {
            $result['error'] = true;
            $result['message'] = \implode("\n", \array_merge([$err->getMessage()], $this->getErrors()));
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

            if (false === $found && false === $lineOld->delete()) {
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
     *
     * @return array
     */
    private function getErrors(): array
    {
        $errors = [];
        foreach ($this->toolBox()->log()->readAll() as $log) {
            $errors[] = $log['message'];
        }

        return $errors;
    }

    /**
     * 
     * @param string $code
     *
     * @return string
     */
    private function getCellAlign($code): string
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
                $data['headers'][] = $this->toolBox()->i18n()->trans($col->title);
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
            'className' => $this->getCellAlign($column->display),
            'data' => $column->widget->fieldname,
            'readOnly' => ($column->widget->readonly == 'true' || $this->readOnly()),
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

            case 'money':
            case 'number':
            case 'percentage':
                $item['type'] = 'numeric';
                $item['numericFormat'] = $column->widget->gridFormat();
                break;

            case 'select':
                $item['type'] = 'text';
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
     * Set initial values for columns into a new line
     *
     * @param array|string $data
     */
    private function initLineData(&$data)
    {
        foreach ($this->getDetailColumns('detail') as $col) {
            if (!isset($data[$col->widget->fieldname])) {
                // TODO: maybe the widget can have a default value method instead of null
                $data[$col->widget->fieldname] = null;
            }
        }
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
        // old data
        if ($this->model->loadFromCode($data[$field])) {
            // new data (the web form may be not have all the fields)
            $this->model->loadFromData($data, ['action', 'activetab', 'code']);
            return $this->model->test();
        }

        return false;
    }

    /**
     * 
     * @return bool
     */
    private function readOnly()
    {
        return isset($this->model->editable) ? !$this->model->editable : false;
    }

    /**
     *
     * @param string $documentFieldKey
     * @param int    $documentFieldValue
     * @param array  $data
     *
     * @return bool
     */
    private function saveLines($documentFieldKey, $documentFieldValue, &$data)
    {
        // load old data, if exits
        $field = $this->detailModel->primaryColumn();
        if (empty($data[$field])) {
            $this->detailModel->clear();
        } else {
            $this->detailModel->loadFromCode($data[$field]);
        }

        // set new data from user form
        $this->detailModel->loadFromData($data);

        // if new record, save field relation with master document
        if (empty($this->detailModel->primaryColumnValue())) {
            $this->detailModel->{$documentFieldKey} = $documentFieldValue;
        }

        return $this->detailModel->save();
    }
}
