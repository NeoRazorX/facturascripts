<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * Estructura para almacenar los datos de estado de una caja registradora (TPV).
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Caja
{

    use Base\ModelTrait;

    /**
     * UN array con todos los agentes utilizados, para agilizar la carga.
     *
     * @var Agente[]
     */
    private static $agentes;

    /**
     * Clave primaria.
     *
     * @var int
     */
    public $id;

    /**
     * Identificador del terminal. En la tabla cajas_terminales.
     *
     * @var int
     */
    public $fs_id;

    /**
     * Codigo del agente que abre y usa la caja.
     * El agente asociado al usuario.
     *
     * @var string
     */
    public $codagente;

    /**
     * Fecha de apertura (inicio) de la caja.
     *
     * @var string
     */
    public $fecha_inicial;

    /**
     * Dinero inicial en la caja
     *
     * @var float|int
     */
    public $dinero_inicial;

    /**
     * Fecha de cierre (fin) de la caja.
     *
     * @var string
     */
    public $fecha_fin;

    /**
     * Dinero final en la caja
     *
     * @var float|int
     */
    public $dinero_fin;

    /**
     * Numero de tickets emitidos en esta caja.
     *
     * @var int
     */
    public $tickets;

    /**
     * Ultima IP del usuario de la caja.
     *
     * @var string
     */
    public $ip;

    /**
     * El objeto agente asignado.
     *
     * @var Agente
     */
    public $agente;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'cajas';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'id';
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
     * Devuelve True si la caja está abierta, sino False
     *
     * @return bool
     */
    public function abierta()
    {
        return $this->fecha_fin === null;
    }

    /**
     * Muestra la fecha de fin
     *
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
     * Muestra la diferencia de dinero entre el cierre e inicio de caja
     *
     * @return mixed
     */
    public function diferencia()
    {
        return $this->dinero_fin - $this->dinero_inicial;
    }

    /**
     * Devuelve todas las cajas usadas por el agente
     *
     * @param string $codagente
     * @param int    $offset
     * @param int    $limit
     *
     * @return self[]
     */
    public function allByAgente($codagente, $offset = 0, $limit = FS_ITEM_LIMIT)
    {
        $cajalist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codagente = '
            . $this->var2str($codagente) . ' ORDER BY id DESC';

        $data = $this->dataBase->selectLimit($sql, $limit, $offset);
        if (!empty($data)) {
            foreach ($data as $c) {
                $cajalist[] = new self($c);
            }
        }

        return $cajalist;
    }
}
