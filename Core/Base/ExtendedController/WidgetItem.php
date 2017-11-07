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

namespace FacturaScripts\Core\Base\ExtendedController;

/**
 * Description of WidgetItem
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
abstract class WidgetItem
{
    /**
     * Nombre del campo con los datos que visualiza el widget
     *
     * @var string
     */
    public $fieldName;

    /**
     * Tipo de widget que se visualiza
     *
     * @var string
     */
    public $type;

    /**
     * Información adicional para el usuario
     *
     * @var string
     */
    public $hint;

    /**
     * Indica que el campo es no editable
     *
     * @var boolean
     */
    public $readOnly;

    /**
     * Indica que el campo es obligatorio y debe contener un valor
     *
     * @var boolean
     */
    public $required;

    /**
     * Icono que se usa como valor o acompañante del widget
     *
     * @var string
     */
    public $icon;

    /**
     * Controlador destino al hacer click sobre los datos visualizados
     *
     * @var string
     */
    public $onClick;

    /**
     * Opciones visuales para configurar el widget
     *
     * @var array
     */
    public $options;

    /**
     * @param string $value
     */
    abstract public function getListHTML($value);

    /**
     * @param string $value
     */
    abstract public function getEditHTML($value);

    /**
     * Constructor dinámico de la clase.
     * Crea un objeto Widget del tipo informado
     *
     * @param string $type
     */
    private static function widgetItemFromType($type)
    {
        switch ($type) {
            case 'number':
                return new WidgetItemNumber();

            case 'money':
                return new WidgetItemMoney();

            case 'checkbox':
                return new WidgetItemCheckBox();

            case 'datepicker':
                return new WidgetItemDateTime();

            case 'select':
                return new WidgetItemSelect();

            case 'radio':
                return new WidgetItemRadio();

            case 'color':
                return new WidgetItemColor();

            default:
                return new WidgetItemText($type);
        }
    }

    /**
     * Crea y carga la estructura de atributos en base a un archivo XML
     *
     * @param \SimpleXMLElement $column
     * @return WidgetItem
     */
    public static function newFromXMLColumn($column)
    {
        $widgetAtributes = $column->widget->attributes();
        $type = (string) $widgetAtributes->type;
        $widget = self::widgetItemFromType($type);
        $widget->loadFromXMLColumn($column, $widgetAtributes);
        return $widget;
    }

    /**
     * Crea y carga la estructura de atributos en base a la base de datos
     *
     * @param array $column
     * @return WidgetItem
     */
    public static function newFromJSONColumn($column)
    {
        $type = (string) $column['widget']['type'];
        $widget = self::widgetItemFromType($type);
        $widget->loadFromJSONColumn($column);
        return $widget;
    }

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        $this->fieldName = '';
        $this->hint = '';
        $this->readOnly = false;
        $this->required = false;
        $this->icon = null;
        $this->onClick = '';
        $this->options = [];
    }

    /**
     * Carga el diccionario de atributos de un grupo de opciones o valores
     * del widget
     *
     * @param array            $property
     * @param \SimpleXMLElement $group
     */
    protected function getAttributesGroup(&$property, $group)
    {
        $property = [];
        foreach ($group as $item) {
            $values = [];
            foreach ($item->attributes() as $attributeKey => $attributeValue) {
                $values[$attributeKey] = (string) $attributeValue;
            }
            $values['value'] = (string) $item;
            $property[] = $values;
            unset($values);
        }
    }

    /**
     * Carga la estructura de atributos en base a un archivo XML
     *
     * @param \SimpleXMLElement $column
     * @param \SimpleXMLElement $widgetAtributes
     */
    protected function loadFromXMLColumn($column, $widgetAtributes)
    {
        $this->fieldName = (string) $widgetAtributes->fieldname;
        $this->hint = (string) $widgetAtributes->hint;
        $this->readOnly = (bool) $widgetAtributes->readonly;
        $this->required = (bool) $widgetAtributes->required;
        $this->icon = (string) $widgetAtributes->icon;
        $this->onClick = (string) $widgetAtributes->onclick;

        $this->getAttributesGroup($this->options, $column->widget->option);
    }

    /**
     * Carga la estructura de atributos en base a la base de datos
     *
     * @param array $column
     */
    protected function loadFromJSONColumn($column)
    {
        $this->fieldName = (string) $column['widget']['fieldName'];
        $this->hint = (string) $column['widget']['hint'];
        $this->readOnly = (bool) $column['widget']['readOnly'];
        $this->required = (bool) $column['widget']['required'];
        $this->icon = (string) $column['widget']['icon'];
        $this->onClick = (string) $column['widget']['onClick'];
        $this->options = (array) $column['widget']['options'];
    }

    /**
     * Indica si se cumple la condición para aplicar un Option Text
     *
     * @param string $optionValue
     * @param string $valueItem
     * @return boolean
     */
    private function canApplyOptions($optionValue, $valueItem)
    {
        switch ($optionValue[0]) {
            case '<':
                $optionValue = substr($optionValue, 1);
                $result = ((float) $valueItem < (float) $optionValue);
                break;

            case '>':
                $optionValue = substr($optionValue, 1);
                $result = ((float) $valueItem > (float) $optionValue);
                break;

            default:
                $result = ($optionValue == $valueItem);
                break;
        }
        return $result;
    }

    /**
     * Genera el código CSS para el style del widget en base a los options
     *
     * @param string $valueItem
     *
     * @return string
     */
    protected function getTextOptionsHTML($valueItem)
    {
        $html = '';
        foreach ($this->options as $option) {
            if ($this->canApplyOptions($option['value'], $valueItem)) {
                $html = ' style="';
                foreach ($option as $key => $value) {
                    if ($key != 'value') {
                        $html .= $key . ':' . $value . '; ';
                    }
                }
                $html .= '"';
                break;
            }
        }

        return $html;
    }

    /**
     * Devuelve el código HTML para la visualización de un popover
     * con el texto indicado.
     *
     * @param string $hint
     *
     * @return string
     */
    public function getHintHTML($hint)
    {
        return empty($hint) ? '' : ' data-toggle="popover" data-placement="auto" data-trigger="hover" data-content="' . $hint . '" ';
    }

    /**
     * General el código html para la visualización del icono en el lado
     * izquierda de los datos
     *
     * @return string
     */
    protected function getIconHTML()
    {
        if (empty($this->icon)) {
            return '';
        }

        $html = '<div class="input-group"><span class="input-group-addon">';
        if (strpos($this->icon, 'fa-') === 0) {
            return $html . '<i class="fa ' . $this->icon . '" aria-hidden="true"></i></span>';
        }

        return $html . '<i aria-hidden="true">' . $this->icon . '</i></span>';
    }

    /**
     * Genera el código html para atributos especiales como:
     * hint
     * sólo lectura
     * valor obligatorio
     *
     * @return string
     */
    protected function specialAttributes()
    {
        $hint = $this->getHintHTML($this->hint);
        $readOnly = empty($this->readOnly) ? '' : ' readonly';
        $required = empty($this->required) ? '' : ' required';

        return $hint . $readOnly . $required;
    }

    /**
     * Devuelve el código HTML para lista de controles no especiales
     * @param string $value
     * @param string $text
     *
     * @return string
     */
    protected function standardListHTMLWidget($value, $text = '')
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (empty($text)) {
            $text = $value;
        }

        $style = $this->getTextOptionsHTML($value);
        $html = (empty($this->onClick)) ? '<span' . $style . '>' . $text . '</span>' : '<a href="?page=' . $this->onClick . '&code=' . $value . '"' . $style . '>' . $text . '</a>';

        return $html;
    }

    /**
     * Devuelve el código HTML para edición de controles no especiales
     * @param string $value
     * @param string $specialAttributes
     * @param string $extraClass
     *
     * @return string
     */
    protected function standardEditHTMLWidget($value, $specialAttributes, $extraClass = '', $type = '')
    {
        $fieldName = '"' . $this->fieldName . '"';
        $icon = $this->getIconHTML();

        if (empty($type)) {
            $type = $this->type;
        }

        $html = $icon
            . '<input id=' . $fieldName . ' type="' . $type . '" class="form-control' . $extraClass . '"'
            . 'name=' . $fieldName . ' value="' . $value . '"' . $specialAttributes . ' />';

        if (!empty($this->icon)) {
            $html .= '</div>';
        }

        return $html;
    }
}
