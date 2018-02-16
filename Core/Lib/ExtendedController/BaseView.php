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
use FacturaScripts\Core\Model;

/**
 * Base definition for the views used in ExtendedControllers
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class BaseView
{
    /**
     * Needed model to for the model method calls.
     * In the scope of EditController it contains the view data.
     *
     * @var mixed
     */
    protected $model;

    /**
     * Stores the new code from the save() procedure, to use in loadData().
     *
     * @var string
     */
    protected $newCode;

    /**
     * Columns and filters configuration
     *
     * @var Model\PageOption
     */
    protected $pageOption;

    /**
     * View title
     *
     * @var string
     */
    public $title;

    /**
     * Total count of read rows
     *
     * @var int
     */
    public $count;

    /**
     * Contains the translator
     *
     * @var Base\Translator
     */
    public static $i18n;

    /**
     * Construct and initialize the class
     *
     * @param string $title
     * @param string $modelName
     */
    public function __construct($title, $modelName)
    {
        static::$i18n = new Base\Translator();

        $this->count = 0;
        $this->title = static::$i18n->trans($title);
        $this->model = empty($modelName) ? null : new $modelName();
        $this->pageOption = new Model\PageOption();
    }

    /**
     * Verifies the structure and loads into the model the given data array
     *
     * @param array $data
     */
    public function loadFromData(&$data)
    {
        $fieldKey = $this->model->primaryColumn();
        $fieldValue = $data[$fieldKey];
        if ($fieldValue !== $this->model->primaryColumnValue() && $fieldValue !== '') {
            $this->model->loadFromCode($fieldValue);
        }

        $this->model->checkArrayData($data);
        $this->model->loadFromData($data, ['action', 'active']);
    }

    /**
     * Saves the model data into the database for persistence
     *
     * @return bool
     */
    public function save()
    {
        if ($this->model->save()) {
            $this->newCode = $this->model->primaryColumnValue();

            return true;
        }

        return false;
    }

    /**
     * Calculate and set new code for PK of the model
     */
    public function setNewCode()
    {
        $this->model->{$this->model->primaryColumn()} = $this->model->newCode();
    }

    /**
     * Deletes from the database the row with the given code
     *
     * @param string $code
     *
     * @return bool
     */
    public function delete($code)
    {
        if ($this->model->loadFromCode($code)) {
            return $this->model->delete();
        }

        return false;
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
     * Gets the column by the column name
     *
     * @param string $columnName
     *
     * @return ColumnItem
     */
    public function columnForName($columnName)
    {
        $result = null;
        foreach ($this->pageOption->columns as $group) {
            foreach ($group->columns as $key => $column) {
                if ($key === $columnName) {
                    $result = $column;
                    break;
                }
            }
            if (!empty($result)) {
                break;
            }
        }

        return $result;
    }

    /**
     * Gets the column by the given field name
     *
     * @param string $fieldName
     *
     * @return ColumnItem
     */
    public function columnForField($fieldName)
    {
        $result = null;
        foreach ($this->pageOption->columns as $group) {
            foreach ($group->columns as $column) {
                if ($column->widget->fieldName === $fieldName) {
                    $result = $column;
                    break;
                }
            }
            if (!empty($result)) {
                break;
            }
        }

        return $result;
    }

    /**
     * If it exists, return the specified row type
     *
     * @param string $key
     *
     * @return RowItem
     */
    public function getRow($key)
    {
        return isset($this->pageOption->rows[$key]) ? $this->pageOption->rows[$key] : null;
    }

    /**
     * Returns the list of modal forms
     *
     * @return array
     */
    public function getModals()
    {
        return $this->pageOption->modals;
    }

    /**
     * Returns the url for the requested model type
     *
     * @param string $type (edit / list / auto)
     *
     * @return string
     */
    public function getURL($type)
    {
        return empty($this->model) ? '' : $this->model->url($type);
    }

    /**
     * Returns the model identifier
     *
     * @return string
     */
    public function getModelID()
    {
        return empty($this->model) ? '' : $this->model->modelClassName();
    }

    /**
     * Returns the name.
     *
     * @return string
     */
    public function getViewName()
    {
        return $this->pageOption->name;
    }
}
