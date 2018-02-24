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
namespace FacturaScripts\Core\Lib;

/**
 * Structure for each of the items in the FacturaScripts menu.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class MenuItem
{

    /**
     * Indicates whether it is activated or not.
     *
     * @var bool
     */
    public $active;

    /**
     * Fontawesome font icon of the menu option.
     *
     * @var string
     */
    public $icon;

    /**
     * List of menu options for the item.
     *
     * @var MenuItem[]
     */
    public $menu;

    /**
     * Identifying name of the element.
     *
     * @var string
     */
    public $name;

    /**
     * Title of the menu option.
     *
     * @var string
     */
    public $title;

    /**
     * URL for the href of the menu option.
     *
     * @var string
     */
    public $url;

    /**
     * Build and fill the main values of the Item.
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
     * Returns the HTML for the icon of the item.
     *
     * @return string
     */
    private function getHTMLIcon()
    {
        return empty($this->icon) ? '<i class="fa fa-fw" aria-hidden="true"></i> ' : '<i class="fa ' . $this->icon
            . ' fa-fw" aria-hidden="true"></i> ';
    }

    /**
     * Returns the indintifier of the menu.
     *
     * @param string $parent
     *
     * @return string
     */
    private function getMenuId($parent)
    {
        return empty($parent) ? 'menu-' . $this->title : $parent . $this->name;
    }

    /**
     * Returns the html for the menu / submenu.
     *
     * @param string $parent
     *
     * @return string
     */
    public function getHTML($parent = '')
    {
        $active = $this->active ? ' active' : '';
        $menuId = $this->getMenuId($parent);

        $html = empty($parent) ? '<li class="nav-item dropdown' . $active . '">'
            . '<a class="nav-link dropdown-toggle" href="#" id="' . $menuId . '" data-toggle="dropdown"'
            . ' aria-haspopup="true" aria-expanded="false">&nbsp; ' . \ucfirst($this->title) . '</a>'
            . '<ul class="dropdown-menu" aria-labelledby="' . $menuId . '">' : '<li class="dropdown-submenu">'
            . '<a class="dropdown-item' . $active . '" href="#" id="' . $menuId . '"><i class="fa fa-folder-open fa-fw"'
            . ' aria-hidden="true"></i> &nbsp; ' . \ucfirst($this->title) . '</a>'
            . '<ul class="dropdown-menu" aria-labelledby="' . $menuId . '">';

        foreach ($this->menu as $menuItem) {
            $extraClass = $menuItem->active ? ' active' : '';
            $html .= empty($menuItem->menu) ? '<li><a class="dropdown-item' . $extraClass . '" href="' . $menuItem->url
                . '">' . $menuItem->getHTMLIcon() . '&nbsp; ' . \ucfirst($menuItem->title)
                . '</a></li>' : $menuItem->getHTML($menuId);
        }

        $html .= '</ul>';

        return $html;
    }
}
