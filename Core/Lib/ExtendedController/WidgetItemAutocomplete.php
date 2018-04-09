<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  carlos@facturascripts.com
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
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class WidgetItemAutocomplete extends WidgetItem
{

    /**
     * Model to use with select and autocomplete filters.
     *
     * @var Model\CodeModel
     */
    private $codeModel;

    /**
     * Accepted values for the field associated to the widget.
     * Values are loaded from Model\PageOption::getForUser()
     *
     * @var array
     */
    public $values;

    /**
     * WidgetItemAutocomplete constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->codeModel = new Model\CodeModel();
        $this->type = 'autocomplete';
        $this->values = [];
    }

    /**
     * Generates the HTML code to display and edit the data in the Edit / EditList controller.
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

        $html = '<input type="hidden" id="' . $this->fieldName . 'Autocomplete" name="'
            . $this->fieldName . '" value="' . $value . '"/>'
            . '<div class="input-group">';

        if (empty($value) || $this->required) {
            $html .= '<span class="input-group-prepend"><span class="input-group-text">'
                . '<i class="fa fa-keyboard-o" aria-hidden="true"></i></span></span>';
        } else {
            $html .= '<span class="input-group-prepend">'
                . '<button type="button" class="btn btn-warning" onclick="$(\'#' . $this->fieldName . 'Autocomplete, #' . $this->fieldName . 'Autocomplete2\').val(\'\');">'
                . '<i class="fa fa-remove" aria-hidden="true"></i>'
                . '</button>'
                . '</span>';
        }

        $html .= '<input type="text" id="' . $this->fieldName . 'Autocomplete2" value="' . $this->getTextValue($value) . '" class="form-control autocomplete"'
            . ' data-source="' . $this->values[0]['source'] . '" data-field="' . $this->values[0]['fieldcode'] . '"'
            . ' data-title="' . $this->values[0]['fieldtitle'] . '" ' . $specialAttributes . ' />'
            . '</div>';

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
            return '';
        }

        return '<span>' . $value . '</span>';
    }

    /**
     * Get the text for the given value
     *
     * @param string $value
     *
     * @return string
     */
    public function getTextValue($value)
    {
        $tableName = $this->values[0]['source'];
        $fieldCode = $this->values[0]['fieldcode'];
        $fieldDesc = $this->values[0]['fieldtitle'];

        return $this->codeModel->getDescription($tableName, $fieldCode, $value, $fieldDesc);
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
     * Loads the attributes structure from a XML file
     *
     * @param \SimpleXMLElement $column
     */
    public function loadFromXML($column)
    {
        parent::loadFromXML($column);
        $this->getAttributesGroup($this->values, $column->widget->values);
    }
}
