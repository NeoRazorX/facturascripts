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

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\PageOption;

/**
 * Base definition for the views used in ExtendedControllers
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
abstract class BaseView
{

    /**
     * Total count of read rows.
     *
     * @var int
     */
    public $count;

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
     * Model to use in this view.
     *
     * @var ModelClass
     */
    public $model;

    /**
     * Stores the new code from the save() procedure, to use in loadData().
     *
     * @var string
     */
    public $newCode;

    /**
     * Columns and filters configuration
     *
     * @var PageOption
     */
    protected $pageOption;

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
     * Method to export the view data.
     */
    abstract public function export(&$exportManager);

    /**
     * Construct and initialize the class
     *
     * @param string $title
     * @param string $modelName
     * @param string $icon
     */
    public function __construct(string $title, string $modelName, string $icon)
    {
        static::$i18n = new Base\Translator();
        $this->count = 0;
        $this->icon = $icon;
        $this->model = class_exists($modelName) ? new $modelName() : null;
        $this->pageOption = new PageOption();
        $this->template = 'Master/BaseView.html.twig';
        $this->title = static::$i18n->trans($title);
    }

    /**
     * Clears the model and set new code for the PK.
     */
    public function clear()
    {
        $this->model->clear();
        $this->model->{$this->model->primaryColumn()} = $this->model->newCode();
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
     * Gets the column by the column name
     *
     * @param string $columnName
     *
     * @return ColumnItem
     */
    public function columnForName(string $columnName)
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
     * Returns the list of modal forms
     *
     * @return array
     */
    public function getModals()
    {
        return $this->pageOption->modals;
    }

    /**
     * If it exists, return the specified row type
     *
     * @param string $key
     *
     * @return RowItem
     */
    public function getRow(string $key)
    {
        return isset($this->pageOption->rows[$key]) ? $this->pageOption->rows[$key] : null;
    }

    /**
     * Returns the url for the requested model type
     *
     * @param string $type (edit / list / auto)
     *
     * @return string
     */
    public function getURL(string $type)
    {
        return empty($this->model) ? '' : $this->model->url($type);
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

        $this->model->checkArrayData($data);
        $this->model->loadFromData($data, ['action', 'active']);
    }
}
