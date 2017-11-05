<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
     * Indica si está activado o no.
     *
     * @var bool
     */
    public $active;

    /**
     * Icono de la fuente Fontawesome de la opción de menú.
     *
     * @var string
     */
    public $icon;

    /**
     * Lista de opciones de menú para el item.
     *
     * @var MenuItem[]
     */
    public $menu;

    /**
     * Nombre identificativo del elemento.
     *
     * @var string
     */
    public $name;

    /**
     * Título de la opción de menú.
     *
     * @var string
     */
    public $title;

    /**
     * URL para el href de la opción de menú.
     *
     * @var string
     */
    public $url;

    /**
     * Contruye y rellena los valores principales del Item
     *
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
     *
     * @return string
     */
    private function getHTMLIcon()
    {
        return empty($this->icon) ? '<i class="fa fa-fw" aria-hidden="true"></i> ' : '<i class="fa ' . $this->icon . ' fa-fw" aria-hidden="true"></i> ';
    }

    /**
     * Devuelve el indintificador del menu
     *
     * @param string $parent
     * @return string
     */
    private function getMenuId($parent)
    {
        return empty($parent) ? 'menu-' . $this->title : $parent . $this->title;
    }

    /**
     * Devuelve el html para el menú / submenú
     * @param string $parent
     * @return string
     */
    public function getHTML($parent = '')
    {
        $active = $this->active ? ' active' : '';
        $menuId = $this->getMenuId($parent);

        $html = empty($parent) ? '<li class="nav-item dropdown' . $active . '">'
            . '<a class="nav-link dropdown-toggle" href="#" id="' . $menuId . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">&nbsp; ' . \ucfirst($this->title) . '</a>'
            . '<ul class="dropdown-menu" aria-labelledby="' . $menuId . '">' : '<li class="dropdown-submenu">'
            . '<a class="dropdown-item" href="#" id="' . $menuId . '"><i class="fa fa-folder-open fa-fw" aria-hidden="true"></i>&nbsp; ' . \ucfirst($this->title) . '</a>'
            . '<ul class="dropdown-menu" aria-labelledby="' . $menuId . '">';

        foreach ($this->menu as $menuItem) {
            $extraClass = '';
            if ($menuItem->active) {
                $extraClass = 'active';
            }

            $html .= empty($menuItem->menu) ? '<li><a class="dropdown-item ' . $extraClass . '" href="' . $menuItem->url . '">'
                . $menuItem->getHTMLIcon() . '&nbsp; ' . \ucfirst($menuItem->title) . '</a></li>' : $menuItem->getHTML($menuId);
        }

        $html .= '</ul>';
        return $html;
    }
}
