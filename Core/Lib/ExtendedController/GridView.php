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

use Exception;
use FacturaScripts\Core\Base;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Lib\ExportManager;
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Dinamic\Lib\Widget;
use Symfony\Component\HttpFoundation\Request;

/**
 * View definition for its use in ExtendedControllers
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
     * GridView constructor and initialization.
     *
     * @param BaseView $parent
     * @param string   $name
     * @param string   $title
     * @param string   $modelName
     * @param string   $icon
     */
    public function __construct(&$parent, $name, $title, $modelName, $icon)
    {
        parent::__construct($name, $title, $modelName, $icon);
        $this->template = 'Master/GridView.html.twig';

        // Join the parent view
        $this->parentView = $parent;
        $this->parentModel = $parent->model;
    }

    /**
     * Method to export the view data.
     *
     * @param ExportManager $exportManager
     */
    public function export(&$exportManager)
    {
        /// TODO: complete this method
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
     * @param Widget\WidgetAutocomplete $widget
     *
     * @return array
     */
    private function getAutocompleteSource($widget): array
    {
        $url = $this->parentModel->url('edit'); // Calculate url for grid controller
        $datasource = $widget->getDataSource();

        return [
            'url' => $url,
            'source' => $datasource['source'],
            'field' => $datasource['fieldcode'],
            'title' => $datasource['fieldtitle']
        ];
    }

    /**
     * Return array of values to select
     *
     * @param Widget\WidgetSelect $widget
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
     * Return grid column configuration
     *
     * @param Widget\ColumnItem $column
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

            case 'select':
                $item['editor'] = 'select';
                $item['selectOptions'] = $this->getSelectSource($column->widget);
                break;

            case 'number':
            case 'money':
                $item['type'] = 'numeric';
                $item['numericFormat'] = Base\DivisaTools::gridMoneyFormat();
                break;
        }

        return $item;
    }

    /**
     * Return grid columns configuration
     *
     * @return array
     */
    private function getGridColumns(): array
    {
        $data = [
            'headers' => [],
            'columns' => [],
            'hidden' => []
        ];

        $columns = $this->pageOption->columns['main']->columns;
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
     * Load the data in the model property, according to the code specified.
     *
     * @param string          $code
     * @param DataBaseWhere[] $where
     * @param array           $order
     * @param int             $offset
     * @param int             $limit
     */
    public function loadData($code = '', $where = [], $order = [], $offset = 0, $limit = FS_ITEM_LIMIT)
    {
        // load columns configuration
        $this->gridData = $this->getGridColumns();

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
     * @param string $fieldPK
     * @param array  $data
     *
     * @return bool
     */
    private function loadDocumentDataFromArray($fieldPK, &$data): bool
    {
        if ($this->parentModel->loadFromCode($data[$fieldPK])) {    // old data
            $this->parentModel->loadFromData($data, ['action', 'active']);  // new data (the web form may not have all the fields)
            return $this->parentModel->test();
        }
        return false;
    }

    /**
     * Removes from the database the non-existent detail
     *
     * @param array $linesOld
     * @param array $linesNew
     *
     * @return bool
     */
    private function deleteLinesOld(&$linesOld, &$linesNew): bool
    {
        if (!empty($linesOld)) {
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
        }
        return true;
    }

    public function saveData($data): array
    {
        $result = [
            'error' => false,
            'message' => '',
            'url' => ''
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

            // URL for refresh data
            $result['url'] = $this->parentModel->url('edit') . '&action=save-ok';
        } catch (Exception $e) {
            $result['error'] = true;
            $result['message'] = $e->getMessage();
        } finally {
            if ($dataBase->inTransaction()) {
                $dataBase->rollback();
            }
            return $result;
        }
    }

    public function processFormLines(&$lines): array
    {
        $result = [];
        $primaryKey = $this->model->primaryColumn();
        foreach ($lines as $data) {
            if (!isset($data[$primaryKey])) {
                foreach ($this->pageOption->columns as $group) {
                    foreach ($group->columns as $col) {
                        if (!isset($data[$col->widget->fieldname])) {
                            $data[$col->widget->fieldname] = null;   // TODO: maybe the widget can have a default value method instead of null
                        }
                    }
                }
            }
            $result[] = $data;
        }

        return $result;
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
