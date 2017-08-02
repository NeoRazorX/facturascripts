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
namespace FacturaScripts\Core\Model;

/**
 * Elemento del menú de FacturaScripts, cada uno se corresponde con un controlador.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Page
{

    use Base\ModelTrait;

    /**
     * Clave primaria. Varchar (30).
     * Nombre de la página (controlador).
     * @var string
     */
    public $name;

    /**
     * Título de la página.
     * @var string
     */
    public $title;

    /**
     * Título de la opción de menú donde se visualiza
     * @var string
     */
    public $menu;

    /**
     * Título de la subopción de menú donde se visualiza (si usa 2 nivel)
     * @var string
     */
    public $submenu;

    /**
     * Indica si se visualiza en el menú
     * FALSE -> ocultar en el menú.
     * @var bool
     */
    public $showonmenu;

    /**
     * Posición donde se coloca en el menú
     * @var int
     */
    public $orden;

    /**
     * Icono de la página
     * @var string
     */
    public $icon;

    /**
     * Page constructor.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->init('fs_pages', 'name');
        if (empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->name = null;
        $this->title = null;
        $this->menu = null;
        $this->submenu = null;
        $this->icon = null;
        $this->showonmenu = true;
        $this->orden = 100;
    }

    /**
     * Devuelve la url donde ver/modificar estos datos
     * @return string
     */
    public function url()
    {
        return 'index.php?page=' . $this->name;
    }

    /**
     * TODO
     * @return bool
     */
    public function isDefault()
    {
        return ($this->name === $this->defaultItems->defaultPage());
    }

    /**
     * TODO
     * @return bool
     */
    public function showing()
    {
        return ($this->name === $this->defaultItems->showingPage());
    }
}
