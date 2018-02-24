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
 * View definition for its use in ExtendedControllers
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class EditListView extends BaseView implements DataViewInterface
{

    /**
     * Cursor with the display model's data
     *
     * @var array
     */
    private $cursor;

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
     * Store the parameters for the cursor's WHERE clause
     *
     * @var DataBaseWhere[]
     */
    private $where;

    /**
     * Class constructor and initialization
     *
     * @param string $title
     * @param string $modelName
     * @param string $viewName
     * @param string $userNick
     */
    public function __construct($title, $modelName, $viewName, $userNick)
    {
        parent::__construct($title, $modelName);

        $this->order = [$this->model->primaryColumn() => 'ASC'];
        $this->offset = 0;
        $this->where = [];

        // Load the view configuration for the user
        $this->pageOption->getForUser($viewName, $userNick);
    }

    /**
     * Returns the list of read data in the Model format
     *
     * @return array
     */
    public function getCursor()
    {
        return $this->cursor;
    }

    /**
     * Column list and its configuration
     * (Array of ColumnItem)
     *
     * @return GroupItem[]
     */
    public function getColumns()
    {
        return $this->pageOption->columns;
    }

    /**
     * Returns True if have less than 5 columns, else returns False.
     */
    public function isBasicEditList()
    {
        if (count($this->pageOption->columns) !== 1) {
            return false;
        }

        $maxColumns = 5;
        $group = reset($this->pageOption->columns);
        foreach ($group->columns as $col) {
            if ($col->display !== 'none') {
                --$maxColumns;
            }
        }

        return $maxColumns > 0;
    }

    /**
     * Establishes the column's edit state
     *
     * @param string $columnName
     * @param bool   $disabled
     */
    public function disableColumn($columnName, $disabled)
    {
        $column = $this->columnForName($columnName);
        if (!empty($column)) {
            $column->widget->readOnly = $disabled;
        }
    }

    /**
     * Load the data in the cursor property, according to the where filter specified.
     * Adds an empty row/model at the end of the loaded data.
     *
     * @param mixed           $code
     * @param DataBaseWhere[] $where
     * @param array           $order
     * @param int             $offset
     * @param int             $limit
     */
    public function loadData($code = false, $where = [], $order = [], $offset = 0, $limit = FS_ITEM_LIMIT)
    {
        $this->order = empty($order) ? $this->order : $order;
        $this->count = $this->model->count($where);
        if ($this->count > 0) {
            $this->cursor = $this->model->all($where, $this->order, $offset, $limit);
        }

        // We save the values where and offset for the export
        $this->offset = $offset;
        $this->where = $where;
    }

    /**
     * Prepares the fields for an empty model
     *
     * @return mixed
     */
    public function newEmptyModel()
    {
        $class = $this->model->modelName();
        $result = new $class();

        foreach (DataBaseWhere::getFieldsFilter($this->where) as $field => $value) {
            $result->{$field} = $value;
        }

        return $result;
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
