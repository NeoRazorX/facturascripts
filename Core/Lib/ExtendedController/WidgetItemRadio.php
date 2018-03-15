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

/**
 * This class manage all specific method for a WidgetItem of Date Time type.
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class WidgetItemRadio extends WidgetItem
{

    /**
     * Accepted values for the field associated to the widget
     *
     * @var array
     */
    public $values;

    /**
     * WidgetItemRadio constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->type = 'radio';
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
     * Generates the HTML code to display the data in the List controller
     *
     * @param string $value
     *
     * @return string
     */
    public function getListHTML($value)
    {
        return $this->standardListHTMLWidget($value);
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
        $html = $this->getIconHTML()
            . '<input name="' . $this->fieldName . '" id="' . $this->fieldName
            . '"sufix% class="form-check-input" type="radio"'
            . ' value=""value%"' . $specialAttributes . '"checked%>';

        if (!empty($this->icon)) {
            $html .= '</div>';
        }

        return $html;
    }
}
