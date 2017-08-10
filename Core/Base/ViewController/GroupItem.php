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
 * Description of GroupItem
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class GroupItem
{
    private $i18n;
    
    /**
     * Etiqueta o título del grupo
     * @var string
     */
    public $title;

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
     * Definición de columnas que incluye el grupo
     * @var array 
     */
    public $columns;
    
    /**
     * Construye e inicializa la clase.
     */
    public function __construct()
    {
        $this->title = '';
        $this->numColumns = 12;
        $this->order = 100;
        $this->columns = [];
        $this->i18n = new Base\Translator();
    }
    
    public function loadFromXMLColumns($group)
    {
        $this->columns = [];
        foreach ($group->column as $column) {
            $columnItem = new ColumnItem();
            $columnItem->loadFromXMLColumn($column);
            $key = str_pad($columnItem->order, 3, '0', STR_PAD_LEFT) . '_' . $columnItem->widget->fieldName;
            $this->columns[$key] = $columnItem;
            unset($columnItem);
        }
        ksort($this->columns, SORT_STRING);
    }    
    
    public function loadFromXML($group)
    {
        $group_atributes = $group->attributes();
        $this->title = (string) $group_atributes->title;

        if (!empty($group_atributes->numcolumns)) {
            $this->numColumns = (int) $group_atributes->numcolumns;
        }
        
        if (!empty($group_atributes->order)) {
            $this->order = (int) $group_atributes->order;
        }        

        $this->loadFromXMLColumns($group);
    }   
    
    public function loadFromJSON($group)
    {
        $this->title = (string) $group['title'];
        $this->numColumns = (int) $group['numColumns'];
        $this->order = (int) $group['order'];
        $this->options = (array) $group['columns'];
    }    
}
