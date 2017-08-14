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
     * Nombre identificativo del elemento.
     * @var string
     */
    public $name;

    /**
     * Título de la opción de menú.
     * @var string
     */
    public $title;

    /**
     * URL para el href de la opción de menú.
     * @var string
     */
    public $url;

    /**
     * Icono de la fuente Fontawesome de la opción de menú.
     * @var string
     */
    public $icon;

    /**
     * Indica si está activado o no.
     * @var bool
     */
    public $active;

    /**
     * Lista de opciones de menú para el item.
     * @var MenuItem[]
     */
    public $menu;

    /**
     * Contruye y rellena los valores principales del Item
     * @param string $name
     * @param string $title
     * @param string $url
     * @param string $icon
     */
    public function __construct($name, $title, $url, $icon = null)
    {
        $this->name = $name;
        $this->title = $title;
        $this->url = $url;
        $this->icon = $icon;
        $this->menu = [];
        $this->active = false;
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
        if ($level == 0) {
            $liClass = 'nav-item';
            if ($this->active) {
                $liClass .= ' active';
            }

            if (empty($this->menu)) {
                return '<li class="text-capitalize ' . $liClass . '"><a class="nav-link" href="' . $this->url . '">'
                    . $this->getHTMLIcon() . $this->title . "</a></li>\n";
            }

            $html = '<li class="text-capitalize ' . $liClass . ' dropdown">'
                . '<a class="nav-link dropdown-toggle" href="' . $this->url . '" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'
                . $this->getHTMLIcon() . $this->title . '</a>'
                . '<div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">';

            foreach ($this->menu as $menuItem) {
                $html .= $menuItem->getHTML($level + 1);
            }

            $html .= '</div></li>';
            return $html;
        }

        $liClass = 'dropdown-item';
        if ($this->active) {
            $liClass .= ' active';
        }

        $html = '<a class="' . $liClass . '" href="' . $this->url . '">' . $this->getHTMLIcon() . $this->title . '</a>';
        return $html;
    }
}
