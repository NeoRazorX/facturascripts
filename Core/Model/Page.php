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

use FacturaScripts\Core\Base\Model;

/**
 * Elemento del menú de FacturaScripts, cada uno se corresponde con un controlador.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Page
{

    use Model;

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
     * TODO
     * @var
     */
    public $menu;

    /**
     * TODO
     * @var
     */
    public $submenu;

    /**
     * FALSE -> ocultar en el menú.
     * @var bool
     */
    public $showonmenu;

    /**
     * TODO
     * @var
     */
    public $orden;

    /**
     * Page constructor.
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->init(__CLASS__, 'fs_pages', 'name');
        if (!empty($data)) {
            $this->loadFromData($data);
        } else {
            $this->clear();
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
        $this->showonmenu = true;
        $this->orden = 100;
    }

    /**
     * Esta función es llamada al crear la tabla del modelo. Devuelve el SQL
     * que se ejecutará tras la creación de la tabla. útil para insertar valores
     * por defecto.
     * @return string
     */
    protected function install()
    {
        return 'INSERT INTO ' . $this->tableName() . ' (name,title,menu,submenu,showonmenu)'
            . " VALUES ('AdminHome','Panel de control','admin',NULL,TRUE);";
    }

    /**
     * TODO
     * @return string
     */
    public function url()
    {
        if ($this->name === null) {
            return 'index.php?page=AdminHome';
        }

        return 'index.php?page=' . $this->name;
    }

    /**
     * TODO
     * @return bool
     */
    public function isDefault()
    {
        return ( $this->name === $this->defaultItems->defaultPage() );
    }

    /**
     * TODO
     * @return bool
     */
    public function showing()
    {
        return ( $this->name === $this->defaultItems->showingPage() );
    }
}
