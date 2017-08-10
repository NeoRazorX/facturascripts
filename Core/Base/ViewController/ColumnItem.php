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
 * Description of ColumnItem
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ColumnItem
{
    private $i18n;

    /**
     * Etiqueta o título de la columna
     * @var string
     */
    public $title;

    /**
     * URL de salto si hacen click en $title
     * @var string
     */
    public $titleURL;

    /**
     * Texto adicional que explica el campo al usuario
     * @var string
     */
    public $description;

    /**
     * Configuración del objeto de visualización del campo
     * @var WidgetItem
     */
    public $widget;

    /**
     * Número de columnas que usa el campo en su visualización
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
     * Configuración del estado y alineamiento de la visualización
     * (left|right|center|none)
     * @var string
     */
    public $display;

    /**
     * Construye e inicializa la clase.
     */
    public function __construct()
    {
        $this->title = '';
        $this->titleURL = '';
        $this->description = '';
        $this->numColumns = 12;
        $this->display = 'none';
        $this->order = 100;
        $this->widget = new WidgetItem();
        $this->i18n = new Base\Translator();
    }

    /**
     * Inicializa la clase con los valores pasados.
     * Es una estructura de columna leida desde un XML
     * @param SimpleXMLElement $column
     */
    public function loadFromXMLColumn($column)
    {
        $column_atributes = $column->attributes();
        $this->title = (string) $column_atributes->title;
        $this->titleURL = (string) $column_atributes->titleurl;
        $this->description = (string) $column_atributes->description;
        $this->display = (string) $column_atributes->display;

        if (!empty($column_atributes->numcolumns)) {
            $this->numColumns = (int) $column_atributes->numcolumns;
        }
        
        if (!empty($column_atributes->order)) {
            $this->order = (int) $column_atributes->order;
        }        
        
        $this->widget->loadFromXMLColumn($column);
    }

    public function loadFromJSONColumn($column)
    {
        $this->title = (string) $column['title'];
        $this->titleURL = (string) $column['titleURL'];
        $this->description = (string) $column['description'];
        $this->numColumns = (int) $column['numColumns'];
        $this->display = (string) $column['display'];
        $this->order = (int) $column['order'];
    }

    public function columnsFromJSON($columns)
    {
        $result = [];
        foreach ($columns as $data) {
            $columnItem = new ColumnItem();
            $columnItem->loadFromJSONColumn($data);
            $columnItem->widget->loadFromJSONColumn($data);
            $result[] = $columnItem;
        }
        return $result;
    }

    public function getHeaderHTML($value)
    {
        $html = $this->i18n->trans($value);
        if (!empty($this->description)) {
            $html = '<span title="' . $this->i18n->trans($this->description) . '">' . $html . '</span>';
        }
        
        if (!empty($this->titleURL)) {
            $target = (substr($this->titleURL, 0, 1) != '?') ? "target='_blank'" : '';
            $html = '<a href="' . $this->titleURL . '" ' . $target . '>' . $html . '</a>';
        }

        return $html;
    }
    
    public function getListHTML($value)
    {
        return $this->widget->getListHTML($value);
    }
    
    public function getEditHTML($value)
    {
        $columnClass = ($this->numColumns < 12) ? (' col-md-' . $this->numColumns) : '';
        $input = $this->widget->getEditHTML($value);
        $header = $this->getHeaderHTML($this->title);
        $hint = empty($this->widget->hint) ? '' : ' title="' . $this->i18n->trans($this->widget->hint) . '"';
        $description = empty($this->description) ? '' : '<span class="help-block">' . $this->i18n->trans($this->description) . '</span>';
        
        switch ($this->widget->type) {
            case "checkbox-inline":
            case "checkbox":
                $html = '<div class="' . $this->widget->type . $columnClass . '">'
                        . '<label class="checkbox-inline"' . $hint . '>'
                        . $input . $header 
                        . '</label>'
                        . $description
                        . '</div>';
                break;

            default:
                $html = '<div class="form-group' . $columnClass . '">'
                        . '<label for="' . $this->widget->fieldName . '"' . $hint . '>' . $header . '</label>'
                        . $input
                        . $description
                        . '</div>';                        
                break;
        }
        return $html;
    }        
}
