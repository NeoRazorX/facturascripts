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

use FacturaScripts\Core\Model;

/**
 * This class manage all specific method for a WidgetItem of Select type.
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class WidgetItemSelect extends WidgetItem
{

    /**
     * Accepted values for the field associated to the widget.
     * Values are loaded from Model\PageOption::getForUser()
     *
     * @var array
     */
    public $values;

    /**
     * WidgetItemSelect constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->type = 'select';
        $this->values = [];
    }

    /**
     * Loads the attributes structure from a XML file
     *
     * @param \SimpleXMLElement $column
     */
    public function loadFromXML($column)
    {
        parent::loadFromXML($column);
        $this->getAttributesGroup($this->values, $column->widget->values);
        if (!$this->required) {
            array_unshift($this->values, ['value' => '---null---', 'title' => '------']);
        }
    }

    /**
     * Loads the attributes structure from a JSON file
     *
     * @param array $widget
     */
    public function loadFromJSON($widget)
    {
        parent::loadFromJSON($widget);
        $this->values = (array) $widget['values'];
    }

    /**
     * Loads the value list from an array with value and title (description)
     *
     * @param array $rows
     */
    public function setValuesFromCodeModel(&$rows)
    {
        $this->values = [];
        foreach ($rows as $codeModel) {
            if ($codeModel->code === null) {
                $codeModel->code = '---null---';
                $codeModel->description = '------';
            }

            $this->values[] = [
                'value' => $codeModel->code,
                'title' => $codeModel->description,
            ];
        }
    }

    /**
     * Loads the value list from a given array.
     * The array must have one of the two following structures:
     * - If it's a value array, it must uses the value of each element as title and value
     * - If it's a multidimensional array, the indexes value and title must be set for each element
     *
     * @param array $values
     */
    public function setValuesFromArray($values)
    {
        $this->values = [];
        foreach ($values as $value) {
            if (is_array($value)) {
                $this->values[] = ['title' => $value['title'], 'value' => $value['value']];
                continue;
            }

            $this->values[] = [
                'value' => $value,
                'title' => $value,
            ];
        }
    }

    /**
     * Load values from model.
     */
    public function loadValuesFromModel()
    {
        $tableName = $this->values[0]['source'];
        $fieldCode = $this->values[0]['fieldcode'];
        $fieldDesc = $this->values[0]['fieldtitle'];
        $allowEmpty = !$this->required;
        $rows = Model\CodeModel::all($tableName, $fieldCode, $fieldDesc, $allowEmpty);
        $this->setValuesFromCodeModel($rows);
        unset($rows);
    }

    /**
     * Load values from array.
     */
    public function loadValuesFromRange()
    {
        $start = $this->values[0]['start'];
        $end = $this->values[0]['end'];
        $step = $this->values[0]['step'];
        $values = range($start, $end, $step);
        $this->setValuesFromArray($values);
    }

    /**
     * Generates the HTML code to display the data in the List controller
     *
     * @param string $value
     *
     * @return string
     */
    public function getListHTML($value)
    {
        if ($value === null || $value === '') {
            return '';
        }

        return '<span>' . $value . '</span>';
    }

    /**
     * Generates the HTML code to display and edit  the data in the Edit / EditList controller.
     * Values are loaded from Model\PageOption::getForUser()
     *
     * @param string $value
     *
     * @return string
     */
    public function getEditHTML($value)
    {
        $specialAttributes = $this->specialAttributes();

        if ($this->readOnly) {
            return $this->standardEditHTMLWidget($value, $specialAttributes, '', 'text');
        }

        $html = $this->getIconHTML()
            . '<select name="' . $this->fieldName . '" id="' . $this->fieldName
            . '" class="form-control"' . $specialAttributes . '>';

        foreach ($this->values as $selectValue) {
            /// don't use strict comparation (===)
            $selected = ($selectValue['value'] == $value) ? ' selected="selected" ' : '';
            $html .= '<option value="' . $selectValue['value'] . '" ' . $selected . '>' . $selectValue['title']
                . '</option>';
        }
        $html .= '</select>';

        if (!empty($this->icon)) {
            $html .= '</div>';
        }

        return $html;
    }
}
