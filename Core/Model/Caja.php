<?php
/**
 * This file is part of FacturaScripts
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
 * Structure to store the status data of a cash register (POS).
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Caja
{

    use Base\ModelTrait;

    /**
     * AN array with all the agents used, to speed up the loading.
     *
     * @var Agente[]
     */
    private static $agentes;

    /**
     * Primary key.
     *
     * @var int
     */
    public $id;

    /**
     * Identifier of the terminal. In the table boxes_terminals.
     *
     * @var int
     */
    public $fs_id;

    /**
     * Agent code that opens and uses the box.
     * The agent associated with the user.
     *
     * @var string
     */
    public $codagente;

    /**
     * Opening date (start) of the box.
     *
     * @var string
     */
    public $fecha_inicial;

    /**
     * Initial money in the box.
     *
     * @var float|int
     */
    public $dinero_inicial;

    /**
     * Closing date (end) of the box.
     *
     * @var string
     */
    public $fecha_fin;

    /**
     * Final money in the box.
     *
     * @var float|int
     */
    public $dinero_fin;

    /**
     * Number of tickets issued in this box.
     *
     * @var int
     */
    public $tickets;

    /**
     * Last IP of the user of the box.
     *
     * @var string
     */
    public $ip;

    /**
     * The assigned agent object.
     *
     * @var Agente
     */
    public $agente;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'cajas';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'id';
    }

    /**
     * Reset the values of all model properties.
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
     * Returns True if the box is open, but False.
     *
     * @return bool
     */
    public function abierta()
    {
        return $this->fecha_fin === null;
    }

    /**
     * Shows the end date.
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
     * It shows the difference of money between the closing and beginning of cash.
     *
     * @return mixed
     */
    public function diferencia()
    {
        return $this->dinero_fin - $this->dinero_inicial;
    }

    /**
     * Returns all the boxes used by the agent.
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
        $sql = 'SELECT * FROM ' . static::tableName() . ' WHERE codagente = '
            . self::$dataBase->var2str($codagente) . ' ORDER BY id DESC';

        $data = self::$dataBase->selectLimit($sql, $limit, $offset);
        if (!empty($data)) {
            foreach ($data as $c) {
                $cajalist[] = new self($c);
            }
        }

        return $cajalist;
    }
}
