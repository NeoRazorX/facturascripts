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

use FacturaScripts\Core\Base\DivisaTools;

/**
 * Description of GridView
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class GridView extends EditView
{

    const GRIDVIEW_TEMPLATE = 'Master/GridView.html.twig';


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
    public $editTemplate = self::EDITVIEW_TEMPLATE;

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
        static::$assets['css'][] = FS_ROUTE . '/node_modules/handsontable/dist/handsontable.full.min.css';
        static::$assets['js'][] = FS_ROUTE . '/node_modules/handsontable/dist/handsontable.full.min.js';
        static::$assets['js'][] = FS_ROUTE . '/Dinamic/Assets/JS/GridView.js';
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
     * Returns detail column configuration
     *
     * @param string $key
     * @return GroupItem[]
     */
    public function getDetailColumns($key = '')
    {
        if (!array_key_exists($key, $this->detailView->columns)) {
            $key = array_keys($this->detailView->columns)[0];
        }

        return $this->detailView->columns[$key]->columns;
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
     * Returns JSON into string with Grid view data
     *
     * @return string
     */
    public function getGridData(): string
    {
        return json_encode($this->gridData);
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

            case 'select':
                $item['editor'] = 'select';
                $item['selectOptions'] = $this->getSelectSource($column->widget);
                break;

            case 'number':
            case 'money':
                $item['type'] = 'numeric';
                $item['numericFormat'] = DivisaTools::gridMoneyFormat();
                break;
        }

        return $item;
    }

    /**
     * Return array of values to select
     *
     * @param WidgetSelect $widget
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
     * Load detail data and set grid configuration
     *
     * @param DataBaseWhere[] $where
     * @param array           $order
     */
    public function loadGridData($where = array(), $order = array())
    {
        // load columns configuration
        $this->gridData = $this->getGridColumns();

        // load detail model data
        $this->gridData['rows'] = [];
        $this->detailView->count = $this->detailView->model->count($where);
        if ($this->detailView->count > 0) {
            foreach ($this->detailView->model->all($where, $order, 0, 0) as $line) {
                $this->gridData['rows'][] = (array) $line;
            }
        }
    }
}
