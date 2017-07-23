<?php
/**
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
class Caja
{
    use Model;

    /**
     * UN array con todos los agentes utilizados, para agilizar la carga.
     * @var array
     */
    private static $agentes;
    /**
     * Clave primaria.
     * @var
     */
    public $id;
    /**
     * Identificador del terminal. En la tabla cajas_terminales.
     * @var
     */
    public $fs_id;
    /**
     * Codigo del agente que abre y usa la caja.
     * El agente asociado al usuario.
     * @var
     */
    public $codagente;
    /**
     * Fecha de apertura (inicio) de la caja.
     * @var
     */
    public $fecha_inicial;
    /**
     * TODO
     * @var
     */
    public $dinero_inicial;
    /**
     * TODO
     * @var
     */
    public $fecha_fin;
    /**
     * TODO
     * @var
     */
    public $dinero_fin;
    /**
     * Numero de tickets emitidos en esta caja.
     * @var
     */
    public $tickets;
    /**
     * Ultima IP del usuario de la caja.
     * @var
     */
    public $ip;
    /**
     * El objeto agente asignado.
     * @var
     */
    public $agente;

    /**
     * caja constructor.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->init(__CLASS__, 'cajas', 'id');
        $this->clear();
        if (is_array($data) && !empty($data)) {
            $this->loadFromData($data);
        }
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->id = null;
        $this->fs_id = null;
        $this->codagente = null;
        $this->fecha_inicial = date('d-m-Y H:i:s');
        $this->dinero_inicial = 0;
        $this->fecha_fin = null;
        $this->dinero_fin = 0;
        $this->tickets = 0;

        $this->ip = null;
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $this->ip = $_SERVER['REMOTE_ADDR'];
        }

        $this->agente = null;
    }

    /**
     * TODO
     * @return bool
     */
    public function abierta()
    {
        return $this->fecha_fin === null;
    }

    /**
     * TODO
     * @return string
     */
    public function showFechaFin()
    {
        if ($this->fecha_fin === null) {
            return '-';
        }
        return $this->fecha_fin;
    }

    /**
     * TODO
     * @return mixed
     */
    public function diferencia()
    {
        return ($this->dinero_fin - $this->dinero_inicial);
    }

    /**
     * TODO
     *
     * @param string $codagente
     * @param int $offset
     * @param int $limit
     *
     * @return array
     */
    public function allByAgente($codagente, $offset = 0, $limit = FS_ITEM_LIMIT)
    {
        $cajalist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codagente = '
            . $this->var2str($codagente) . ' ORDER BY id DESC';

        $data = $this->database->selectLimit($sql, $limit, $offset);
        if (!empty($data)) {
            foreach ($data as $c) {
                $cajalist[] = new Caja($c);
            }
        }

        return $cajalist;
    }
}
