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

namespace FacturaScripts\Core\Base;

/**
 * Estructura para cada uno de los items del menú de Facturascripts
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class MenuItem {
    /**
     * Título de la opción de menú
     * @var string
     */
    public $title;
    
    /**
     * URL para el href de la opción de menú
     * @var string
     */
    public $url;
    
    /**
     * Lista de opciones de menú para el item
     * @var array
     */
    public $menu;
    
    /**
     * Contruye y rellena los valores principales del Item
     * @param type $title
     * @param type $url
     */
    public function __construct($title, $url) {
        $this->title = $title;
        $this->url = $url;
        $this->menu = [];
    }
}
