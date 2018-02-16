<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * Description of SubcuentaSaldo
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class SubcuentaSaldo extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var int
     */
    public $id;

    /**
     * Month
     *
     * @var int
     */
    public $mes;

    /**
     * ID of the subaccount to which it belongs.
     *
     * @var int
     */
    public $idsubcuenta;

    /**
     * ID of the account to which it belongs.
     *
     * @var int
     */
    public $idcuenta;

    /**
     * Exercise code.
     *
     * @var string
     */
    public $codejercicio;

    /**
     * Debit amount for the month.
     *
     * @var float|int
     */
    public $debe;

    /**
     * Credit amount for the month.
     *
     * @var float|int
     */
    public $haber;

    /**
     * Balance amount for the month.
     *
     * @var float|int
     */
    public $saldo;


    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'subcuentassaldos';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idsubcuenta';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();

        $this->debe = 0.0;
        $this->haber = 0.0;
        $this->saldo = 0.0;
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $account = new Subcuenta();
        if (!$account->loadFromCode($this->idsubcuenta)) {
            self::$miniLog->alert(self::$i18n->trans('missing-data-subaccount'));
            return false;
        }

        $this->codejercicio = $account->codejercicio;
        $this->idcuenta = $account->idcuenta;
        $this->saldo = $this->debe - $this->haber;
        return true;
    }

    /**
     * Update account balance for a month
     *
     * @param int $month
     * @param float $debit
     * @param float $credit
     * @return bool
     */
    public function updateBalance($month, $debit, $credit): bool
    {
        $balance = $debit - $credit;
        $sql = 'UPDATE ' . static::tableName() . ' SET '
            . ' debe = debe + ' . $debit
            . ',haber = haber + ' . $credit
            . ',saldo = saldo + ' . $balance
            . ' WHERE idsubcuenta = ' . $this->idsubcuenta
            . ' AND mes = ' . $month;

        return self::$dataBase->exec($sql);
    }
}
