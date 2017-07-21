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
 * Estructura para almacenar los datos de estado de una caja registradora (TPV).
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class caja
{
    use Model;

    /**
     * Clave primaria.
     * @var type 
     */
    public $id;

    /**
     * Identificador del terminal. En la tabla cajas_terminales.
     * @var type 
     */
    public $fs_id;

    /**
     * Codigo del agente que abre y usa la caja.
     * El agente asociado al usuario.
     * @var type 
     */
    public $codagente;

    /**
     * Fecha de apertura (inicio) de la caja.
     * @var type 
     */
    public $fecha_inicial;
    public $dinero_inicial;
    public $fecha_fin;
    public $dinero_fin;

    /**
     * Numero de tickets emitidos en esta caja.
     * @var type 
     */
    public $tickets;

    /**
     * Ultima IP del usuario de la caja.
     * @var type 
     */
    public $ip;

    /**
     * El objeto agente asignado.
     * @var type 
     */
    public $agente;

    /**
     * UN array con todos los agentes utilizados, para agilizar la carga.
     * @var type 
     */
    private static $agentes;

    public function __construct(array $data = []) 
	{
        $this->init(__CLASS__, 'cajas', 'id');
        if (!empty($data)) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }
    
    public function clear()
    {
        $this->id = NULL;
        $this->fs_id = NULL;
        $this->codagente = NULL;
        $this->fecha_inicial = Date('d-m-Y H:i:s');
        $this->dinero_inicial = 0;
        $this->fecha_fin = NULL;
        $this->dinero_fin = 0;
        $this->tickets = 0;

        $this->ip = NULL;
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $this->ip = $_SERVER['REMOTE_ADDR'];
        }

        $this->agente = NULL;
    }

    protected function install() {
        return '';
    }

    public function abierta() {
        return is_null($this->fecha_fin);
    }

    public function show_fecha_fin() {
        if (is_null($this->fecha_fin)) {
            return '-';
        } else
            return $this->fecha_fin;
    }

    public function diferencia() {
        return ($this->dinero_fin - $this->dinero_inicial);
    }

    public function exists() {
        if (is_null($this->id)) {
            return FALSE;
        } else
            return self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE id = " . $this->var2str($this->id) . ";");
    }

    public function get($id) {
        if (isset($id)) {
            $caja = self::$dataBase->select("SELECT * FROM " . $this->table_name . " WHERE id = " . $this->var2str($id) . ";");
            if ($caja) {
                return new \caja($caja[0]);
            } else
                return FALSE;
        } else
            return FALSE;
    }

    public function save() {
        if ($this->exists()) {
            $sql = "UPDATE " . $this->table_name . " SET fs_id = " . $this->var2str($this->fs_id)
                    . ", codagente = " . $this->var2str($this->codagente)
                    . ", ip = " . $this->var2str($this->ip)
                    . ", f_inicio = " . $this->var2str($this->fecha_inicial)
                    . ", d_inicio = " . $this->var2str($this->dinero_inicial)
                    . ", f_fin = " . $this->var2str($this->fecha_fin)
                    . ", d_fin = " . $this->var2str($this->dinero_fin)
                    . ", tickets = " . $this->var2str($this->tickets)
                    . "  WHERE id = " . $this->var2str($this->id) . ";";

            return self::$dataBase->exec($sql);
        } else {
            $sql = "INSERT INTO " . $this->table_name . " (fs_id,codagente,f_inicio,d_inicio,f_fin,d_fin,tickets,ip) VALUES
                   (" . $this->var2str($this->fs_id)
                    . "," . $this->var2str($this->codagente)
                    . "," . $this->var2str($this->fecha_inicial)
                    . "," . $this->var2str($this->dinero_inicial)
                    . "," . $this->var2str($this->fecha_fin)
                    . "," . $this->var2str($this->dinero_fin)
                    . "," . $this->var2str($this->tickets)
                    . "," . $this->var2str($this->ip) . ");";

            if (self::$dataBase->exec($sql)) {
                $this->id = self::$dataBase->lastval();
                return TRUE;
            } else
                return FALSE;
        }
    }

    public function delete() {
        return self::$dataBase->exec("DELETE FROM " . $this->table_name . " WHERE id = " . $this->var2str($this->id) . ";");
    }

    public function all($offset = 0, $limit = FS_ITEM_LIMIT) {
        $cajalist = array();

        $data = self::$dataBase->select_limit("SELECT * FROM " . $this->table_name . " ORDER BY id DESC", $limit, $offset);
        if ($data) {
            foreach ($data as $c) {
                $cajalist[] = new \caja($c);
            }
        }

        return $cajalist;
    }

    public function all_by_agente($codagente, $offset = 0, $limit = FS_ITEM_LIMIT) {
        $cajalist = array();
        $sql = "SELECT * FROM " . $this->table_name . " WHERE codagente = "
                . $this->var2str($codagente) . " ORDER BY id DESC";

        $data = self::$dataBase->select_limit($sql, $limit, $offset);
        if ($data) {
            foreach ($data as $c) {
                $cajalist[] = new \caja($c);
            }
        }

        return $cajalist;
    }

}
