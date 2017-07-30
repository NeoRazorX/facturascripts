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
     * Datos adicionales dependientes del tipo de Widget
     * @var string
     */
    public $value;
    
    /**
     * Información adicional para el usuario
     * @var string
     */
    public $title;
    
    /**
     * Código HTML para la representación del widget
     * @var string
     */
    public $html;

    /**
     * Constructor de la clase. Si se informa un array se cargan los datos
     * informados en el nuevo objeto
     * @param string $type
     */
    public function __construct($type, $value, $title)
    {
        $this->type = $type;
        $this->value = $value;
        $this->title = $title;
        $this->html = $this->getHTML();            
    }
    
    private function getHTML()
    {
        $html = '';
        switch ($this->type) {
            case "text":
                break;
            
            case "check":
                $html = '<span class="glyphicon glyphicon-ok"></span>';
                break;
            
            case "downdrop":
                break;
            
            case "textarea":
                break;
            
            case "icon":
                $html = '<span class="glyphicon "' . $this->value . ' aria-hidden="true" title="' . $this->title . '"></span>';
                break;
            
            default:
        }
        
        return $html;
    }
}
