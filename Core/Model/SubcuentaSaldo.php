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
     * Balance amount for the month of January.
     *
     * @var float|int
     */
    public $saldo_1;

    /**
     * Balance amount for the month of February.
     *
     * @var float|int
     */
    public $saldo_2;

    /**
     * Balance amount for the month of March.
     *
     * @var float|int
     */
    public $saldo_3;

    /**
     * Balance amount for the month of April.
     *
     * @var float|int
     */
    public $saldo_4;

    /**
     * Balance amount for the month of May.
     *
     * @var float|int
     */
    public $saldo_5;

    /**
     * Balance amount for the month of June.
     *
     * @var float|int
     */
    public $saldo_6;

    /**
     * Balance amount for the month of July.
     *
     * @var float|int
     */
    public $saldo_7;

    /**
     * Balance amount for the month of August.
     *
     * @var float|int
     */
    public $saldo_8;

    /**
     * Balance amount for the month of September.
     *
     * @var float|int
     */
    public $saldo_9;

    /**
     * Balance amount for the month of October.
     *
     * @var float|int
     */
    public $saldo_10;

    /**
     * Balance amount for the month of November.
     *
     * @var float|int
     */
    public $saldo_11;

    /**
     * Balance amount for the month of December.
     *
     * @var float|int
     */
    public $saldo_12;

    /**
     * Balance amount.
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
        return 'co_subcuentas_saldos';
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

        $this->saldo_1 = 0.0;
        $this->saldo_2 = 0.0;
        $this->saldo_3 = 0.0;
        $this->saldo_4 = 0.0;
        $this->saldo_5 = 0.0;
        $this->saldo_6 = 0.0;
        $this->saldo_7 = 0.0;
        $this->saldo_8 = 0.0;
        $this->saldo_9 = 0.0;
        $this->saldo_10 = 0.0;
        $this->saldo_11 = 0.0;
        $this->saldo_12 = 0.0;
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
        $this->saldo = 0.0;
        for ($i = 1; $i < 13; ++$i) {
            $field = 'saldo_' . strval($i);
            $this->saldo += $this->{$field};
        }
        return true;
    }
}
