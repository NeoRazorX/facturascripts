<?php

/*
 * This file is part of facturacion_base
 * Copyright (C) 2015-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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
 * Un atributo para artículos.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class Atributo
{
    use Model;

    /**
     * Clave primaria.
     * @var type 
     */
    public $codatributo;
    public $nombre;

    public function __construct(array $data = []) 
    {
        $this->init(__CLASS__, 'atributos', 'codatributo');
        if (!empty($data)) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }
    
    public function clear()
    {
        $this->codatributo = NULL;
        $this->nombre = NULL;
    }

    protected function install() {
        return '';
    }

    public function url() {
        return 'index.php?page=ventas_atributos&cod=' . urlencode($this->codatributo);
    }

    public function valores() {
        $valor0 = new \atributo_valor();
        return $valor0->all_from_atributo($this->codatributo);
    }

    public function get($cod) {
        $data = self::$dataBase->select("SELECT * FROM atributos WHERE codatributo = " . $this->var2str($cod) . ";");
        if ($data) {
            return new \atributo($data[0]);
        }

        return FALSE;
    }

    public function get_by_nombre($nombre, $minusculas = FALSE) {
        if ($minusculas) {
            $data = self::$dataBase->select("SELECT * FROM atributos WHERE lower(nombre) = " . $this->var2str(mb_strtolower($nombre, 'UTF8')) . ";");
        } else {
            $data = self::$dataBase->select("SELECT * FROM atributos WHERE nombre = " . $this->var2str($nombre) . ";");
        }

        if ($data) {
            return new \atributo($data[0]);
        }

        return FALSE;
    }

    public function exists() {
        if (is_null($this->codatributo)) {
            return FALSE;
        }

        return self::$dataBase->select("SELECT * FROM atributos WHERE codatributo = " . $this->var2str($this->codatributo) . ";");
    }

    public function save() {
        $this->nombre = $this->no_html($this->nombre);

        if ($this->exists()) {
            $sql = "UPDATE atributos SET nombre = " . $this->var2str($this->nombre)
                    . " WHERE codatributo = " . $this->var2str($this->codatributo) . ";";
        } else {
            $sql = "INSERT INTO atributos (codatributo,nombre) VALUES "
                    . "(" . $this->var2str($this->codatributo)
                    . "," . $this->var2str($this->nombre) . ");";
        }

        return self::$dataBase->exec($sql);
    }

    public function delete() {
        return self::$dataBase->exec("DELETE FROM atributos WHERE codatributo = " . $this->var2str($this->codatributo) . ";");
    }

    public function all() {
        $lista = array();

        $data = self::$dataBase->select("SELECT * FROM atributos ORDER BY nombre DESC;");
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new \atributo($d);
            }
        }

        return $lista;
    }

}
