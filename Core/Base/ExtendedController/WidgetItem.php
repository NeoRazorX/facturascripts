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
     * Valor del incremento
     *
     * @var string
     */
    public $step;

    /**
     * Valor máximo
     *
     * @var string
     */
    public $max;

    /**
     * Valor mínimo
     *
     * @var string
     */
    public $min;

    /**
     * Valores aceptados por el campo asociado al widget
     *
     * @var array
     */
    public $values;

    /**
     * Clase para formatear valores de la divisa
     *
     * @var DivisaTools
     */
    private static $divisaTools;

    /**
     * Clase para formatear valores numéricos
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
        if (!isset(self::$divisaTools)) {
            self::$divisaTools = new DivisaTools();
            self::$numberTools = new NumberTools();
        }

        $this->type = 'text';
        $this->decimal = 0;
        $this->fieldName = '';
        $this->hint = '';
        $this->readOnly = false;
        $this->required = false;
        $this->icon = null;
        $this->onClick = '';
        $this->options = [];
        $this->values = [];
        $this->step = 'any';
        $this->max = '';
        $this->min = '';
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
            $item = [];
            $item['value'] = $codeModel->code;
            $item['title'] = $codeModel->description;
            $this->values[] = $item;
            unset($item);
        }
    }

    /**
     * Carga la lista de valores según un array de valores
     *
     * @param array $values
     */
    public function setValuesFromArray(&$values)
    {
        $this->values = [];
        foreach ($values as $value) {
            $item = [];
            $item['value'] = $value;
            $item['title'] = $value;
            $this->values[] = $item;
            unset($item);
        }
    }

    /**
     * Carga el diccionario de atributos de un grupo de opciones o valores
     * del widget
     *
     * @param array            $property
     * @param \SimpleXMLElement $group
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
     * @param \SimpleXMLElement $column
     */
    public function loadFromXMLColumn($column)
    {
        $widgetAtributes = $column->widget->attributes();
        $this->fieldName = (string) $widgetAtributes->fieldname;
        $this->type = (string) $widgetAtributes->type;
        $this->decimal = (int) $widgetAtributes->decimal;
        $this->hint = (string) $widgetAtributes->hint;
        $this->readOnly = (bool) $widgetAtributes->readonly;
        $this->required = (bool) $widgetAtributes->required;
        $this->icon = (string) $widgetAtributes->icon;
        $this->onClick = (string) $widgetAtributes->onclick;
        $this->step = (string) $widgetAtributes->step;
        $this->min = (string) $widgetAtributes->min;
        $this->max = (string) $widgetAtributes->max;

        $this->getAttributesGroup($this->options, $column->widget->option);
        $this->getAttributesGroup($this->values, $column->widget->values);
    }

    /**
     * Carga la estructura de atributos en base a la base de datos
     *
     * @param array $column
     */
    public function loadFromJSONColumn($column)
    {
        $this->fieldName = (string) $column['widget']['fieldName'];
        $this->type = (string) $column['widget']['type'];
        $this->decimal = (int) $column['widget']['decimal'];
        $this->hint = (string) $column['widget']['hint'];
        $this->readOnly = (bool) $column['widget']['readOnly'];
        $this->required = (bool) $column['widget']['required'];
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
        return empty($hint) ? '' : ' data-toggle="popover" data-placement="auto" data-trigger="hover" data-content="' . $hint . '" ';
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
    private function getTextOptionsHTML($valueItem)
    {
        $html = '';
        foreach ($this->options as $option) {
            if ($this->canApplyOptions($option['value'], $valueItem)) {
                $html = ' style="';
                foreach ($option as $key => $value) {
                    if ($key !== 'value') {
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
                $txt = $this->getTextResume($value);
                $html = empty($this->onClick) ? '<span ' . $style . '>' . $txt . '</span>' : '<a href="?page='
                    . $this->onClick . '&code=' . $value . '" ' . $style . '>' . $txt . '</a>';
                break;

            case 'number':
                $html = '<span ' . $style . '>' . self::$numberTools->format($value, $this->decimal) . '</span>';
                break;

            case 'money':
                $aux = empty($this->decimal) ? self::$divisaTools->format($value) : self::$divisaTools->format($value, $this->decimal);
                $html = '<span ' . $style . '>' . $aux . '</span>';
                break;
            default:
                $html = 'not-supported-type';
        }

        return $html;
    }

    /**
     * Devuelve el texto resumido
     *
     * @param string $txt
     *
     * @return string
     */
    private function getTextResume($txt)
    {
        if (mb_strlen($txt) < 60) {
            return $txt;
        }

        return mb_substr($txt, 0, 57) . '...';
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
        if ($value === null || $value === '') {
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
                $html = '<i class="fa ' . $icon . '" aria-hidden="true" ' . $style . '></i>';
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
     * Genera el código html para atributos especiales como:
     * sólo lectura
     * valor obligatorio
     *
     * @return string
     */
    private function specialAttributes()
    {
        $hint = $this->getHintHTML($this->hint);
        $readOnly = empty($this->readOnly) ? '' : ' readonly="readonly"';
        $required = empty($this->required) ? '' : ' required="required"';
        $step = empty($this->step) ? '' : ' step="' . $this->step . '"';
        $min = empty($this->step) ? '' : ' min="' . $this->min . '"';
        $max = empty($this->step) ? '' : ' max="' . $this->max . '"';

        return $step . $min . $max . $hint . $readOnly . $required;
    }

    /**
     * Genera el código html para la visualización y edición de los datos
     * en el controlador List / Edit
     *
     * @param string $value
     *
     * @return string
     */
    public function getEditHTML($value)
    {
        $specialAttributes = $this->specialAttributes();
        $fieldName = '"' . $this->fieldName . '"';
        $html = $this->getIconHTML();

        switch ($this->type) {
            case 'checkbox':
                $checked = in_array(strtolower($value), ['true', 't', '1']) ? ' checked ' : '';
                $html .= '<input id=' . $fieldName . ' class="form-check-input" type="checkbox" name='
                    . $fieldName . ' value="true"' . $specialAttributes . $checked . '>';
                break;

            case 'radio':
                $html .= '<input id=' . $fieldName . 'sufix% class="form-check-input" type="radio" name='
                    . $fieldName . ' value=""value%"' . $specialAttributes . '"checked%>';
                break;

            case 'textarea':
                $html .= '<textarea id=' . $fieldName . ' class="form-control" name=' . $fieldName . ' rows="3" '
                    . $specialAttributes . '>' . $value . '</textarea>';
                break;

            case 'select':
                $html .= $this->selectHTMLWidget($fieldName, $value, $specialAttributes);
                break;

            case 'datepicker':
                $html .= $this->standardHTMLWidget($fieldName, $value, $specialAttributes, ' datepicker');
                break;

            default:
                $html .= $this->standardHTMLWidget($fieldName, $value, $specialAttributes);
        }

        if (!empty($this->icon)) {
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Devuelve el código HTML para controles no especiales
     * @param string $fieldName
     * @param mixed $value
     * @param string $specialAttributes
     * @param string $extraClass
     *
     * @return string
     */
    private function standardHTMLWidget($fieldName, $value, $specialAttributes, $extraClass = '')
    {
        return '<input id=' . $fieldName . ' type="' . $this->type . '" class="form-control' . $extraClass
            . '" name=' . $fieldName . ' value="' . $value . '"' . $specialAttributes . ' />';
    }

    /**
     * Devuelve el código HTML para controles tipo Select
     *
     * @param string $fieldName
     * @param string $value
     * @param string $specialAttributes
     *
     * @return string
     */
    private function selectHTMLWidget($fieldName, $value, $specialAttributes)
    {
        $html = '<select id=' . $fieldName . ' class="form-control" name=' . $fieldName . $specialAttributes . '>';
        foreach ($this->values as $selectValue) {
            $selected = ($selectValue['value'] == $value) ? ' selected="selected" ' : '';
            $html .= '<option value="' . $selectValue['value'] . '" ' . $selected . '>' . $selectValue['title'] . '</option>';
        }
        $html .= '</select>';

        return $html;
    }
}
