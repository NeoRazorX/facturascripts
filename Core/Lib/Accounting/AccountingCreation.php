<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Cuenta;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Dinamic\Model\Subcuenta;

/**
 * Class to create accounting sub-accounts automatically:
 *
 *   - General
 *   - Customer
 *   - Supplier
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class AccountingCreation
{

    /**
     * @var Ejercicio
     */
    private $exercise;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->exercise = new Ejercicio();
    }

    /**
     * Create a sub-account with the code and the reported description
     * belonging to the group and exercise.
     *
     * @param Cuenta $account Parent group account
     * @param string $code The code of the subaccount
     * @param string $description The description of the subaccount
     *
     * @return Subcuenta
     */
    public function createFromAccount($account, $code, $description = '')
    {
        if (!$account->exists() || !$this->checkExercise($account->codejercicio)) {
            return new Subcuenta();
        }

        $subaccount = new Subcuenta();
        $subaccount->codcuenta = $account->codcuenta;
        $subaccount->codejercicio = $account->codejercicio;
        $subaccount->codsubcuenta = $code;
        $subaccount->descripcion = empty($description) ? $account->descripcion : $description;
        $subaccount->idcuenta = $account->idcuenta;
        $subaccount->save();
        return $subaccount;
    }

    /**
     * Create an account into informed exercise.
     *
     * @param Cuenta $account
     * @param string $codejercicio
     *
     * @return Cuenta
     */
    public function copyAccountToExercise($account, $codejercicio)
    {
        /// account already exists?
        $newAccount = new Cuenta();
        $where = [
            new DataBaseWhere('codcuenta', $account->codcuenta),
            new DataBaseWhere('codejercicio', $codejercicio)
        ];
        if ($newAccount->loadFromCode('', $where)) {
            return $newAccount;
        }

        /// create account
        $newAccount->codcuenta = $account->codcuenta;
        $newAccount->codcuentaesp = $account->codcuentaesp;
        $newAccount->codejercicio = $codejercicio;
        $newAccount->descripcion = $account->descripcion;
        $newAccount->parent_codcuenta = $account->parent_codcuenta;
        $newAccount->save();
        return $newAccount;
    }

    /**
     * Create a subaccount into informed exercise.
     *    - Check exercise is open.
     *    - Check subaccount exists.
     *
     * For new subaccount:
     *    - Search acount and copy from source subacount if dont exists.
     *    - Save new subaccount.
     *
     * @param Subcuenta $subAccount
     * @param string $codejercicio
     *
     * @return Subcuenta
     */
    public function copySubAccountToExercise($subAccount, $codejercicio)
    {
        if (!$this->checkExercise($codejercicio)) {
            return new Subcuenta();
        }

        $newSubaccount = new Subcuenta();
        $where = [
            new DataBaseWhere('codsubcuenta', $subAccount->codsubcuenta),
            new DataBaseWhere('codejercicio', $codejercicio)
        ];
        if ($newSubaccount->loadFromCode('', $where)) {
            return $newSubaccount;
        }

        $newSubaccount->codcuenta = $subAccount->codcuenta;
        $newSubaccount->codejercicio = $this->exercise->codejercicio;
        $newSubaccount->codsubcuenta = $subAccount->codsubcuenta;
        $newSubaccount->descripcion = $subAccount->descripcion;

        $account = $newSubaccount->getAccount();
        if (empty($account->idcuenta)) {
            $account = $this->copyAccountToExercise($subAccount->getAccount(), $codejercicio);
        }

        $newSubaccount->idcuenta = $account->idcuenta;
        $newSubaccount->save();
        return $newSubaccount;
    }

    /**
     * Create the accounting sub-account for the informed customer or supplier.
     * If the customer or supplier does not have an associated accounting subaccount,
     * one is calculated automatically.
     *
     * @param Cliente|Proveedor $subject Customer or Supplier model
     * @param Cuenta $account Parent group account model
     *
     * @return Subcuenta
     */
    public function createSubjectAccount(&$subject, $account)
    {
        if (!$account->exists() || !$this->checkExercise($account->codejercicio)) {
            return new Subcuenta();
        }

        if (empty($subject->codsubcuenta)) {
            $subject->codsubcuenta = $this->getFreeSubjectSubaccount($subject, $account);
        }

        $subaccount = new Subcuenta();
        $subaccount->codcuenta = $account->codcuenta;
        $subaccount->codejercicio = $account->codejercicio;
        $subaccount->codsubcuenta = $subject->codsubcuenta;
        $subaccount->descripcion = $subject->razonsocial;
        $subaccount->idcuenta = $account->idcuenta;
        if ($subaccount->save()) {
            $subject->save();
        }

        return $subaccount;
    }

    /**
     * Complete to the indicated length with zeros.
     *
     * @param int $length
     * @param string $value
     * @param string $prefix
     *
     * @return string
     */
    public function fillToLength(int $length, string $value, string $prefix = ''): string
    {
        $value2 = trim($value);
        $count = $length - strlen($prefix) - strlen($value2);
        if ($count > 0) {
            return $prefix . str_repeat('0', $count) . $value2;
        } elseif ($count == 0) {
            return $prefix . $value2;
        }

        return '';
    }

    /**
     * Calculate an accounting sub-account from the customer or supplier code
     *
     * @param Cliente|Proveedor $subject Customer or Supplier model
     * @param Cuenta $account Parent group account model
     *
     * @return string
     */
    public function getFreeSubjectSubaccount($subject, $account): string
    {
        if (false === $this->checkExercise($account->codejercicio)) {
            return '';
        }

        // nos quedamos solamente con los números del código
        $code = preg_replace('/[^0-9]/', '', $subject->primaryColumnValue());
        if (strlen($code) === $this->exercise->longsubcuenta) {
            // si el código ya tiene la longitud de una subcuenta, lo usamos como subcuenta
            return $code;
        }

        // conformamos un array con el número del cliente, los 49 primeros números y varios números aleatorios
        $numbers = array_merge(
            [$code],
            range(1, 49),
            [rand(50, 99), rand(50, 999), rand(100, 9999), rand(100, 99999), rand(1000, 999999)]
        );

        // añadimos también los 100 siguientes números al total de subcuentas
        $subcuenta = new Subcuenta();
        $whereTotal = [
            new DataBaseWhere('codcuenta', $account->codcuenta),
            new DataBaseWhere('codejercicio', $account->codejercicio)
        ];
        $total = $subcuenta->count($whereTotal);
        if ($total > 99) {
            $numbers = array_merge($numbers, range($total, $total + 99));
        }

        // probamos los números para elegir el primer código de subcuenta que no exista
        foreach ($numbers as $num) {
            $newCode = $this->fillToLength($this->exercise->longsubcuenta, $num, $account->codcuenta);
            if (empty($newCode)) {
                continue;
            }

            // comprobamos que esta subcuenta no esté en uso en otro cliente o proveedor
            $where = [new DataBaseWhere('codsubcuenta', $newCode)];
            $count = $subject->count($where);
            if ($count > 0) {
                continue;
            }

            // si la subcuenta no existe, la elegimos
            $where = [
                new DataBaseWhere('codejercicio', $account->codejercicio),
                new DataBaseWhere('codsubcuenta', $newCode)
            ];
            if (false === $subcuenta->loadFromCode('', $where)) {
                return $newCode;
            }
        }

        // no hemos encontrado ninguna subcuenta libre
        Tools::log()->error('no-empty-account-found');

        return '';
    }

    /**
     * Load exercise and check if it can be used
     *
     * @param string $code
     *
     * @return bool
     */
    private function checkExercise(string $code): bool
    {
        if ($this->exercise->codejercicio !== $code) {
            $this->exercise->loadFromCode($code);
        }

        return $this->exercise->isOpened();
    }
}
