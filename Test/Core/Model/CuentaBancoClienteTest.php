<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Model;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Core\Model\CuentaBancoCliente;
use FacturaScripts\Test\Core\CustomTest;

/**
 * Description of CuentaBancoClienteTest
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 * @covers \FacturaScripts\Core\Model\CuentaBancoCliente
 */
class CuentaBancoClienteTest extends CustomTest
{

    protected function setUp()
    {
        $this->model = new CuentaBancoCliente();
    }

    public function testSaveInsert()
    {
        /// save customer
        $customer = new Cliente();
        $customer->cifnif = '1234';
        $customer->nombre = 'Test';
        $this->assertTrue($customer->save());

        /// save bank account
        $account = new CuentaBancoCliente();
        $account->codcliente = $customer->primaryColumnValue();
        $account->descripcion = 'test';
        $this->assertTrue($account->save());

        /// delete bank account
        $this->assertTrue($account->delete());

        /// delete customer
        $this->assertTrue($customer->delete());
    }

    public function testIBAN()
    {
        /// save customer
        $customer = new Cliente();
        $customer->cifnif = '1234';
        $customer->nombre = 'Test';
        $this->assertTrue($customer->save());

        /// save valid iban with validate
        $settings = new AppSettings();
        $settings->set('default', 'validate_iban', true);
        $account = new CuentaBancoCliente();
        $account->codcliente = $customer->primaryColumnValue();
        $account->descripcion = 'test';
        $account->iban = 'ES91 2100 0418 4502 0005 1332';
        $this->assertTrue($account->save());

        /// now save invalid iban with validate
        $account->iban = '1234';
        $this->assertFalse($account->save());

        /// now save invalid iban without validate
        $settings->set('default', 'validate_iban', false);
        $this->assertTrue($account->save());

        /// delete bank account
        $this->assertTrue($account->delete());

        /// delete customer
        $this->assertTrue($customer->delete());
    }
}
