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
 * Description of ColumnItem
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ColumnItem
{

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
     * Configuración del campo de la columna
     * @var FieldOptions
     */
    public $field;

    /**
     * Texto adicional que explica el campo al usuario
     * @var string
     */
    public $description;

    /**
     * Configuración del objeto de visualización del campo
     * @var WidgetOptions
     */
    public $widget;

    /**
     * Número de columnas que usa el campo en su visualización
     * (Mínimo 1 - Máximo 8)
     * @var int
     */
    public $numColumns;

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
        $this->numColumns = 1;
        $this->display = 'none';
        $this->field = new FieldOptions();
        $this->widget = new WidgetOptions();
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
        $this->numColumns = (int) $column_atributes->numcolumns;
        $this->display = (string) $column_atributes->display;

        $this->field->loadFromXMLColumn($column);
        $this->widget->loadFromXMLColumn($column);
    }

    public function loadFromJSONColumn($column)
    {
        $this->title = (string) $column['title'];
        $this->titleURL = (string) $column['titleURL'];
        $this->description = (string) $column['description'];
        $this->numColumns = (int) $column['numColumns'];
        $this->display = (string) $column['display'];
    }

    public function columnsFromJSON($columns)
    {
        $result = [];
        foreach ($columns as $data) {
            $columnItem = new ColumnItem();
            $columnItem->loadFromJSONColumn($data);
            $columnItem->field->loadFromJSONColumn($data);
            $columnItem->widget->loadFromJSONColumn($data);
            $result[] = $columnItem;
        }
        return $result;
    }
    
    public function getHeaderHTML($value)
    {
        $html = $value;
        if (!empty($this->titleURL)) {
            $target = (substr($this->titleURL, 0, 1) != '?') ? "target='_blank'" : '';
            $html = "<a href='" . $this->titleURL . "' " . $target . ">" . $value . "</a>";
        }
        
        return $html;
    }
}
