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
class MenuItem
{

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
     * Icono de la fuente Fontawesome de la opción de menú
     * @var string 
     */
    public $icon;
    
    /**
     * Lista de opciones de menú para el item
     * @var MenuItem[]
     */
    public $menu;

    /**
     * Contruye y rellena los valores principales del Item
     * @param string $title
     * @param string $url
     */
    public function __construct($title, $url, $icon)
    {
        $this->title = $title;
        $this->url = $url;
        $this->icon = $icon;
        $this->menu = [];
    }
    
    /**
     * Devuelve el html para el icono del item
     * @return string
     */
    private function getHTMLIcon()
    {
        $result = '<i class="fa " aria-hidden="true" style="margin-right: 19px"></i> ';
        if (!empty($this->icon)) {
          $result = '<i class="fa ' . $this->icon . '" aria-hidden="true" style="margin-right: 5px"></i> ';
        }
        return $result;
    }
    
    /**
     * Devuelve el html para el menú / submenú
     * @param int $level
     * @return string
     */
    public function getHTML($level = 0)
    {
        if (empty($this->menu)) {
            return '<li><a href="' . $this->url . '">' . $this->getHTMLIcon() . $this->title . '</a></li>';
        }

        $base = '<a href="' . $this->url . '" class="dropdown-toggle"'
            . ' data-toggle="dropdown" role="button" aria-haspopup="true"'
            . ' aria-expanded="false">' . $this->title;
        
        if ($level === 0) {
            $html = '<li>' . $base . ' <span class="caret"></span>' . '</a>' . '<ul class="dropdown-menu multi-level">';
        } else {
            $html = '<li class="dropdown-submenu">' . $base . '</a>' . '<ul class="dropdown-menu">';
        }

        foreach ($this->menu as $menuItem) {
            $html .= $menuItem->getHTML($level + 1);
        }

        $html .= '</ul></li>';

        return $html;
    }
}
