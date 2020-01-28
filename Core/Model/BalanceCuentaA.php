<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Abbreviated detail of a balance.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class BalanceCuentaA extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Balance code.
     *
     * @var string
     */
    public $codbalance;

    /**
     * Account code.
     *
     * @var string
     */
    public $codcuenta;

    /**
     * Description of the account.
     *
     * @var string
     */
    public $desccuenta;

    /**
     * Primary key.
     *
     * @var int
     */
    public $id;

    /**
     * Obtain all balances from the account by your balance code.
     *
     * @param string $cod
     *
     * @return static[]
     */
    public function allFromCodbalance($cod)
    {
        $where = [new DataBaseWhere('codbalance', $cod)];
        return $this->all($where, ['codcuenta' => 'ASC'], 0, 0);
    }

    /**
     * 
     * @return string
     */
    public function install()
    {
        /// needed dependency
        new Balance();

        return parent::install();
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'id';
    }

    /**
     * Returns the balance of an exercise.
     *
     * @param ejercicio $ejercicio
     * @param bool      $desde
     * @param bool      $hasta
     *
     * @return float|int
     */
    public function saldo(&$ejercicio, $desde = false, $hasta = false)
    {
        $extra = '';
        if (!empty($ejercicio->idasientopyg)) {
            $extra = ' AND idasiento != ' . self::$dataBase->var2str($ejercicio->idasientopyg);
            if ($ejercicio->idasientocierre !== null) {
                $extra = ' AND idasiento NOT IN (' . self::$dataBase->var2str($ejercicio->idasientocierre)
                    . ', ' . self::$dataBase->var2str($ejercicio->idasientopyg) . ')';
            }
        } elseif (!empty($ejercicio->idasientocierre)) {
            $extra = ' AND idasiento != ' . self::$dataBase->var2str($ejercicio->idasientocierre);
        }

        if ($desde && $hasta) {
            $extra .= ' AND idasiento IN (SELECT idasiento FROM asientos WHERE '
                . 'fecha >= ' . self::$dataBase->var2str($desde) . ' AND '
                . 'fecha <= ' . self::$dataBase->var2str($hasta) . ')';
        }

        if ($this->codcuenta === '129') {
            $sql = "SELECT SUM(debe) AS debe, SUM(haber) AS haber FROM partidas
            WHERE idsubcuenta IN (SELECT idsubcuenta FROM co_subcuentas
              WHERE (codcuenta LIKE '6%' OR codcuenta LIKE '7%')
                AND codejercicio = " . self::$dataBase->var2str($ejercicio->codejercicio) . ')' . $extra . ';';
            $data = self::$dataBase->select($sql);
        } else {
            $sql = "SELECT SUM(debe) AS debe, SUM(haber) AS haber FROM partidas
            WHERE idsubcuenta IN (SELECT idsubcuenta FROM co_subcuentas
               WHERE codcuenta LIKE '" . $this->toolBox()->utils()->noHtml($this->codcuenta) . "%'"
                . ' AND codejercicio = ' . self::$dataBase->var2str($ejercicio->codejercicio) . ')' . $extra . ';';
            $data = self::$dataBase->select($sql);
        }

        if (!empty($data)) {
            return (float) $data[0]['haber'] - (float) $data[0]['debe'];
        }

        return 0;
    }

    /**
     * Search all balances of the account by its balance code.
     *
     * @param string $cod
     *
     * @return static[]
     */
    public function searchByCodbalance($cod)
    {
        $balist = [];
        $sql = 'SELECT * FROM ' . static::tableName()
            . " WHERE codbalance LIKE '" . $this->toolBox()->utils()->noHtml($cod) . "%' ORDER BY codcuenta ASC;";

        foreach (self::$dataBase->select($sql) as $row) {
            $balist[] = new static($row);
        }

        return $balist;
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'balancescuentasabreviadas';
    }

    /**
     * 
     * @return bool
     */
    public function test()
    {
        $this->desccuenta = $this->toolBox()->utils()->noHtml($this->desccuenta);
        return parent::test();
    }
}
