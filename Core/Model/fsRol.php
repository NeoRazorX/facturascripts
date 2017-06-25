<?php

/*
 * This file is part of FacturaScripts
 * Copyright (C) 2016 Joe Nilson             <joenilson at gmail.com>
 * Copyright (C) 2017 Carlos García Gómez    <neorazorx at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
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

use FacturaScripts\Core\Model\fsRolAccess;
use FacturaScripts\Core\Model\fsRolUser;
/**
 * Define un paquete de permisos para asignar rápidamente a usuarios.
 *
 * @author Joe Nilson            <joenilson at gmail.com>
 * @author Carlos García Gómez   <neorazorx at gmail.com>
 */
class fsRol  extends \FacturaScripts\Core\Base\Model  {

    public $codrol;
    public $descripcion;

    public function __construct($t = FALSE) {
        parent::__construct('fs_roles');
        if ($t) {
            $this->codrol = $t['codrol'];
            $this->descripcion = $t['descripcion'];
        } else {
            $this->codrol = NULL;
            $this->descripcion = NULL;
        }
    }

    protected function install() {
        return '';
    }

    public function url() {
        if (is_null($this->codrol)) {
            return 'index.php?page=admin_rol';
        } else
            return 'index.php?page=admin_rol&codrol=' . $this->codrol;
    }

    public function get($codrol) {
        $data = $this->dataBase->select("SELECT * FROM " . $this->tableName . " WHERE codrol = " . $this->var2str($codrol) . ";");
        if ($data) {
            return new fsRol($data[0]);
        } else {
            return FALSE;
        }
    }

    /**
     * Devuelve la lista de accesos permitidos del rol.
     * @return type
     */
    public function get_accesses() {
        $access = new fsRolAccess();
        return $access->allFromRol($this->codrol);
    }

    /**
     * Devuelve la lista de usuarios con este rol.
     * @return type
     */
    public function get_users() {
        $ru = new fsRolUser();
        return $ru->allFromRol($this->codrol);
    }

    public function exists() {
        if (is_null($this->codrol)) {
            return FALSE;
        } else {
            return $this->dataBase->select("SELECT * FROM " . $this->tableName . " WHERE codrol = " . $this->var2str($this->codrol) . ";");
        }
    }

    public function save() {
        $this->descripcion = $this->noHtml($this->descripcion);

        if ($this->exists()) {
            $sql = "UPDATE " . $this->tableName . " SET descripcion = " . $this->var2str($this->descripcion)
                    . " WHERE codrol = " . $this->var2str($this->codrol) . ";";
        } else {
            $sql = "INSERT INTO " . $this->tableName . " (codrol,descripcion) VALUES "
                    . "(" . $this->var2str($this->codrol)
                    . "," . $this->var2str($this->descripcion) . ");";
        }

        return $this->dataBase->exec($sql);
    }

    public function delete() {
        $sql = "DELETE FROM " . $this->tableName . " WHERE codrol = " . $this->var2str($this->codrol) . ";";
        return $this->dataBase->exec($sql);
    }

    public function all() {
        $lista = array();

        $sql = "SELECT * FROM " . $this->tableName . " ORDER BY descripcion ASC;";
        $data = $this->dataBase->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new fsRol($d);
            }
        }

        return $lista;
    }

    public function allForUser($nick) {
        $lista = array();

        $sql = "SELECT * FROM " . $this->tableName . " WHERE codrol IN "
                . "(SELECT codrol FROM fs_roles_users WHERE fs_user = " . $this->var2str($nick) . ");";
        $data = $this->dataBase->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new fsRol($d);
            }
        }

        return $lista;
    }

    public function clear() {
        
    }

}
