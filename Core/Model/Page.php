<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Tools;

/**
 * Element of the menu of InvoiceScripts, each corresponds to a controller.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Page extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Icon of the page.
     *
     * @var string
     */
    public $icon;

    /**
     * Title of the menu option where it is displayed.
     *
     * @var string
     */
    public $menu;

    /**
     * Primary key. Varchar (30).
     * Name of the page (controller).
     *
     * @var string
     */
    public $name;

    /**
     * Position where it is placed in the menu.
     *
     * @var int
     */
    public $ordernum;

    /**
     * Indicates if it is displayed in the menu.
     * False -> hide in the menu.
     *
     * @var bool
     */
    public $showonmenu;

    /**
     * Title of the menu sub-option where it is displayed (if it uses 2 levels).
     *
     * @var string
     */
    public $submenu;

    /**
     * Page title.
     *
     * @var string
     */
    public $title;

    public function clear()
    {
        parent::clear();
        $this->ordernum = 100;
        $this->showonmenu = true;
    }

    public function install(): string
    {
        return 'INSERT INTO ' . static::tableName() . " (name,title) VALUES ('Wizard','Wizard');";
    }

    public static function primaryColumn(): string
    {
        return 'name';
    }

    public static function tableName(): string
    {
        return 'pages';
    }

    public function test(): bool
    {
        // escapamos el html
        $this->icon = Tools::noHtml($this->icon);
        $this->menu = Tools::noHtml($this->menu);
        $this->name = Tools::noHtml($this->name);
        $this->submenu = Tools::noHtml($this->submenu);
        $this->title = Tools::noHtml($this->title);

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        return (string)$this->name;
    }
}
