<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExportManager;

/**
 * View definition for its use in ListController
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ListView extends BaseView implements DataViewInterface
{
    /**
     * Order constants
     */
    const ICON_ASC = 'fa-sort-amount-asc';
    const ICON_DESC = 'fa-sort-amount-desc';

    /**
     * Cursor with data from the model display
     *
     * @var array
     */
    private $cursor;

    /**
     * Filter configuration preset by the user
     *
     * @var ListFilter[]
     */
    private $filters;

    /**
     * List of fields where to search in when a search is made
     *
     * @var array
     */
    private $searchIn;

    /**
     * List of fields available to order by
     * Example: orderby[key] = ["label" => "Etiqueta", "icon" => ICON_ASC]
     *          key = field_asc | field_desc
     *
     * @var array
     */
    private $orderby;

    /**
     * Selected element in the Order By list
     *
     * @var string
     */
    public $selectedOrderBy;

    /**
     * Stores the offset for the cursor
     *
     * @var int
     */
    private $offset;

    /**
     * Stores the order for the cursor
     *
     * @var array
     */
    private $order;

    /**
     * Stores the where parameters for the cursor
     *
     * @var DataBaseWhere[]
     */
    private $where;

    /**
     * ListView constructor and initialization.
     *
     * @param string $title
     * @param string $modelName
     * @param string $viewName
     * @param string $userNick
     */
    public function __construct($title, $modelName, $viewName, $userNick)
    {
        parent::__construct($title, $modelName);

        $this->cursor = [];
        $this->orderby = [];
        $this->filters = [];
        $this->searchIn = [];
        $this->count = 0;
        $this->selectedOrderBy = '';

        // Carga configuración de la vista para el usuario
        $this->pageOption->getForUser($viewName, $userNick);
    }

    /**
     * Returns the link text for a given model
     *
     * @param $data
     *
     * @return string
     */
    public function getClickEvent($data)
    {
        foreach ($this->getColumns() as $col) {
            if ($col->widget->onClick !== null && $col->widget->onClick !== '') {
                return $col->widget->onClick . '?code=' . $data->{$col->widget->fieldName};
            }
        }

        return '';
    }

    /**
     * Returns the read data list in Model format
     *
     * @return array
     */
    public function getCursor()
    {
        return $this->cursor;
    }

    /**
     * Returns the list of defined filters
     *
     * @return ListFilter[]
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Returns the field list for the search, in WhereDatabase format
     *
     * @return string
     */
    public function getSearchIn()
    {
        return implode('|', $this->searchIn);
    }

    /**
     * Returns the list of defined Order By
     *
     * @return array
     */
    public function getOrderBy()
    {
        return $this->orderby;
    }

    /**
     * List of columns and its configuration
     * (Array of ColumnItem)
     *
     * @return ColumnItem[]
     */
    public function getColumns()
    {
        $keys = array_keys($this->pageOption->columns);
        if (empty($keys)) {
            return [];
        }

        $key = $keys[0];

        return $this->pageOption->columns[$key]->columns;
    }

    /**
     * Returns the indicated Order By in array format
     *
     * @param string $orderKey
     *
     * @return array
     */
    public function getSQLOrderBy($orderKey = '')
    {
        if (empty($this->orderby)) {
            return [];
        }

        if ($orderKey === '') {
            $orderKey = array_keys($this->orderby)[0];
        }

        $orderby = explode('_', $orderKey);

        return [$orderby[0] => $orderby[1]];
    }

    /**
     * Checks and establishes the selected value in the Order By
     *
     * @param string $orderKey
     */
    public function setSelectedOrderBy($orderKey)
    {
        $keys = array_keys($this->orderby);
        if (empty($orderKey) || !in_array($orderKey, $keys, false)) {
            if (empty($this->selectedOrderBy)) {
                $this->selectedOrderBy = (string) $keys[0]; // We force the first element when there is no default
            }
        } else {
            $this->selectedOrderBy = $orderKey;
        }
    }

    /**
     * Adds the given fields to the list of fields to search in
     *
     * @param array $fields
     */
    public function addSearchIn($fields)
    {
        if (is_array($fields)) {
            // TODO: Error: Perhaps array_merge/array_replace can be used instead.
            // Feel free to disable the inspection if '+' is intended.
            //$this->searchIn = array_merge($this->searchIn, $fields);
            $this->searchIn += $fields;
        }
    }

    /**
     * Adds a field to the Order By list
     *
     * @param string $field
     * @param string $label
     * @param int    $default (0 = None, 1 = ASC, 2 = DESC)
     */
    public function addOrderBy($field, $label = '', $default = 0)
    {
        $key1 = strtolower($field) . '_asc';
        $key2 = strtolower($field) . '_desc';
        if (empty($label)) {
            $label = $field;
        }

        $this->orderby[$key1] = ['icon' => self::ICON_ASC, 'label' => static::$i18n->trans($label)];
        $this->orderby[$key2] = ['icon' => self::ICON_DESC, 'label' => static::$i18n->trans($label)];

        switch ($default) {
            case 1:
                $this->setSelectedOrderBy($key1);
                break;

            case 2:
                $this->setSelectedOrderBy($key2);
                break;

            default:
                break;
        }
    }

    /**
     * Defines a new option to filter the data with
     *
     * @param string     $key
     * @param ListFilter $filter
     */
    public function addFilter($key, $filter)
    {
        if (empty($filter->options['field'])) {
            $filter->options['field'] = $key;
        }

        if (isset($filter->options['label'])) {
            $filter->options['label'] = static::$i18n->trans($filter->options['label']);
        }

        $this->filters[$key] = $filter;
    }

    /**
     * Establishes a column's display state
     *
     * @param string $columnName
     * @param bool   $disabled
     */
    public function disableColumn($columnName, $disabled)
    {
        $column = $this->columnForName($columnName);
        if (!empty($column)) {
            $column->display = $disabled ? 'none' : 'left';
        }
    }

    /**
     * Load the data in the cursor property, according to the where filter specified.
     *
     * @param mixed           $code
     * @param DataBaseWhere[] $where
     * @param array           $order
     * @param int             $offset
     * @param int             $limit
     */
    public function loadData($code = false, $where = [], $order = [], $offset = 0, $limit = FS_ITEM_LIMIT)
    {
        $this->order = empty($order) ? $this->getSQLOrderBy($this->selectedOrderBy) : $order;
        $this->count = $this->model->count($where);
        /// needed when megasearch force data reload
        $this->cursor = [];
        if ($this->count > 0) {
            $this->cursor = $this->model->all($where, $this->order, $offset, $limit);
        }

        /// store values where & offset for exportation
        $this->offset = $offset;
        $this->where = $where;
    }

    /**
     * Method to export the view data
     *
     * @param ExportManager $exportManager
     */
    public function export(&$exportManager)
    {
        if ($this->count > 0) {
            $exportManager->generateListModelPage(
                $this->model, $this->where, $this->order, $this->offset, $this->getColumns(), $this->title
            );
        }
    }
}
