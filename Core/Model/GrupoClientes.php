<?php

/*
 * This file is part of facturacion_base
 * Copyright (C) 2014-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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
 * Un grupo de clientes, que puede estar asociado a una tarifa.
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class GrupoClientes
{
    use Model;

    /**
     * Clave primaria
     * @var type 
     */
    public $codgrupo;

    /**
     * Nombre del grupo
     * @var type 
     */
    public $nombre;

    /**
     * Código de la tarifa asociada, si la hay
     * @var type 
     */
    public $codtarifa;

    public function __construct(array $data = []) 
	{
        $this->init(__CLASS__, 'gruposclientes', 'codgrupo');
        if (!empty($data)) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }
	
    public function clear()
    {
        $this->codgrupo = NULL;
        $this->nombre = NULL;
        $this->codtarifa = NULL;
    }

    protected function install() {
        /// como hay una clave ajena a tarifas, tenemos que comprobar esa tabla antes
        //new \tarifa();

        return '';
    }

    /**
     * Devuelve la url donde ver/modificar los datos
     * @return string
     */
    public function url() {
        if ($this->codgrupo == NULL) {
            return 'index.php?page=ventas_clientes#grupos';
        }

        return 'index.php?page=ventas_grupo&cod=' . urlencode($this->codgrupo);
    }

    /**
     * Devuelve un nuevo código para un nuevo grupo de clientes
     * @return string
     */
    public function get_new_codigo() {
        if (strtolower(FS_DB_TYPE) == 'postgresql') {
            $sql = "SELECT codgrupo from " . $this->table_name . " where codgrupo ~ '^\d+$'"
                    . " ORDER BY codgrupo::integer DESC";
        } else {
            $sql = "SELECT codgrupo from " . $this->table_name . " where codgrupo REGEXP '^[0-9]+$'"
                    . " ORDER BY CAST(`codgrupo` AS decimal) DESC";
        }

        $data = self::$dataBase->select_limit($sql, 1, 0);
        if ($data) {
            return sprintf('%06s', (1 + intval($data[0]['codgrupo'])));
        }

        return '000001';
    }

    public function get($cod) {
        $data = self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE codgrupo = " . $this->var2str($cod) . ";");
        if ($data) {
            return new \grupo_clientes($data[0]);
        }

        return FALSE;
    }

    public function exists() {
        if (is_null($this->codgrupo)) {
            return FALSE;
        }

        return self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE codgrupo = " . $this->var2str($this->codgrupo) . ";");
    }

    public function save() {
        $this->nombre = $this->no_html($this->nombre);

        if ($this->exists()) {
            $sql = "UPDATE " . $this->table_name . " SET nombre = " . $this->var2str($this->nombre)
                    . ", codtarifa = " . $this->var2str($this->codtarifa)
                    . "  WHERE codgrupo = " . $this->var2str($this->codgrupo) . ";";
        } else {
            $sql = "INSERT INTO " . $this->table_name . " (codgrupo,nombre,codtarifa) VALUES "
                    . "(" . $this->var2str($this->codgrupo)
                    . "," . $this->var2str($this->nombre)
                    . "," . $this->var2str($this->codtarifa) . ");";
        }

        return self::$dataBase->exec($sql);
    }

    public function delete() {
        return self::$dataBase->exec("DELETE FROM " . $this->table_name . " WHERE codgrupo = " . $this->var2str($this->codgrupo) . ";");
    }

    public function all() {
        $glist = array();

        $data = self::$dataBase->select("SELECT * FROM " . $this->table_name . " ORDER BY nombre ASC;");
        if ($data) {
            foreach ($data as $d) {
                $glist[] = new \grupo_clientes($d);
            }
        }

        return $glist;
    }

    /**
     * Devuelve todos los grupos con la tarifa $cod
     * @param type $cod
     * @return \grupo_clientes
     */
    public function all_with_tarifa($cod) {
        $glist = array();

        $data = self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE codtarifa = " . $this->var2str($cod) . " ORDER BY codgrupo ASC;");
        if ($data) {
            foreach ($data as $d) {
                $glist[] = new \grupo_clientes($d);
            }
        }

        return $glist;
    }

}
