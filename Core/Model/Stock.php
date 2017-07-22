<?php

/*
 * This file is part of facturacion_base
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

use FacturaScripts\Core\Base\Model;

/**
 * La cantidad en inventario de un artículo en un almacén concreto.
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class Stock
{
    use Model;

    /**
     * Clave primaria.
     * @var type 
     */
    public $idstock;
    public $codalmacen;
    public $referencia;
    public $nombre;
    public $cantidad;
    public $reservada;
    public $disponible;
    public $pterecibir;
    public $stockmin;
    public $stockmax;
    public $cantidadultreg;
    public $ubicacion;

    public function __construct(array $data = []) 
    {
        $this->init(__CLASS__, 'stocks', 'idstock');
        if (!empty($data)) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }
	
    public function clear() {
        $this->idstock = NULL;
        $this->codalmacen = NULL;
        $this->referencia = NULL;
        $this->nombre = '';
        $this->cantidad = 0;
        $this->reservada = 0;
        $this->disponible = 0;
        $this->pterecibir = 0;
        $this->stockmin = 0;
        $this->stockmax = 0;
        $this->cantidadultreg = 0;
        $this->ubicacion = NULL;
    }

    protected function install() {
        /**
         * La tabla stocks tiene claves ajenas a artículos y almacenes,
         * por eso creamos un objeto de cada uno, para forzar la comprobación
         * de las tablas.
         */
        //new \almacen();
        //new \articulo();

        return '';
    }

    public function nombre() {
        $al0 = new \almacen();
        $almacen = $al0->get($this->codalmacen);
        if ($almacen) {
            $this->nombre = $almacen->nombre;
        }

        return $this->nombre;
    }

    public function set_cantidad($c = 0) {
        $this->cantidad = floatval($c);

        if ($this->cantidad < 0 AND !FS_STOCK_NEGATIVO) {
            $this->cantidad = 0;
        }

        $this->disponible = $this->cantidad - $this->reservada;
    }

    public function sum_cantidad($c = 0) {
        /// convertimos a flot por si acaso nos ha llegado un string
        $this->cantidad += floatval($c);

        if ($this->cantidad < 0 AND !FS_STOCK_NEGATIVO) {
            $this->cantidad = 0;
        }

        $this->disponible = $this->cantidad - $this->reservada;
    }

    public function get($id) {
        $data = self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE idstock = " . $this->var2str($id) . ";");
        if ($data) {
            return new \stock($data[0]);
        } else {
                    return FALSE;
        }
    }

    public function get_by_referencia($ref, $codalmacen = FALSE) {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE referencia = " . $this->var2str($ref) . ";";
        if ($codalmacen) {
            $sql = "SELECT * FROM " . $this->table_name . " WHERE referencia = " . $this->var2str($ref)
                    . ", codalmacen = " . $this->var2str($codalmacen) . ";";
        }

        $data = self::$dataBase->select($sql);
        if ($data) {
            return new \stock($data[0]);
        } else {
                    return FALSE;
        }
    }

    public function exists() {
        if (is_null($this->idstock)) {
            return FALSE;
        } else {
                    return self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE idstock = " . $this->var2str($this->idstock) . ";");
        }
    }

    public function save() {
        $this->cantidad = round($this->cantidad, 3);
        $this->reservada = round($this->reservada, 3);
        $this->disponible = $this->cantidad - $this->reservada;

        if ($this->exists()) {
            $sql = "UPDATE " . $this->table_name . " SET codalmacen = " . $this->var2str($this->codalmacen)
                    . ", referencia = " . $this->var2str($this->referencia)
                    . ", nombre = " . $this->var2str($this->nombre)
                    . ", cantidad = " . $this->var2str($this->cantidad)
                    . ", reservada = " . $this->var2str($this->reservada)
                    . ", disponible = " . $this->var2str($this->disponible)
                    . ", pterecibir = " . $this->var2str($this->pterecibir)
                    . ", stockmin = " . $this->var2str($this->stockmin)
                    . ", stockmax = " . $this->var2str($this->stockmax)
                    . ", cantidadultreg = " . $this->var2str($this->cantidadultreg)
                    . ", ubicacion = " . $this->var2str($this->ubicacion)
                    . "  WHERE idstock = " . $this->var2str($this->idstock) . ";";

            return self::$dataBase->exec($sql);
        } else {
            $sql = "INSERT INTO " . $this->table_name . " (codalmacen,referencia,nombre,cantidad,reservada,
            disponible,pterecibir,stockmin,stockmax,cantidadultreg,ubicacion) VALUES 
                   (" . $this->var2str($this->codalmacen)
                    . "," . $this->var2str($this->referencia)
                    . "," . $this->var2str($this->nombre)
                    . "," . $this->var2str($this->cantidad)
                    . "," . $this->var2str($this->reservada)
                    . "," . $this->var2str($this->disponible)
                    . "," . $this->var2str($this->pterecibir)
                    . "," . $this->var2str($this->stockmin)
                    . "," . $this->var2str($this->stockmax)
                    . "," . $this->var2str($this->cantidadultreg)
                    . "," . $this->var2str($this->ubicacion) . ");";

            if (self::$dataBase->exec($sql)) {
                $this->idstock = self::$dataBase->lastval();
                return TRUE;
            } else {
                            return FALSE;
            }
        }
    }

    public function delete() {
        return self::$dataBase->exec("DELETE FROM " . $this->table_name . " WHERE idstock = " . $this->var2str($this->idstock) . ";");
    }

    public function all_from_articulo($ref) {
        $stocklist = array();

        $data = self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE referencia = " . $this->var2str($ref) . " ORDER BY codalmacen ASC;");
        if ($data) {
            foreach ($data as $s) {
                $stocklist[] = new \stock($s);
            }
        }

        return $stocklist;
    }

    public function total_from_articulo($ref, $codalmacen = FALSE) {
        $num = 0;
        $sql = "SELECT SUM(cantidad) as total FROM " . $this->table_name . " WHERE referencia = " . $this->var2str($ref);

        if ($codalmacen) {
            $sql .= " AND codalmacen = " . $this->var2str($codalmacen);
        }

        $data = self::$dataBase->select($sql);
        if ($data) {
            $num = round(floatval($data[0]['total']), 3);
        }

        return $num;
    }

    public function count($column = 'idstock') {
        $num = 0;

        $data = self::$dataBase->select("SELECT COUNT(idstock) as total FROM " . $this->table_name . ";");
        if ($data) {
            $num = intval($data[0]['total']);
        }

        return $num;
    }

    public function count_by_articulo() {
        return $this->count('DISTINCT referencia');
    }

    /**
     * Aplicamos algunas correcciones a la tabla.
     */
    public function fix_db() {
        /**
         * Esta consulta produce un error si no hay datos erroneos, pero da igual
         */
        self::$dataBase->exec("DELETE FROM stocks s WHERE NOT EXISTS "
                . "(SELECT referencia FROM articulos a WHERE a.referencia = s.referencia);");
    }

}
