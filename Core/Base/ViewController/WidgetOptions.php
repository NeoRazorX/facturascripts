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
 * Description of WidgetOptions
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class WidgetOptions
{

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
     * Icono que se usa como valor o acompañante del widget
     * @var string
     */
    public $icon;

    /**
     * Código HTML para la representación del widget
     * @var string
     */
    public $html;

    /**
     * Constructor de la clase. Si se informa un array se cargan los datos
     * informados en el nuevo objeto
     */
    public function __construct()
    {
        $this->type = 'text';
        $this->hint = '';
        $this->icon = null;
        $this->html = '';
    }

    public function loadFromXMLColumn($column)
    {
        $widget_atributes = $column->widget->attributes();
        $this->type = (string) $widget_atributes->type;
        $this->hint = (string) $widget_atributes->hint;
        $this->icon = (string) $widget_atributes->icon;
    }

    public function loadFromJSONColumn($column)
    {
        $this->type = (string) $column['widget']['type'];
        $this->hint = (string) $column['widget']['hint'];
        $this->icon = (string) $column['widget']['icon'];
    }

    public function getHTML($value)
    {
        $html = '';
        switch ($this->type) {
            case "text":
                $html = empty($value) ? '' : $value;
                break;

            case "check":
                if (in_array($value, ['t', '1'])) {
                    $html = '<span class="glyphicon glyphicon-ok"></span>';
                }
                break;

            case "icon":
                $html = '<span class="glyphicon "' . $this->icon . ' aria-hidden="true" title="' . $this->hint . '"></span>';
                break;

            default:
                $html = $this->extraWidgetHTML($value);
        }

        return $html;
    }

    private function extraWidgetHTML($value)
    {
        $html = $value;
        switch ($this->type) {
            case "downdrop":
                break;

            case "textarea":
                break;
        }

        return $html;
    }
}
