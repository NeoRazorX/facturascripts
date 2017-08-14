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
 * Description of GroupItem
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class GroupItem extends VisualItem implements VisualItemInterface
{    
    /**
     * Icono que se usa como valor o acompañante del título de grupo
     * @var string
     */    
    public $icon;
    
    /**
     * Definición de columnas que incluye el grupo
     * @var array 
     */
    public $columns;
    
    /**
     * Construye e inicializa la clase.
     */
    public function __construct()
    {
        parent::__construct();

        $this->icon = NULL;
        $this->columns = [];
    }
    
    public function loadFromXMLColumns($group)
    {
        $this->columns = [];
        foreach ($group->column as $column) {
            $columnItem = new ColumnItem();
            $columnItem->loadFromXML($column);
            $key = str_pad($columnItem->order, 3, '0', STR_PAD_LEFT) . '_' . $columnItem->widget->fieldName;
            $this->columns[$key] = $columnItem;
            unset($columnItem);
        }
        ksort($this->columns, SORT_STRING);
    }    
    
    /**
     * Carga la estructura de atributos en base a un archivo XML
     * @param SimpleXMLElement $group
     */    
    public function loadFromXML($group)
    {
        parent::loadFromXML($group);
        
        $group_atributes = $group->attributes();
        $this->icon = (string) $group_atributes->icon;        
        $this->loadFromXMLColumns($group);
    }   
    
    /**
     * Carga la estructura de atributos en base a la base de datos
     * @param SimpleXMLElement $group
     */    
    public function loadFromJSON($group)
    {
        parent::loadFromJSON($group);
        $this->options = (array) $group['columns'];
    }

    /**
     * Obtiene el código html para visualizar un icono
     * @return string
     */
    private function getIconHTML()
    {        
        if (empty($this->icon)) {
            return '';
        }
        
        if (strpos($this->icon, 'fa-') === 0) {
            return '<i class="fa ' . $this->icon . '" aria-hidden="true">&nbsp;&nbsp;</i></span>';
        }
                
        return '<i aria-hidden="true">' . $this->icon . '</i>&nbsp;&nbsp;</span>';            
    }
    
    /**
     * Genera el código html para visualizar la cabecera del elemento visual
     * @param string $value
     */    
    public function getHeaderHTML($value)
    {
        $html = $this->getIconHTML() . parent::getHeaderHTML($value);        
        return $html;        
    }
}
