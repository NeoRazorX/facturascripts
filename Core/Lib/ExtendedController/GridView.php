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

/**
 * Description of GridView
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class GridView extends BaseView
{
    private $parentView;
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

        // Loads the view configuration for the user
        $this->pageOption->getForUser($viewName, $userNick);
    }

    /**
     * Returns the pointer to the data model
     *
     * @return mixed
     */
    public function getModel()
    {
        return $this->model;
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
        $parentModel = $this->parentView->getModel();
        $url = $parentModel->url('edit');

        return [
            'url' => $url,
            'source' => $values['source'],
            'field' => $values['fieldcode'],
            'title' => $values['fieldtitle']
        ];
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
                $item['strict'] = true;
                $item['allowInvalid'] = false;
                $item['visibleRows'] = 5;
                $item['trimDropdown'] = false;
                $item['data-source'] = $this->getAutocompleteSource($column->widget->values[0]);
                break;

            case 'number':
            case 'money':
                $item['type'] = 'numeric';
                $item['format'] = Base\DivisaTools::gridMoneyFormat();
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
            'hidden'  => []
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
}
