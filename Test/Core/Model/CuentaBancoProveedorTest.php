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

use FacturaScripts\Core\Model\CuentaBancoProveedor;
use FacturaScripts\Core\Model\Proveedor;
use FacturaScripts\Test\Core\CustomTest;

/**
 * Description of CuentaBancoProveedorTest
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 * @covers \FacturaScripts\Core\Model\CuentaBancoProveedor
 */
class CuentaBancoProveedorTest extends CustomTest
{

    protected function setUp()
    {
        $this->model = new CuentaBancoProveedor();
    }

    public function testSaveInsert()
    {
        /// save supplier
        $supplier = new Proveedor();
        $supplier->cifnif = '1234';
        $supplier->nombre = 'Test';
        $this->assertTrue($supplier->save());

        /// save bank account
        $account = new CuentaBancoProveedor();
        $account->codproveedor = $supplier->primaryColumnValue();
        $account->descripcion = 'test';
        $this->assertTrue($account->save());

        /// delete bank account
        $this->assertTrue($account->delete());

        /// delete supplier
        $this->assertTrue($supplier->delete());
    }

    public function testIBAN()
    {
        /// save supplier
        $supplier = new Proveedor();
        $supplier->cifnif = '1234';
        $supplier->nombre = 'Test';
        $this->assertTrue($supplier->save());

        /// save valid iban
        $account = new CuentaBancoProveedor();
        $account->codproveedor = $supplier->primaryColumnValue();
        $account->descripcion = 'test';
        $account->iban = 'ES91 2100 0418 4502 0005 1332';
        $this->assertTrue($account->save());

        /// now save invalid iban
        $account->iban = '1234';
        $this->assertFalse($account->save());

        /// delete bank account
        $this->assertTrue($account->delete());

        /// delete supplier
        $this->assertTrue($supplier->delete());
    }
}
