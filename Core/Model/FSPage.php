<?php

/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class FSPage {

    use \FacturaScripts\Core\Base\Model;

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
    public $menu;
    public $submenu;

    /**
     * FALSE -> ocultar en el menú.
     * @var boolean
     */
    public $showonmenu;
    public $orden;

    public function __construct($data = FALSE) {
        $this->init('fs_pages', 'name');
        if ($data) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }

    public function clear() {
        $this->name = NULL;
        $this->title = NULL;
        $this->menu = NULL;
        $this->submenu = NULL;
        $this->showonmenu = TRUE;
        $this->orden = 100;
    }

    protected function install() {
        return "INSERT INTO " . $this->tableName() . " (name,title,menu,submenu,showonmenu)"
                . " VALUES ('AdminHome','Panel de control','admin',NULL,TRUE);";
    }

    public function url() {
        if (is_null($this->name)) {
            return 'index.php?page=AdminHome';
        }

        return 'index.php?page=' . $this->name;
    }

    public function isDefault() {
        return ( $this->name == $this->defaultItems->defaultPage() );
    }

    public function showing() {
        return ( $this->name == $this->defaultItems->showingPage() );
    }

    public function get($name) {
        $data = $this->dataBase->select("SELECT * FROM " . $this->tableName() . " WHERE name = " . $this->var2str($name) . ";");
        if ($data) {
            return new FSPage($data[0]);
        }

        return FALSE;
    }

    public function all() {
        $pagelist = [];
        $sql = "SELECT * FROM " . $this->tableName() . " ORDER BY lower(menu) ASC, orden ASC, lower(title) ASC;";

        $data = $this->dataBase->select($sql);
        if ($data) {
            foreach ($data as $p) {
                $pagelist[] = new FSPage($p);
            }
        }

        return $pagelist;
    }

}
