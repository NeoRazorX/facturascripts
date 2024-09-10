<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Lib;

use FacturaScripts\Core\Lib\Accounting\AccountingAccounts;
use FacturaScripts\Core\Lib\Accounting\AccountingCreation;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class AccountingCreationTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    private static $subaccounts = [];

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
    }

    public function testCreateCustomer()
    {
        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // obtenemos la cuenta de clientes
        $accounts = new AccountingAccounts();
        $accounts->exercise = $this->getCurrentExercise();
        $customersAccount = $accounts->getSpecialAccount(AccountingAccounts::SPECIAL_CUSTOMER_ACCOUNT);
        $this->assertTrue($customersAccount->exists(), 'cant-get-customer-account');

        // obtenemos una nueva subcuenta para el cliente, 1001 veces,
        // para comprobar si en todos los casos se crea una nueva
        $creator = new AccountingCreation();
        for ($i = 0; $i < 1000; $i++) {
            $subaccount = $creator->createSubjectAccount($customer, $customersAccount);
            $this->assertTrue($subaccount->exists(), 'cant-create-customer-subaccount-' . $i);

            $customer->codsubcuenta = null;
            self::$subaccounts[] = $subaccount;
        }

        // eliminamos el cliente
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
    }

    private function getCurrentExercise(): Ejercicio
    {
        $ejercicioModel = new Ejercicio();
        foreach ($ejercicioModel->all() as $ejercicio) {
            if ($ejercicio->isOpened()) {
                return $ejercicio;
            }
        }

        return $ejercicioModel;
    }

    protected function tearDown(): void
    {
        // eliminamos las subcuentas creadas
        foreach (self::$subaccounts as $key => $subaccount) {
            $subaccount->delete();
            unset(self::$subaccounts[$key]);
        }

        $this->logErrors();
    }
}
