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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Utils;

/**
 * Detail level of an accounting plan. It is related to a single account.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class Subcuenta extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idsubcuenta;

    /**
     * Sub-account code.
     *
     * @var string
     */
    public $codsubcuenta;

    /**
     * Description of the subaccount.
     *
     * @var string
     */
    public $descripcion;

    /**
     * ID of the account to which it belongs.
     *
     * @var int
     */
    public $idcuenta;

    /**
     * Account code.
     *
     * @var string
     */
    public $codcuenta;

    /**
     * Exercise code.
     *
     * @var string
     */
    public $codejercicio;

    /**
     * Tax code.
     *
     * @var string
     */
    public $codimpuesto;

    /**
     * Amount of credit.
     *
     * @var float|int
     */
    public $haber;

    /**
     * Amount of the debit.
     *
     * @var float|int
     */
    public $debe;

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
        return 'subcuentas';
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
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        new Cuenta();
        return '';
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
     * Load de ID for account
     *
     * @return int
     */
    private function getIdAccount(): int
    {
        $where = [
            new DataBaseWhere('codejercicio', $this->codejercicio),
            new DataBaseWhere('codcuenta', $this->codcuenta),
        ];

        $account = new Cuenta();
        $account->loadFromCode(null, $where);
        return $account->idcuenta;
    }

    /**
     * Check if exists error in data of account
     *
     * @return bool
     */
    private function testErrorInData(): bool
    {
        return empty($this->codcuenta) || empty($this->codsubcuenta) || empty($this->descripcion) || empty($this->codejercicio);
    }

    /**
     * Check if exists error in long of subaccount
     *
     * @return bool
     */
    private function testErrorInLengthSubAccount(): bool
    {
        $exercise = new Ejercicio();
        $exercise->loadFromCode($this->codejercicio);
        return empty($exercise->codejercicio) || (strlen($this->codsubcuenta) <> $exercise->longsubcuenta);
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->codcuenta = trim($this->codcuenta);
        $this->codsubcuenta = trim($this->codsubcuenta);
        $this->descripcion = Utils::noHtml($this->descripcion);

        if ($this->testErrorInData()) {
            self::$miniLog->alert(self::$i18n->trans('account-data-missing'));
            return false;
        }

        if ($this->testErrorInLengthSubAccount()) {
            self::$miniLog->alert(self::$i18n->trans('account-length-error'));
            return false;
        }

        $this->idcuenta = $this->getIdAccount();
        if (empty($this->idcuenta)) {
            self::$miniLog->alert(self::$i18n->trans('account-data-error'));
            return false;
        }

        return true;
    }

    /**
     * Update account balance
     *
     * @param string $date
     * @param float $debit
     * @param float $credit
     * @return bool
     */
    public function updateBalance($date, $debit, $credit): bool
    {
        $balance = $debit - $credit;
        $month = (int) date("n", strtotime($date));
        $detail = new SubcuentaSaldo();
        $detail->idsubcuenta = $this->idsubcuenta;

        $inTransaction = self::$dataBase->inTransaction();
        try {
            if ($inTransaction === false) {
                self::$dataBase->beginTransaction();
            }

            if (!$detail->updateBalance($month, $debit, $credit)) {
                return false;
            }

            $sql = 'UPDATE ' . static::tableName() . ' SET '
                . ' debe = debe + ' . $debit
                . ',haber = haber + ' . $credit
                . ',saldo = saldo + ' . $balance
                . ' WHERE idsubcuenta = ' . $this->idsubcuenta;
            self::$dataBase->exec($sql);
        } catch (Exception $e) {
            self::$miniLog->error($e->getMessage());
            if (!$inTransaction) {
                self::$dataBase->rollback();
            }
            return false;
        } finally {
            if (!$inTransaction && self::$dataBase->inTransaction()) {
                self::$dataBase->rollback();
                self::$miniLog->alert(self::$i18n->trans('update-account-balance-error'));
                return false;
            }
        }

        return true;
    }
}
