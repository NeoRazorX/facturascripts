<?php
/**
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

/**
 * Detalle abreviado de un balance.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class BalanceCuentaA
{
    use Base\ModelTrait;

    /**
     * Clave primaria.
     * @var
     */
    public $id;
    /**
     * TODO
     * @var
     */
    public $codbalance;
    /**
     * TODO
     * @var
     */
    public $codcuenta;
    /**
     * TODO
     * @var
     */
    public $desccuenta;

    /**
     * BalanceCuentaA constructor.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->init(__CLASS__, 'co_cuentascbba', 'id');
        if (is_null($data) || empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
    }

    /**
     * Devuelve el saldo del balance de un ejercicio.
     *
     * @param ejercicio $ejercicio
     * @param bool $desde
     * @param bool $hasta
     *
     * @return float|int
     */
    public function saldo(&$ejercicio, $desde = false, $hasta = false)
    {
        $extra = '';
        if ($ejercicio->idasientopyg !== null) {
            $extra = ' AND idasiento != ' . $this->var2str($ejercicio->idasientopyg);
            if ($ejercicio->idasientocierre !== null) {
                $extra = ' AND idasiento NOT IN (' . $this->var2str($ejercicio->idasientocierre)
                    . ', ' . $this->var2str($ejercicio->idasientopyg) . ')';
            }
        } elseif ($ejercicio->idasientocierre !== null) {
            $extra = ' AND idasiento != ' . $this->var2str($ejercicio->idasientocierre);
        }

        if ($desde && $hasta) {
            $extra .= ' AND idasiento IN (SELECT idasiento FROM co_asientos WHERE '
                . 'fecha >= ' . $this->var2str($desde) . ' AND '
                . 'fecha <= ' . $this->var2str($hasta) . ')';
        }

        if ($this->codcuenta === '129') {
            $sql = "SELECT SUM(debe) AS debe, SUM(haber) AS haber FROM co_partidas
            WHERE idsubcuenta IN (SELECT idsubcuenta FROM co_subcuentas
              WHERE (codcuenta LIKE '6%' OR codcuenta LIKE '7%') 
                AND codejercicio = " . $this->var2str($ejercicio->codejercicio) . ')' . $extra . ';';
            $data = $this->dataBase->select($sql);
        } else {
            $sql = "SELECT SUM(debe) AS debe, SUM(haber) AS haber FROM co_partidas
            WHERE idsubcuenta IN (SELECT idsubcuenta FROM co_subcuentas
               WHERE codcuenta LIKE '" . static::noHtml($this->codcuenta) . "%'"
                . ' AND codejercicio = ' . $this->var2str($ejercicio->codejercicio) . ')' . $extra . ';';
            $data = $this->dataBase->select($sql);
        }

        if (!empty($data)) {
            return (float)$data[0]['haber'] - (float)$data[0]['debe'];
        }
        return 0;
    }

    /**
     * TODO
     *
     * @param string $cod
     *
     * @return array
     */
    public function allFromCodbalance($cod)
    {
        $balist = [];
        $sql = 'SELECT * FROM ' . $this->tableName()
            . ' WHERE codbalance = ' . $this->var2str($cod) . ' ORDER BY codcuenta ASC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $b) {
                $balist[] = new BalanceCuentaA($b);
            }
        }

        return $balist;
    }

    /**
     * TODO
     *
     * @param string $cod
     *
     * @return array
     */
    public function searchByCodbalance($cod)
    {
        $balist = [];
        $sql = 'SELECT * FROM ' . $this->tableName()
            . " WHERE codbalance LIKE '" . static::noHtml($cod) . "%' ORDER BY codcuenta ASC;";

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $b) {
                $balist[] = new BalanceCuentaA($b);
            }
        }

        return $balist;
    }
}
