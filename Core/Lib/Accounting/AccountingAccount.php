<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib\Accounting;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Cuenta;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\GrupoClientes;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Dinamic\Model\Subcuenta;

/**
 * Class for calculate/obtain accounting sub-account of:
 * (Respecting the additional levels)
 *
 *   - Customer
 *   - Customer Group
 *   - Supplier
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class AccountingAccounts
{

    const SPECIAL_CUSTOMER_ACCOUNT = 'CLIENT';
    const SPECIAL_SUPPLIER_ACCOUNT = 'PROVEE';

    /**
     *
     * @var Ejercicio
     */
    protected $exercise;

    /**
     * Class constructor
     *
     * @param string $exercise
     */
    public function __construct()
    {
        $this->exercise = new Ejercicio();
        $this->exercise->loadFromDate(date('d-m-Y'), false);
    }

    /**
     * Get the accounting sub-account for the customer and the fiscal year.
     *   - First check the customer data card
     *   - Second check the customer group
     *   - Third search for the sub-account classified as a special account for customers
     *   - Fourth search for the general account classified as a special account for customers
     *     and then search for a sub account belonging to the account
     *
     * @param string $code
     * @param string $specialAccount
     *
     * @return Subcuenta
     */
    public function getCustomerAccount(string $code, string $specialAccount = self::SPECIAL_CUSTOMER_ACCOUNT)
    {
        $customer = new Cliente();
        if ($customer->loadFromCode($code) && !empty($customer->codsubcuenta)) {
            return $this->getCustomerGroupAccount($customer->codgrupo, $specialAccount);
        }

        return $this->getSpecialSubAccount($specialAccount);
    }

    /**
     * Get the accounting sub-account for the group's customer and the fiscal year.
     *   - First check the group's customer data card
     *   - Second search for the sub-account classified as a special account for customers
     *   - Third search for the general account classified as a special account for customers
     *     and then search for a sub account belonging to the account
     *
     * @param string $code
     * @param string $specialAccount
     *
     * @return Subcuenta
     */
    public function getCustomerGroupAccount(string $code, string $specialAccount = self::SPECIAL_CUSTOMER_ACCOUNT)
    {
        $group = new GrupoClientes();
        if (!empty($code) && $group->loadFromCode($code) && !empty($group->codsubcuenta)) {
            return $this->getSubAccount($group->codsubcuenta, $specialAccount);
        }

        return $this->getSpecialSubAccount($specialAccount);
    }

    /**
     * Get the accounting subaccount for the account set as special
     *  - First search for the account marked as the special type indicated.
     *    If it exists, search for the accounting sub-account of the group
     *    If it does not exist, return a empty accounting sub-account
     *
     * @param string $specialAccount
     *
     * @return Subcuenta
     */
    public function getSpecialAccount(string $specialAccount)
    {
        $where = [
            new DataBaseWhere('codejercicio', $this->exercise->codejercicio),
            new DataBaseWhere('codcuentaesp', $specialAccount)
        ];
        $orderBy = ['codcuenta', 'DESC'];

        $account = new Cuenta();
        if ($account->loadFromCode('', $where, $orderBy)) {
            $where2 = [new DataBaseWhere('idcuenta', $account->idcuenta)];
            $orderBy2 = ['codsubcuenta', 'ASC'];

            $subaccount = new Subcuenta();
            $subaccount->loadFromCode('', $where2, $orderBy2);
            return $subaccount;
        }

        return new Subcuenta();
    }

    /**
     * Get an accounting sub-account for the special type indicated
     * If there is no, search within the group of accounts for the special type
     *
     * @param string $specialAccount
     *
     * @return Subcuenta
     */
    public function getSpecialSubAccount(string $specialAccount)
    {
        $where = [
            new DataBaseWhere('codejercicio', $this->exercise->codejercicio),
            new DataBaseWhere('codcuentaesp', $specialAccount)
        ];

        $orderBy = ['idsubcuenta', 'ASC'];

        $subAccount = new Subcuenta();
        if ($subAccount->loadFromCode('', $where, $orderBy)) {
            return $subAccount;
        }

        return $this->getSpecialAccount($specialAccount);
    }

    /**
     * Get the indicated accounting sub-account.
     * If it does not exist, search for the sub-account
     * associated with the special type indicated
     *
     * @param string $code
     * @param string $specialAccount
     *
     * @return Subcuenta
     */
    public function getSubAccount(string $code, string $specialAccount)
    {
        $where = [
            new DataBaseWhere('codejercicio', $this->exercise->codejercicio),
            new DataBaseWhere('codsubcuenta', $code)
        ];

        $subAccount = new Subcuenta();
        if ($subAccount->loadFromCode('', $where)) {
            return $subAccount;
        }

        return $this->getSpecialSubAccount($specialAccount);
    }

    /**
     * Get the accounting sub-account for the supplier and the fiscal year.
     * If it does not exist, search for the sub-account
     * associated with the special type indicated
     *
     * @param string $code
     * @param string $specialAccount
     *
     * @return Subcuenta
     */
    public function getSupplierAccount(string $code, string $specialAccount = self::SPECIAL_SUPPLIER_ACCOUNT)
    {
        $supplier = new Proveedor();
        if ($supplier->loadFromCode($code) && !empty($supplier->codsubcuenta)) {
            return $this->getSubAccount($supplier->codsubcuenta, $specialAccount);
        }

        return $this->getSpecialSubAccount($specialAccount);
    }

    /**
     * Set the exercise from primary key
     *
     * @param string $code
     */
    public function setExerciseFromCode(string $code)
    {
        $this->exercise->loadFromCode($code);
    }

    /**
     * Set the exercise search from company and date
     *
     * @param int    $idCompany
     * @param string $date
     */
    public function setExerciseFromCompany(int $idCompany, $date)
    {
        if (empty($date)) {
            $date = date('d-m-Y');
        }

        $this->exercise->idempresa = $idCompany;
        $this->exercise->loadFromDate($date, false);
    }
}
