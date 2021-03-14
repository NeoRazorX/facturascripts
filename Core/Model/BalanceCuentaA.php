<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\Cuenta;
use FacturaScripts\Dinamic\Model\Subcuenta;

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
     * 
     * @param string $codejercicio
     *
     * @return float
     */
    public function calculate($codejercicio): float
    {
        $debe = $haber = 0.00;
        $subaccounts = $this->getSubaccounts($this->codcuenta, $codejercicio);
        if (empty($subaccounts)) {
            return 0.00;
        }

        $sqlIN = '';
        foreach ($subaccounts as $subc) {
            $sqlIN .= empty($sqlIN) ? static::$dataBase->var2str($subc->idsubcuenta) : ',' . static::$dataBase->var2str($subc->idsubcuenta);
        }

        $sql = 'SELECT SUM(debe) AS debe, SUM(haber) AS haber FROM partidas WHERE idsubcuenta IN (' . $sqlIN . ');';
        foreach (static::$dataBase->select($sql) as $row) {
            $debe += (float) $row['debe'];
            $haber += (float) $row['haber'];
        }

        return $debe - $haber;
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

    /**
     * 
     * @param string $codcuenta
     * @param string $codejercicio
     *
     * @return Subcuenta[]
     */
    protected function getSubaccounts($codcuenta, $codejercicio)
    {
        $subaccounts = [];

        /// add related subaccounts
        $subcuentaModel = new Subcuenta();
        $where = [
            new DataBaseWhere('codcuenta', $codcuenta),
            new DataBaseWhere('codejercicio', $codejercicio)
        ];
        foreach ($subcuentaModel->all($where, [], 0, 0) as $subc) {
            $subaccounts[$subc->idsubcuenta] = $subc;
        }

        /// add related subaccounts from children accounts
        $cuentaModel = new Cuenta();
        $where2 = [
            new DataBaseWhere('codejercicio', $codejercicio),
            new DataBaseWhere('parent_codcuenta', $codcuenta)
        ];
        foreach ($cuentaModel->all($where2, [], 0, 0) as $cuenta) {
            foreach ($this->getSubaccounts($cuenta->codcuenta, $codejercicio) as $subc) {
                $subaccounts[$subc->idsubcuenta] = $subc;
            }
        }

        return $subaccounts;
    }
}
