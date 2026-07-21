<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;

/**
 * Element of the menu of InvoiceScripts, each corresponds to a controller.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Page extends ModelClass
{
    use ModelTrait;

    /** @var string Icono utilizado para representar la página. */
    public $icon;

    /** @var string Título de la opción principal del menú donde se muestra la página. */
    public $menu;

    /** @var string Nombre del controlador asociado a la página. */
    public $name;

    /** @var int Posición de la página dentro del menú. */
    public $ordernum;

    /** @var bool Indica si la página se muestra en el menú. */
    public $showonmenu;

    /** @var string Título del submenú donde se muestra la página. */
    public $submenu;

    /** @var string Título de la página. */
    public $title;

    public function clear(): void
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
