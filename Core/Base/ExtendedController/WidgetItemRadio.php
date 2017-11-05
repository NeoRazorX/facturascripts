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
 * Description of WidgetItemDateTime
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class WidgetItemRadio extends WidgetItem
{
    /**
     * Valores aceptados por el campo asociado al widget
     *
     * @var array
     */
    public $values;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        parent::__construct();

        $this->type = 'radio';
        $this->values = [];
    }

    /**
     * Carga la estructura de atributos en base a un archivo XML
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
     * Carga la estructura de atributos en base a la base de datos
     *
     * @param array $column
     */
    protected function loadFromJSONColumn($column)
    {
        parent::loadFromJSONColumn($column);
        $this->values = (array) $column['widget']['values'];
    }

    /**
     * Genera el código html para la visualización de los datos en el
     * controlador List
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
     * Genera el código html para la visualización y edición de los datos
     * en el controlador Edit / EditList
     *
     * @param string $value
     *
     * @return string
     */
    public function getEditHTML($value)
    {
        $specialAttributes = $this->specialAttributes();
        $fieldName = '"' . $this->fieldName . '"';
        $html = $this->getIconHTML()
            . '<input name=' . $fieldName . ' id=' . $fieldName
            . 'sufix% class="form-check-input" type="radio"'
            . ' value=""value%"' . $specialAttributes . '"checked%>';

        if (!empty($this->icon)) {
            $html .= '</div>';
        }

        return $html;
    }
}
