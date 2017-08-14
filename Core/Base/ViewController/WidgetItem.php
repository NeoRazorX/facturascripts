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
namespace FacturaScripts\Core\Base\ViewController;

/**
 * Description of WidgetItem
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class WidgetItem
{

    /**
     * Nombre del campo con los datos que visualiza el widget
     * @var string
     */
    public $fieldName;

    /**
     * Tipo de widget que se visualiza
     * @var string
     */
    public $type;

    /**
     * Información adicional para el usuario
     * @var string
     */
    public $hint;

    /**
     * Indica que el campo es no editable
     * @var boolean
     */
    public $readOnly;

    /**
     * Indica que el campo es obligatorio y debe contener un valor
     * @var boolean
     */
    public $required;
    
    /**
     * Icono que se usa como valor o acompañante del widget
     * @var string
     */
    public $icon;

    /**
     * Controlador destino al hacer click sobre los datos visualizados
     * @var string
     */
    public $onClick;

    /**
     * Opciones para configurar el widget
     * @var array
     */
    public $options;

    /**
     * Constructor de la clase. Si se informa un array se cargan los datos
     * informados en el nuevo objeto
     */
    public function __construct()
    {
        $this->type = 'text';
        $this->fieldName = '';
        $this->hint = '';
        $this->readOnly = FALSE;
        $this->required = FALSE;
        $this->icon = null;
        $this->onClick = '';
        $this->options = [];
    }

    public function loadFromXMLColumn($column)
    {
        $widget_atributes = $column->widget->attributes();
        $this->fieldName = (string) $widget_atributes->fieldname;
        $this->type = (string) $widget_atributes->type;
        $this->hint = (string) $widget_atributes->hint;
        $this->readOnly = (boolean) boolval($widget_atributes->readonly);
        $this->required = (boolean) boolval($widget_atributes->required);
        $this->hint = (string) $widget_atributes->hint;
        $this->icon = (string) $widget_atributes->icon;
        $this->onClick = (string) $widget_atributes->onclick;

        foreach ($column->widget->option as $option) {
            $values = [];
            foreach ($option->attributes() as $key => $value) {
                $values[$key] = (string) $value;
            }
            $values['value'] = (string) $option;
            $this->options[] = $values;
            unset($values);
        }
    }

    public function loadFromJSONColumn($column)
    {
        $this->fieldName = (string) $column['widget']['fieldName'];
        $this->type = (string) $column['widget']['type'];
        $this->hint = (string) $column['widget']['hint'];
        $this->readOnly = (boolean) boolval($column['widget']['readonly']);
        $this->required = (boolean) boolval($column['widget']['required']);
        $this->icon = (string) $column['widget']['icon'];
        $this->onClick = (string) $column['widget']['onClick'];
        $this->options = (array) $column['widget']['options'];
    }

    private function getTextOptionsHTML($valueItem)
    {
        $html = '';
        foreach ($this->options as $option) {
            if ($option['value'] == $valueItem) {
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

    public function getListHTML($value)
    {
        if (empty($value)) {
            return '';
        }

        switch ($this->type) {
            case 'text':
                $style = $this->getTextOptionsHTML($value);
                $html = (empty($this->onClick)) 
                        ? '<span' . $style . '>' . $value . '</span>' 
                        : '<a href="?page=' . $this->onClick . '&code=' . $value . '"' . $style . '>' . $value . '</a>';
                break;

            case 'check':
                $value = in_array($value, ['t', '1']);
                $icon = $value ? 'glyphicon-ok' : 'glyphicon-minus';
                $style = $this->getTextOptionsHTML($value);
                $html = '<span class="glyphicon ' . $icon . '"' . $style . '></span>';
                break;

            case 'icon':
                $html = '<span class="glyphicon "' . $this->icon . ' aria-hidden="true" title="' . $this->hint . '">' . $value . '</span>';
                break;

            default:
                $html = $value;
        }

        return $html;
    }
    
    private function getIconHTML()
    {        
        if (empty($this->icon)) {
            return '';
        }
        
        $html = '<div class="input-group"><span class="input-group-addon">';
        if (strpos($this->icon, 'glyphicon') === 0) {    
            return $html . '<i class="glyphicon ' . $this->icon . '"></i></span>';
        }
        
        if (strpos($this->icon, 'fa-') === 0) {
            return $html . '<i class="fa ' . $this->icon . '" aria-hidden="true"></i></span>';
        }
                
        return $html . '<i aria-hidden="true">' . $this->icon . '</i></span>';            
    }
    
    private function specialClass()
    {
        $readOnly = (empty($this->readOnly)) ? '' : ' readonly="readonly"';
        $required = (empty($this->required)) ? '' : ' required="required"';
        
        return $readOnly . $required;
    }
    
    public function getEditHTML($value)
    {
        $specialClass = $this->specialClass();
        $fieldName = '"' . $this->fieldName . '"';
        $html = $this->getIconHTML();
        
        switch ($this->type) {
            case 'checkbox-inline':
            case 'checkbox':
                $html .= '<input id=' . $fieldName . ' type="checkbox" name=' . $fieldName . ' value="TRUE"' . $specialClass . '>';
                break;

            default:
                $html .= '<input id=' . $fieldName . ' type="' . $this->type . '" class="form-control" name=' . $fieldName . ' value="' . $value . '"' . $specialClass . '>';
        }

        if (!empty($this->icon)) {
            $html .= '</div>';
        }
        
        return $html;
    }    
}
