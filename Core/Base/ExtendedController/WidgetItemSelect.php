<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
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

namespace FacturaScripts\Core\Base\ExtendedController;

/**
 * Description of WidgetItemSelect
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class WidgetItemSelect extends WidgetItem
{
    /**
     * Accepted values for the field associated to the widget
     *
     * @var array
     */
    public $values;

    /**
     * Class constructor
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
     * @param \SimpleXMLElement $widgetAtributes
     */
    protected function loadFromXMLColumn($column, $widgetAtributes)
    {
        parent::loadFromXMLColumn($column, $widgetAtributes);
        $this->getAttributesGroup($this->values, $column->widget->values);
    }

    /**
     * Loads the attributes structure from the database
     *
     * @param array $column
     */
    protected function loadFromJSONColumn($column)
    {
        parent::loadFromJSONColumn($column);
        $this->values = (array) $column['widget']['values'];
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
            $item = [];
            $item['value'] = $codeModel->code;
            $item['title'] = $codeModel->description;
            $this->values[] = $item;
            unset($item);
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
    public function setValuesFromArray(&$values)
    {
        $this->values = [];
        foreach ($values as $value) {
            if (is_array($value)) {
                $this->values[] = ['title' => $value['title'], 'value' => $value['value']];
                continue;
            }

            $item = [];
            $item['value'] = $value;
            $item['title'] = $value;
            $this->values[] = $item;
            unset($item);
        }
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
     * Generates the HTML code to display and edit  the data in the Edit / EditList controller
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

        $fieldName = '"' . $this->fieldName . '"';
        $html = $this->getIconHTML()
            . '<select name=' . $fieldName . ' id=' . $fieldName
            . ' class="form-control"' . $specialAttributes . '>';

        foreach ($this->values as $selectValue) {
            $selected = ($selectValue['value'] == $value) ? ' selected="selected" ' : '';
            $html .= '<option value="' . $selectValue['value'] . '"' . $selected . '>' . $selectValue['title'] . '</option>';
        }
        $html .= '</select>';

        if (!empty($this->icon)) {
            $html .= '</div>';
        }

        return $html;
    }
}
