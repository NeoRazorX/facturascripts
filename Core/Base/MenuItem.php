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
     * Lista de opciones de menú para el item
     * @var MenuItem[]
     */
    public $menu;

    /**
     * Contruye y rellena los valores principales del Item
     * @param string $title
     * @param string $url
     */
    public function __construct($title, $url)
    {
        $this->title = $title;
        $this->url = $url;
        $this->menu = [];
    }

    public function getHTML($level = 0)
    {
        if (empty($this->menu)) {
            return '<li><a href="' . $this->url . '">' . $this->title . '</a></li>';
        }

        if ($level === 0) {
            $html = '<li>';
        } else {
            $html = '<li class="dropdown-submenu">';
        }

        if ($level === 0) {
            $html .= '<a href="' . $this->url . '" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">'
                . $this->title . ' <span class="caret"></span>'
                . '</a>'
                . '<ul class="dropdown-menu multi-level">';
        } else {
            $html .= '<a href="' . $this->url . '" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">'
                . $this->title . '</a>'
                . '<ul class="dropdown-menu">';
        }

        foreach ($this->menu as $menuItem) {
            $html .= $menuItem->getHTML($level + 1);
        }

        $html .= '</ul></li>';

        return $html;
    }
}
