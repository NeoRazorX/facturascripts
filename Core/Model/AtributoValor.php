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
 * Un Valor para un atributo de artículos.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class AtributoValor
{
    use Model;

    /**
     * Clave primaria
     * @var type 
     */
    public $id;

    /**
     * Código del atributo relacionado.
     * @var type 
     */
    public $codatributo;
    public $valor;

    public function __construct(array $data = []) 
    {
        $this->init(__CLASS__, 'atributos_valores', 'id');
        if (!empty($data)) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }
    
    public function clear()
    {
        $this->id = NULL;
        $this->codatributo = NULL;
        $this->valor = NULL;
    }

    protected function install() {
        return '';
    }

    public function url() {
        return 'index.php?page=ventas_atributos&cod=' . $this->codatributo;
    }

    public function nombre() {
        $nombre = '';

        $data = self::$dataBase->select("SELECT * FROM atributos WHERE codatributo = " . $this->var2str($this->codatributo) . ";");
        if ($data) {
            $nombre = $data[0]['nombre'];
        }

        return $nombre;
    }

    public function get($id) {
        if ($id) {
            $data = self::$dataBase->select("SELECT * FROM atributos_valores WHERE id = " . $this->var2str($id) . ";");
            if ($data) {
                return new \atributo_valor($data[0]);
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }

    public function exists() {
        if (is_null($this->id)) {
            return FALSE;
        } else {
            return self::$dataBase->select("SELECT * FROM atributos_valores WHERE id = " . $this->var2str($this->id) . ";");
        }
    }

    public function save() {
        $this->valor = $this->no_html($this->valor);

        if ($this->exists()) {
            $sql = "UPDATE atributos_valores SET valor = " . $this->var2str($this->valor)
                    . ", codatributo = " . $this->var2str($this->codatributo)
                    . "  WHERE id = " . $this->var2str($this->id) . ";";
        } else {
            if (is_null($this->id)) {
                $this->id = 1;

                $data = self::$dataBase->select("SELECT MAX(id) as max FROM atributos_valores;");
                if ($data) {
                    $this->id = 1 + intval($data[0]['max']);
                }
            }

            $sql = "INSERT INTO atributos_valores (id,codatributo,valor) VALUES "
                    . "(" . $this->var2str($this->id)
                    . "," . $this->var2str($this->codatributo)
                    . "," . $this->var2str($this->valor) . ");";
        }

        return self::$dataBase->exec($sql);
    }

    public function delete() {
        return self::$dataBase->exec("DELETE FROM atributos_valores WHERE id = " . $this->var2str($this->id) . ";");
    }

    public function all() {
        $lista = array();

        $data = self::$dataBase->select("SELECT * FROM atributos_valores ORDER BY codatributo DESC;");
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new \atributo_valor($d);
            }
        }

        return $lista;
    }

    public function all_from_atributo($cod) {
        $lista = array();
        $sql = "SELECT * FROM atributos_valores WHERE codatributo = " . $this->var2str($cod)
                . " ORDER BY valor ASC;";

        $data = self::$dataBase->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new \atributo_valor($d);
            }
        }

        return $lista;
    }

}
