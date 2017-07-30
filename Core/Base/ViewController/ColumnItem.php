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

use FacturaScripts\Core\Model as Models;

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
     * Constructor de la clase. Si se informa un array se cargan los datos
     * informados en el nuevo objeto
     * @param array $data
     */
    public function __construct($data = [])
    {
        if (empty($data)) {
            $this->init();
        } else {
            $this->loadFromData($data);
        }
    }
    
    /**
     * Inicializa la clase con valores nulos
     */
    private function init() {
        $this->title = null;
        $this->titleURL = null;
        $this->description = null;
        $this->field = new FieldOptions();
        $this->widget = new WidgetOptions();
        $this->numColumns = 1;
        $this->display = 'none';
    }
    
    /**
     * Inicializa la clase con los datos de un array
     * @param array $data
     */
    private function loadFromData($data) {
        $this->title = $data['title'];
        $this->titleURL = $data['titleurl'];
        $this->description = $data['description'];
        $this->field = new FieldOptions($data['field']);
        $this->widget = new WidgetOptions($data['widget']);
        $this->numColumns = $data['numcolumns'];
        $this->display = $data['display'];
    }

    /**
     * Carga la configuración de columnas de un controlador para el usuario
     * @param string $controller
     * @param string $user
     * @return array ColumnItem
     */
    public static function getColumns($controller, $user)
    {
        $pageOption = new Models\PageOption();
        $pageOption->all();
    }    
}
