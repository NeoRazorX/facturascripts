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

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\Widget\ColumnItem;
use FacturaScripts\Core\Lib\Widget\GroupItem;
use FacturaScripts\Core\Lib\Widget\VisualItem;
use FacturaScripts\Core\Lib\Widget\VisualItemLoadEngine;
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\PageOption;
use FacturaScripts\Core\Model\User;

/**
 * Base definition for the views used in ExtendedControllers
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
abstract class BaseView
{

    /**
     *
     * @var array
     */
    protected $columns = [];

    /**
     * Total count of read rows.
     *
     * @var int
     */
    public $count = 0;

    /**
     * Cursor with data from the model display
     *
     * @var array
     */
    public $cursor = [];

    /**
     *
     * @var string
     */
    public $icon;

    /**
     * Contains the translator.
     *
     * @var Base\Translator
     */
    protected static $i18n;

    /**
     * App log manager.
     *
     * @var Base\MiniLog
     */
    protected static $miniLog;

    /**
     *
     * @var array
     */
    protected $modals = [];

    /**
     * Model to use in this view.
     *
     * @var ModelClass
     */
    public $model;

    /**
     *
     * @var string
     */
    private $name;

    /**
     * Stores the new code from the save() procedure, to use in loadData().
     *
     * @var string
     */
    public $newCode;

    /**
     * Stores the offset for the cursor
     *
     * @var int
     */
    public $offset = 0;

    /**
     *
     * @var array
     */
    public $order = [];

    /**
     * Columns configuration
     *
     * @var PageOption
     */
    protected $pageOption;

    /**
     *
     * @var array
     */
    protected $rows = [];

    /**
     *
     * @var array
     */
    public $settings;

    /**
     *
     * @var string
     */
    public $template;

    /**
     * View title
     *
     * @var string
     */
    public $title;

    /**
     * Stores the where parameters for the cursor.
     *
     * @var DataBaseWhere[]
     */
    public $where = [];

    /**
     * Method to export the view data.
     */
    abstract public function export(&$exportManager);

    /**
     * Loads view data.
     */
    abstract public function loadData($code = false, $where = [], $order = [], $offset = 0, $limit = FS_ITEM_LIMIT);

    /**
     * Process form data.
     */
    abstract public function processFormData($request, $case);

    /**
     * Construct and initialize the class
     *
     * @param string $name
     * @param string $title
     * @param string $modelName
     * @param string $icon
     */
    public function __construct($name, $title, $modelName, $icon)
    {
        if (!isset(static::$i18n)) {
            static::$i18n = new Base\Translator();
            static::$miniLog = new Base\MiniLog();
        }

        if (class_exists($modelName)) {
            $this->model = new $modelName();
        } else {
            static::$miniLog->critical(static::$i18n->trans('model-not-found', ['%model%' => $modelName]));
        }

        $this->icon = $icon;
        $this->name = $name;
        $this->pageOption = new PageOption();
        $this->settings = [
            'active' => true,
            'btnDelete' => true,
            'btnNew' => true,
            'btnPrint' => false,
            'btnSave' => true,
            'btnUndo' => true,
        ];
        $this->template = 'Master/BaseView.html.twig';
        $this->title = static::$i18n->trans($title);
        $this->assets();
    }

    /**
     * Gets the modal column by the column name
     *
     * @param string $columnName
     *
     * @return ColumnItem
     */
    public function columnModalForName(string $columnName)
    {
        return $this->getColumnForName($columnName, $this->modals);
    }

    /**
     * Gets the column by the column name
     *
     * @param string $columnName
     *
     * @return ColumnItem
     */
    public function columnForName(string $columnName)
    {
        return $this->getColumnForName($columnName, $this->columns);
    }

    /**
     * Gets the column by the given field name
     *
     * @param string $fieldName
     *
     * @return ColumnItem
     */
    public function columnForField(string $fieldName)
    {
        foreach ($this->columns as $group) {
            foreach ($group->columns as $column) {
                if ($column->widget->fieldname === $fieldName) {
                    return $column;
                }
            }
        }

        return null;
    }

    /**
     * Establishes the column's edit state
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
     * Gets the column by the column name from source group
     *
     * @param string $columnName
     * @param array  $source
     *
     * @return ColumnItem
     */
    public function getColumnForName(string $columnName, &$source)
    {
        foreach ($source as $group) {
            foreach ($group->columns as $key => $column) {
                if ($key === $columnName) {
                    return $column;
                }
            }
        }

        return null;
    }

    /**
     * Returns the column configuration
     *
     * @return GroupItem[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Returns the modal configuration
     *
     * @return GroupItem[]
     */
    public function getModals()
    {
        return $this->modals;
    }

    /**
     *
     * @return array
     */
    public function getPagination()
    {
        $pages = [];
        $key1 = $key2 = 0;
        $current = 1;

        /// add all pages
        while ($key2 < $this->count) {
            $pages[$key1] = [
                'active' => ($key2 == $this->offset),
                'num' => $key1 + 1,
                'offset' => $key1 * FS_ITEM_LIMIT,
            ];
            if ($key2 == $this->offset) {
                $current = $key1;
            }
            $key1++;
            $key2 += FS_ITEM_LIMIT;
        }

        /// now descarting pages
        foreach (array_keys($pages) as $key2) {
            $middle = intval($key1 / 2);

            /**
             * We discard everything except the first page, the last one, the middle one,
             * the current one, the 5 previous and 5 following ones.
             */
            if (($key2 > 1 && $key2 < $current - 5 && $key2 != $middle) || ( $key2 > $current + 5 && $key2 < $key1 - 1 && $key2 != $middle)) {
                unset($pages[$key2]);
            }
        }

        return (count($pages) > 1) ? $pages : [];
    }

    /**
     * If it exists, return the specified row type
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getRow(string $key)
    {
        return isset($this->rows[$key]) ? $this->rows[$key] : null;
    }

    /**
     * Returns the name.
     *
     * @return string
     */
    public function getViewName()
    {
        return $this->name;
    }

    /**
     * Verifies the structure and loads into the model the given data array
     *
     * @param array $data
     */
    public function loadFromData(array &$data)
    {
        $fieldKey = $this->model->primaryColumn();
        $fieldValue = $data[$fieldKey];
        if ($fieldValue !== $this->model->primaryColumnValue() && $fieldValue !== '') {
            $this->model->loadFromCode($fieldValue);
        }

        $this->model->loadFromData($data, ['action', 'activetab']);
    }

    /**
     *
     * @param User|false $user
     */
    public function loadPageOptions($user = false)
    {
        if (!is_bool($user)) {
            /// sets user security level for use in render
            VisualItem::setLevel($user->level);
        }

        $orderby = ['nick' => 'ASC'];
        $where = $this->getPageWhere($user);
        if (!$this->pageOption->loadFromCode('', $where, $orderby)) {
            $viewName = explode('-', $this->name)[0];
            VisualItemLoadEngine::installXML($viewName, $this->pageOption);
        }

        VisualItemLoadEngine::loadArray($this->columns, $this->modals, $this->rows, $this->pageOption);
    }

    /**
     * Adds assets to the asset manager.
     */
    protected function assets()
    {
        ;
    }

    /**
     * Returns DataBaseWhere[] for locate a pageOption model.
     *
     * @param User|false $user
     */
    protected function getPageWhere($user = false)
    {
        $viewName = explode('-', $this->name)[0];

        if (is_bool($user)) {
            return [new DataBaseWhere('name', $viewName)];
        }

        return [
            new DataBaseWhere('name', $viewName),
            new DataBaseWhere('nick', $user->nick),
            new DataBaseWhere('nick', 'NULL', 'IS', 'OR'),
        ];
    }
}
