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
    const ICON_ASC = 'fa-sort-amount-up';
    const ICON_DESC = 'fa-sort-amount-down';

    /**
     * Cursor with data from the model display
     *
     * @var array
     */
    private $cursor;

    /**
     *
     * @var DivisaTools
     */
    public $divisaTools;

    /**
     * Filter configuration preset by the user
     *
     * @var BaseFilter[]
     */
    private $filters;

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
     * List of fields available to order by
     * Example: orderby[key] = ["label" => "Etiqueta", "icon" => ICON_ASC]
     *          key = field_asc | field_desc
     *
     * @var array
     */
    private $orderby;

    /**
     * List of fields where to search in when a search is made
     *
     * @var array
     */
    private $searchIn;

    /**
     * Selected element in the Order By list
     *
     * @var string
     */
    public $selectedOrderBy;

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
        $this->divisaTools = new DivisaTools();
        $this->filters = [];
        $this->orderby = [];
        $this->selectedOrderBy = '';
        $this->searchIn = [];

        // Carga configuración de la vista para el usuario
        $this->pageOption->getForUser($viewName, $userNick);
    }

    /**
     * Defines a new option to filter the data with
     *
     * @param string     $key
     * @param BaseFilter $filter
     */
    public function addFilter(string $key, BaseFilter $filter)
    {
        $this->filters[$key] = $filter;
    }

    /**
     * Adds a field to the Order By list
     *
     * @param array  $fields
     * @param string $label
     * @param int    $default (0 = None, 1 = ASC, 2 = DESC)
     */
    public function addOrderBy($fields, $label, $default = 0)
    {
        $key1 = strtolower(implode('|', $fields)) . '_asc';
        $key2 = strtolower(implode('|', $fields)) . '_desc';

        $this->orderby[$key1] = ['icon' => self::ICON_ASC, 'fields' => $fields, 'label' => static::$i18n->trans($label)];
        $this->orderby[$key2] = ['icon' => self::ICON_DESC, 'fields' => $fields, 'label' => static::$i18n->trans($label)];

        switch ($default) {
            case 1:
                $this->setSelectedOrderBy($key1);
                break;

            case 2:
                $this->setSelectedOrderBy($key2);
                break;
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
     * Establishes a column's display state
     *
     * @param string $columnName
     * @param bool   $disabled
     */
    public function disableColumn($columnName, $disabled = true)
    {
        $column = $this->columnForName($columnName);
        if (!empty($column)) {
            $column->display = $disabled ? 'none' : 'left';
        }
    }

    /**
     * Method to export the view data.
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
     * @return BaseFilter[]
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Returns the filter identificate by key
     *
     * @param string $key
     * @return BaseFilter
     */
    public function getFilter($key)
    {
        return $this->filters[$key];
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
     * Returns the field list for the search, in WhereDatabase format
     *
     * @return string
     */
    public function getSearchIn()
    {
        return implode('|', $this->searchIn);
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
        $result = [];
        if (!empty($this->orderby)) {
            if ($orderKey === '') {
                $orderKey = array_keys($this->orderby)[0];
            }

            $direction = (substr($orderKey, -5) == '_desc') ? 'DESC' : 'ASC';
            foreach ($this->orderby[$orderKey]['fields'] as $field) {
                $result[$field] = $direction;
            }
        }
        return $result;
    }

    public function getURL(string $type)
    {
        if (empty($this->where)) {
            return parent::getURL($type);
        }

        $extra = '';
        foreach (DataBaseWhere::getFieldsFilter($this->where) as $field => $value) {
            $extra .= ('' === $extra) ? '?' : '&';
            $extra .= $field . '=' . $value;
        }

        switch ($type) {
            /// removed list case to fix problem with listcontroller pagination
            case 'new':
                $extra .= ('' === $extra) ? '?action=insert' : '&action=insert';
                return parent::getURL($type) . $extra;

            default:
                return parent::getURL($type);
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
        $this->count = is_null($this->model) ? 0 : $this->model->count($where);
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
}
