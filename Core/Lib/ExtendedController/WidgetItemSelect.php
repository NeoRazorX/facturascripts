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

use FacturaScripts\Core\Model;
use FacturaScripts\Core\Base\Translator;

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

        if (!$this->required) {
            array_unshift($this->values, ['value' => '---null---', 'title' => '------']);
        }

        $html = $this->getIconHTML()
            . '<select name="' . $this->fieldName . '" class="form-control"' . $specialAttributes . '>';

        foreach ($this->values as $option) {
            /// don't use strict comparation (===)
            $selected = ($option['value'] == $value) ? ' selected="selected" ' : '';
            $title = empty($option['title']) ? $option['value'] : $option['title'];
            $html .= '<option value="' . $option['value'] . '" ' . $selected . '>' . $title . '</option>';
        }
        $html .= '</select>';

        if (!empty($this->icon)) {
            $html .= '</div>';
        }

        return $html;
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
            return '-';
        }

        $txt = $value;
        foreach ($this->values as $option) {
            /// don't use strict comparation (===)
            if ($option['value'] == $value) {
                $txt = empty($option['title']) ? $option['value'] : $option['title'];
                break;
            }
        }

        $style = $this->getTextOptionsHTML($value);

        return empty($this->onClick) ? '<span' . $style . '>' . $txt . '</span>' : '<a href="' . $this->onClick
            . '?code=' . $value . '" class="cancelClickable" ' . $style . '>' . $txt . '</a>';
    }

    /**
     *  Translate the fixed titles, if they exist
     */
    private function applyTranslations()
    {
        $i18n = new Translator();
        $count = count($this->values);
        for ($index = 0; $index < $count; $index++) {
            if (!empty($this->values[$index]['title'])) {
                $this->values[$index]['title'] = $i18n->trans($this->values[$index]['title']);
            }
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
        $this->applyTranslations();
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
        $this->applyTranslations();
    }

    /**
     * Load values from model.
     */
    public function loadValuesFromModel()
    {
        $tableName = $this->values[0]['source'];
        $fieldCode = $this->values[0]['fieldcode'];
        $fieldDesc = $this->values[0]['fieldtitle'];
        $translate = isset($this->values[0]['translate']);
        $allowEmpty = !$this->required;
        $rows = Model\CodeModel::all($tableName, $fieldCode, $fieldDesc, $allowEmpty);
        $this->setValuesFromCodeModel($rows, $translate);
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
     * Loads the value list from a given array.
     * The array must have one of the two following structures:
     * - If it's a value array, it must uses the value of each element as title and value
     * - If it's a multidimensional array, the indexes value and title must be set for each element
     *
     * @param array $values
     * @param bool $translate
     */
    public function setValuesFromArray($values, $translate = true)
    {
        $this->values = [];
        foreach ($values as $value) {
            if (is_array($value)) {
                $this->values[] = ['title' => $value['title'], 'value' => $value['value']];
                continue;
            }

            $this->values[] = [
                'value' => $value,
                'title' => '',
            ];
        }
        if ($translate) {
            $this->applyTranslations();
        }
    }

    /**
     * Loads the value list from an array with value and title (description)
     *
     * @param array $rows
     * @param bool $translate
     */
    public function setValuesFromCodeModel(&$rows, $translate = False)
    {
        $this->values = [];
        $i18n = new Translator();

        foreach ($rows as $codeModel) {
            if ($codeModel->code === null) {
                continue;
            }

            $title = $translate ? $i18n->trans($codeModel->description) : $codeModel->description;
            $this->values[] = [
                'value' => $codeModel->code,
                'title' => $title
            ];
        }
    }
}
