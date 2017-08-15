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

use FacturaScripts\Core\Base as Base;

/**
 * Estructura básica/común para cabecera visual
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class VisualItem
{
    /**
     * Motor de traducción.
     * @var Base\Translator
     */
    protected $i18n;
    
    /**
     * Etiqueta o título del grupo
     * @var string
     */
    public $title;

    /**
     * URL de salto si hacen click en $title
     * @var string
     */
    public $titleURL;
    
    /**
     * Número de columnas que ocupa en su visualización
     * ([1, 2, 4, 6, 8, 10, 12])
     * @var int
     */
    public $numColumns;

    /**
     * Posición en la que se visualizá ( de menor a mayor )
     * @var int
     */
    public $order;
    
    /**
     * Construye e inicializa la clase.
     */
    public function __construct()
    {
        $this->title = '';
        $this->titleURL = '';        
        $this->numColumns = 12;
        $this->order = 100;
        $this->i18n = new Base\Translator();
    }
    
    /**
     * Carga
     * @param type $items
     */
    public function loadFromXML($items)
    {
        $items_atributes = $items->attributes();
        $this->title = (string) $items_atributes->title;
        $this->titleURL = (string) $items_atributes->titleurl;
        
        if (!empty($items_atributes->numcolumns)) {
            $this->numColumns = (int) $items_atributes->numcolumns;
        }
        
        if (!empty($items_atributes->order)) {
            $this->order = (int) $items_atributes->order;
        }                
    }
    
    public function loadFromJSON($items)
    {
        $this->title = (string) $items['title'];
        $this->titleURL = (string) $items['titleURL'];
        $this->numColumns = (int) $items['numColumns'];
        $this->order = (int) $items['order'];        
    }
    
    public function getHeaderHTML($value)
    {
        $html = $this->i18n->trans($value);
        
        if (!empty($this->titleURL)) {
            $target = (substr($this->titleURL, 0, 1) != '?') ? "target='_blank'" : '';
            $html = '<a href="' . $this->titleURL . '" ' . $target . '>' . $html . '</a>';
        }
        
        return $html;
    }
}
