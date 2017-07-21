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
 * Regularización del stock de un almacén de un artículos en una fecha concreta.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class RegularizacionStock
{
    use Model;

    /**
     * Clave primaria.
     * @var type 
     */
    public $id;

    /**
     * ID del stock, en el modelo stock.
     * @var type 
     */
    public $idstock;
    public $cantidadini;
    public $cantidadfin;

    /**
     * Código del almacén destino.
     * @var type 
     */
    public $codalmacendest;
    public $fecha;
    public $hora;
    public $motivo;

    /**
     * Nick del usuario que ha realizado la regularización.
     * @var type 
     */
    public $nick;

    public function __construct(array $data = []) 
    {
        $this->init(__CLASS__, 'agenciastrans', 'codtrans');
        if (!empty($data)) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }
	
    public function clear()
    {
        $this->id = NULL;
        $this->idstock = NULL;
        $this->cantidadini = 0;
        $this->cantidadfin = 0;
        $this->codalmacendest = NULL;
        $this->fecha = date('d-m-Y');
        $this->hora = date('H:i:s');
        $this->motivo = '';
        $this->nick = NULL;
    }

    protected function install() {
        new \stock();

        return '';
    }

    public function get($id) {
        $data = self::$dataBase->select("SELECT * FROM lineasregstocks WHERE id = " . $this->var2str($id) . ";");
        if ($data) {
            return new \regularizacion_stock($data[0]);
        } else {
                    return FALSE;
        }
    }

    public function exists() {
        if (is_null($this->id)) {
            return FALSE;
        } else {
                    return self::$dataBase->select("SELECT * FROM lineasregstocks WHERE id = " . $this->var2str($this->id) . ";");
        }
    }

    public function save() {
        if ($this->exists()) {
            $sql = "UPDATE lineasregstocks SET idstock = " . $this->var2str($this->idstock)
                    . ", cantidadini = " . $this->var2str($this->cantidadini)
                    . ", cantidadfin = " . $this->var2str($this->cantidadfin)
                    . ", codalmacendest = " . $this->var2str($this->codalmacendest)
                    . ", fecha = " . $this->var2str($this->fecha)
                    . ", hora = " . $this->var2str($this->hora)
                    . ", motivo = " . $this->var2str($this->motivo)
                    . ", nick = " . $this->var2str($this->nick)
                    . "  WHERE id = " . $this->var2str($this->id) . ";";

            return self::$dataBase->exec($sql);
        } else {
            $sql = "INSERT INTO lineasregstocks (idstock,cantidadini,cantidadfin,
            codalmacendest,fecha,hora,motivo,nick)
            VALUES (" . $this->var2str($this->idstock)
                    . "," . $this->var2str($this->cantidadini)
                    . "," . $this->var2str($this->cantidadfin)
                    . "," . $this->var2str($this->codalmacendest)
                    . "," . $this->var2str($this->fecha)
                    . "," . $this->var2str($this->hora)
                    . "," . $this->var2str($this->motivo)
                    . "," . $this->var2str($this->nick) . ");";

            if (self::$dataBase->exec($sql)) {
                $this->id = self::$dataBase->lastval();
                return TRUE;
            } else {
                            return FALSE;
            }
        }
    }

    public function delete() {
        return self::$dataBase->exec("DELETE FROM lineasregstocks WHERE id = " . $this->var2str($this->id) . ";");
    }

    /**
     * Devuelve un array con todas las regularizaciones de un artículo.
     * @param type $ref
     * @param type $codalmacen
     * @param type $desde
     * @param type $hasta
     * @param integer $limit
     * @param integer $offset
     * @return \regularizacion_stock
     */
    public function all_from_articulo($ref, $codalmacen = '', $desde = '', $hasta = '', $limit = 1000, $offset = 0) {
        $rlist = array();
        $sql = "SELECT * FROM lineasregstocks WHERE idstock IN"
                . " (SELECT idstock FROM stocks WHERE referencia = " . $this->var2str($ref) . ")";
        if ($codalmacen) {
            $sql .= " AND codalmacendest = " . $this->var2str($codalmacen);
        }
        if ($desde) {
            $sql .= " AND fecha >= " . $this->var2str($desde);
        }
        if ($hasta) {
            $sql .= " AND fecha <= " . $this->var2str($hasta);
        }
        $sql .= " ORDER BY fecha DESC, hora DESC";

        $data = self::$dataBase->select_limit($sql, $limit, $offset);
        if ($data) {
            foreach ($data as $d) {
                $rlist[] = new \regularizacion_stock($d);
            }
        }

        return $rlist;
    }

    /**
     * Aplica algunas correcciones a la tabla.
     */
    public function fix_db() {
        self::$dataBase->exec("DELETE FROM " . $this->table_name . " WHERE idstock NOT IN (SELECT idstock FROM stocks);");
    }

}
