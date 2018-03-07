<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Base\DataBase;

/**
 * Description of GridView
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class GridView extends BaseView
{
    /**
     * Parent container of grid data
     *
     * @var BaseView
     */
    private $parentView;

    /**
     * Model of parent data
     *
     * @var ModelClass
     */
    private $parentModel;

    /**
     * Grid data configuration and data
     *
     * @var array
     */
    private $gridData;

    /**
     * EditView constructor and initialization.
     *
     * @param BaseView $parent
     * @param string $title
     * @param string $modelName
     * @param string $viewName
     * @param string $userNick
     */
    public function __construct(&$parent, $title, $modelName, $viewName, $userNick)
    {
        parent::__construct($title, $modelName);

        // Join the parent view
        $this->parentView = $parent;
        $this->parentModel = $parent->getModel();

        // Loads the view configuration for the user
        $this->pageOption->getForUser($viewName, $userNick);
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
     * Configure autocomplete column with data to Grid component
     *
     * @param array $values
     * @return array
     */
    private function getAutocompleteSource($values): array
    {
        // Calculate url for grid controller
        $url = $this->parentModel->url('edit');

        return [
            'url' => $url,
            'source' => $values['source'],
            'field' => $values['fieldcode'],
            'title' => $values['fieldtitle']
        ];
    }

    /**
     * Determines whether the user's selection should be strictly
     * a value from the list of values
     *
     * @param array $values
     * @return bool
     */
    private function getAutocompeteStrict($values): bool
    {
        return isset($values['strict']) ? $values['strict'] === 'true' : true;
    }

    /**
     * Return grid column configuration
     *
     * @param ColumnItem $column
     * @return array
     */
    private function getItemForColumn($column): array
    {
        $item = ['data' => $column->widget->fieldName];
        switch ($column->widget->type) {
            case 'autocomplete':
                $item['type'] = 'autocomplete';
                $item['visibleRows'] = 5;
                $item['allowInvalid'] = true;
                $item['trimDropdown'] = false;
                $item['strict'] = $this->getAutocompeteStrict($column->widget->values[0]);
                $item['data-source'] = $this->getAutocompleteSource($column->widget->values[0]);
                break;

            case 'number':
            case 'money':
                $item['type'] = 'numeric';
                $item['numericFormat'] = Base\DivisaTools::gridMoneyFormat();
                break;

            default:
                $item['type'] = $column->widget->type;
                break;
        }

        return $item;
    }

    /**
     * Return grid columns configuration
     *
     * @return array
     */
    private function getColumns(): array
    {
        $data = [
            'headers' => [],
            'columns' => [],
            'hidden' => []
        ];

        $columns = $this->pageOption->columns['root']->columns;
        foreach ($columns as $col) {
            $item = $this->getItemForColumn($col);
            switch ($col->display) {
                case 'none':
                    $data['hidden'][] = $item;
                    break;

                default:
                    $data['headers'][] = self::$i18n->trans($col->title);
                    $data['columns'][] = $item;
                    break;
            }
        }

        return $data;
    }

    /**
     * Load the data in the cursor property, according to the where filter specified.
     * Adds an empty row/model at the end of the loaded data.
     *
     * @param DataBaseWhere[] $where
     * @param array           $order
     */
    public function loadData($where = [], $order = [])
    {
        // load columns configuration
        $this->gridData = $this->getColumns();

        // load model data
        $this->gridData['rows'] = [];
        $count = $this->model->count($where);
        if ($count > 0) {
            foreach ($this->model->all($where, $order, 0, 0) as $line) {
                $this->gridData['rows'][] = (array) $line;
            }
        }
    }

    /**
     * Load data of master document and set data from array
     *
     * @param ModelClass $model
     * @param array $data
     * @return bool
     */
    private function loadDocumentDataFromArray($fieldPK, &$data): bool
    {
        if ($this->parentModel->loadFromCode($data[$fieldPK])) {    // old data
            $this->parentModel->loadFromData($data);                // new data (the web form may not have all the fields)
            return $this->parentModel->test();
        }
        return false;
    }

    /**
     * Removes from the database the non-existent detail
     *
     * @param ModelClass $model
     * @param array $linesOld
     * @param array $linesNew
     * @return bool
     */
    private function deleteLinesOld(&$linesOld, &$linesNew): bool
    {
        $fieldPK = $this->model->primaryColumn();
        $oldIDs = array_column($linesOld, $fieldPK);
        $newIDs = array_column($linesNew, $fieldPK);
        $deletedIDs = array_diff($oldIDs, $newIDs);

        foreach ($deletedIDs as $idKey) {
            $this->model->{$fieldPK} = $idKey;
            if (!$this->model->delete()) {
                return false;
            }
        }
        return true;
    }

    public function saveData($data):array
    {
        $result = [
            'error' => false,
            'message' => ''
        ];

        try {
            // load master document data and test it's ok
            $parentPK = $this->parentModel->primaryColumn();
            if (!$this->loadDocumentDataFromArray($parentPK, $data['document'])) {
                throw new Exception(self::$i18n->trans('parent-document-test-error'));
            }

            // load detail document data (old)
            $parentValue = $this->parentModel->primaryColumnValue();
            $linesOld = $this->model->all([new DataBase\DataBaseWhere($parentPK, $parentValue)]);

            // start transaction
            $dataBase = new DataBase();
            $dataBase->beginTransaction();

            // delete old lines not used
            if (!$this->deleteLinesOld($linesOld, $data['lines'])) {
                throw new Exception(self::$i18n->trans('lines-delete-error'));
            }

            // Proccess detail document data (new)
            $this->parentModel->initTotals();
            foreach ($data['lines'] as $newLine) {
                $this->model->loadFromData($newLine);
                if (empty($this->model->primaryColumnValue())) {
                    $this->model->{$parentPK} = $parentValue;
                }
                if (!$this->model->save()) {
                    throw new Exception(self::$i18n->trans('lines-save-error'));
                }
                $this->parentModel->accumulateAmounts($newLine);
            }

            // save master document
            if (!$this->parentModel->save()) {
                throw new Exception(self::$i18n->trans('parent-document-save-error'));
            }

            // confirm save data into database
            $dataBase->commit();
        }
        catch (Exception $e) {
            $result['error'] = true;
            $result['message'] = $e->getMessage();
        }
        finally {
            if ($dataBase->inTransaction()) {
                $dataBase->rollback();
            }
            return $result;
        }
    }
}
