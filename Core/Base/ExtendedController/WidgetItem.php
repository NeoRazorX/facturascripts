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

use FacturaScripts\Core\Base\DivisaTools;
use FacturaScripts\Core\Base\NumberTools;

/**
 * Description of WidgetItem
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class WidgetItem
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
     * Numero de decimales para tipos numéricos
     * 
     * @var int 
     */
    public $decimal;

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
     * Valores aceptados por el campo asociado al widget
     *
     * @var array
     */
    public $values;
    
    /**
     *
     * @var DivisaTools
     */
    private static $divisaTools;
    
    /**
     *
     * @var NumberTools
     */
    private static $numberTools;

    /**
     * Constructor de la clase. Si se informa un array se cargan los datos
     * informados en el nuevo objeto
     */
    public function __construct()
    {
        if(!isset(self::$divisaTools)) {
            self::$divisaTools = new DivisaTools();
            self::$numberTools = new NumberTools();
        }
        
        $this->type = 'text';
        $this->decimal = 0;
        $this->fieldName = '';
        $this->hint = '';
        $this->readOnly = FALSE;
        $this->required = FALSE;
        $this->icon = null;
        $this->onClick = '';
        $this->options = [];
        $this->values = [];
    }

    /**
     * Carga la lista de valores según un array con codigo y descripción
     *
     * @param array $rows
     */
    public function setValuesFromCodeModel(&$rows)
    {
        $this->values = [];
        foreach ($rows as $codeModel) {
            $values = [];
            $values['value'] = $codeModel->code;
            $values['title'] = $codeModel->description;
            $this->values[] = $values;
            unset($values);
        }
    }

    /**
     * Carga el diccionario de atributos de un grupo de opciones o valores
     * del widget
     *
     * @param array            $property
     * @param SimpleXMLElement $group
     */
    private function getAttributesGroup(&$property, $group)
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
     * @param SimpleXMLElement $column
     */
    public function loadFromXMLColumn($column)
    {
        $widget_atributes = $column->widget->attributes();
        $this->fieldName = (string) $widget_atributes->fieldname;
        $this->type = (string) $widget_atributes->type;
        $this->decimal = (int) intval($widget_atributes->decimal);
        $this->hint = (string) $widget_atributes->hint;
        $this->readOnly = (bool) boolval($widget_atributes->readonly);
        $this->required = (bool) boolval($widget_atributes->required);
        $this->icon = (string) $widget_atributes->icon;
        $this->onClick = (string) $widget_atributes->onclick;

        $this->getAttributesGroup($this->options, $column->widget->option);
        $this->getAttributesGroup($this->values, $column->widget->values);
    }

    /**
     * Carga la estructura de atributos en base a la base de datos
     *
     * @param SimpleXMLElement $column
     */
    public function loadFromJSONColumn($column)
    {
        $this->fieldName = (string) $column['widget']['fieldName'];
        $this->type = (string) $column['widget']['type'];
        $this->decimal = (int) intval($column['widget']['decimal']);
        $this->hint = (string) $column['widget']['hint'];
        $this->readOnly = (bool) boolval($column['widget']['readonly']);
        $this->required = (bool) boolval($column['widget']['required']);
        $this->icon = (string) $column['widget']['icon'];
        $this->onClick = (string) $column['widget']['onClick'];
        $this->options = (array) $column['widget']['options'];
        $this->values = (array) $column['widget']['values'];
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
        return empty($hint)
            ? ''
            : ' data-toggle="popover" data-placement="auto" data-trigger="hover" data-content="' . $hint . '" ';
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
        switch (substr($optionValue, 0, 1)) {
            case '<':
                $optionValue = substr($optionValue, 1);
                $result = (floatval($valueItem) < floatval($optionValue));
                break;

            case '>':
                $optionValue = substr($optionValue, 1);
                $result = (floatval($valueItem) > floatval($optionValue));
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
    private function getTextOptionsHTML($valueItem)
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
     * Aplica el formato a tipos: texto, numérico y moneda
     * 
     * @param string $value
     * @return string
     */
    private function getListStandardHTML($value)
    {
        $style = $this->getTextOptionsHTML($value);

        switch ($this->type) {
            case 'text':
                $html = (empty($this->onClick)) 
                    ? '<span' . $style . '>' . $value . '</span>' 
                    : '<a href="?page=' . $this->onClick . '&code=' . $value . '"' . $style . '>' . $value . '</a>';
                break;

            case 'number':
                $html = '<span' . $style . '>' . self::$numberTools->format($value, $this->decimal) . '</span>';
                break;

            case 'money':
                $html = empty($this->decimal) 
                    ? self::$divisaTools->format($value)
                    : self::$divisaTools->format($value, $this->decimal);

                $html = '<span' . $style . '>' . $html . '</span>';
                break;                    
        }
        
        return $html;
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
        // Verificamos el valor, en vez de usar empty() para que deje pasar 0 y false
        if (is_null($value) || ($value == '')) {
            return '';
        }

        switch ($this->type) {
            case 'text':
            case 'number':
            case 'money':
                $html = $this->getListStandardHTML($value);
                break;

            case 'checkbox':
                $value = in_array($value, ['t', '1']);
                $icon = $value ? 'fa-check' : 'fa-minus';
                $style = $this->getTextOptionsHTML($value);
                $html = '<i class="fa ' . $icon . '" aria-hidden="true"' . $style . '></i>';
                break;

            case 'icon':
                $html = '<i class="fa "' . $this->icon . '" aria-hidden="true"></i>';
                break;

            default:
                $html = '<span>' . $value . '</span>';
        }

        return $html;
    }

    /**
     * General el código html para la visualización del icono en el lado
     * izquierda de los datos
     *
     * @return string
     */
    private function getIconHTML()
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
     * Genera el código html para clases especiales como:
     * sólo lectura
     * valor obligatorio
     *
     * @return string
     */
    private function specialClass()
    {
        $hint = $this->getHintHTML($this->hint);
        $readOnly = (empty($this->readOnly)) ? '' : ' readonly="readonly"';
        $required = (empty($this->required)) ? '' : ' required="required"';

        return $hint . $readOnly . $required;
    }

    /**
     * Genera el código html para la visualización y edición de los datos
     * en el controlador Edit
     *
     * @param string $value
     *
     * @return string
     */
    public function getEditHTML($value)
    {
        $specialClass = $this->specialClass();
        $fieldName = '"' . $this->fieldName . '"';
        $html = $this->getIconHTML();

        switch ($this->type) {
            case 'text':
                $html .= $this->standardHTMLWidget($fieldName, $value, $specialClass);
                break;

            case 'datepicker':
                $html .= '<input id=' . $fieldName . ' class="form-control datepicker" type="text" name=' . $fieldName . ' value="' . $value . '"' . $specialClass . '>';
                break;

            case 'checkbox':
                $checked = in_array(strtolower($value), ['true', 't', '1']) ? ' checked ' : '';
                $html .= '<input id=' . $fieldName . ' class="form-check-input" type="checkbox" name=' . $fieldName . ' value="true"' . $specialClass . $checked . '>';
                break;

            case 'radio':
                $html .= '<input id=' . $fieldName . 'sufix% class="form-check-input" type="radio" name=' . $fieldName . ' value=""value%"' . $specialClass . '"checked%>';
                break;

            case 'textarea':
                $html .= '<textarea id=' . $fieldName . ' class="form-control" name=' . $fieldName . ' rows="3"' . $specialClass . '>' . $value . '</textarea>';
                break;

            case 'select':
                $html .= $this->selectHTMLWidget($fieldName, $value, $specialClass);
                break;

            default:
                $html .= $this->standardHTMLWidget($fieldName, $value, $specialClass);
        }

        if (!empty($this->icon)) {
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Devuelve el código HTML para controles no especiales
     *
     * @param string $fieldName
     * @param string $value
     * @param string $specialClass
     *
     * @return string
     */
    private function standardHTMLWidget($fieldName, $value, $specialClass)
    {
        return '<input id=' . $fieldName . ' type="' . $this->type . '" class="form-control" name=' . $fieldName
                . ' value="' . $value . '"' . $specialClass . '>';
    }

    /**
     * Devuelve el código HTML para controles tipo Select
     *
     * @param string $fieldName
     * @param string $value
     * @param string $specialClass
     *
     * @return string
     */
    private function selectHTMLWidget($fieldName, $value, $specialClass)
    {
        $html = '<select id=' . $fieldName . ' class="form-control" name=' . $fieldName . $specialClass . '>';
        foreach ($this->values as $selectValue) {
            $selected = ($selectValue['value'] == $value) ? ' selected="selected" ' : '';
            $html .= '<option value="' . $selectValue['value'] . '"' . $selected . '>' . $selectValue['title'] . '</option>';
        }
        $html .= '</select>';

        return $html;
    }
}
