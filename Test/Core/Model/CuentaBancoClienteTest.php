<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2022-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\CuentaBancoCliente;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class CuentaBancoClienteTest extends TestCase
{
    use RandomDataTrait;

    public function testCreate(): void
    {
        // creamos un cliente
        $cliente = $this->getRandomCustomer();
        $this->assertTrue($cliente->save(), 'cliente-cant-save');

        // creamos una cuenta bancaria
        $cuenta = new CuentaBancoCliente();
        $cuenta->codcliente = $cliente->codcliente;
        $cuenta->descripcion = 'Test Account';
        $this->assertTrue($cuenta->save(), 'cuenta-cant-save');

        // comprobamos que se ha guardado correctamente
        $this->assertNotNull($cuenta->primaryColumnValue(), 'cuenta-not-stored');
        $this->assertTrue($cuenta->exists(), 'cuenta-cant-persist');

        // eliminamos
        $this->assertTrue($cuenta->delete(), 'cuenta-cant-delete');
        $this->assertTrue($cliente->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($cliente->delete(), 'cliente-cant-delete');
    }

    public function testCantCreateWithoutCustomer(): void
    {
        // creamos una cuenta bancaria
        $cuenta = new CuentaBancoCliente();
        $cuenta->descripcion = 'Test Account';
        $this->assertFalse($cuenta->save(), 'cuenta-can-save');
    }

    public function testHtmlOnFields(): void
    {
        // desactivamos la validación de IBAN
        Tools::settingsSet('default', 'validate_iban', '0');

        // creamos un cliente
        $cliente = $this->getRandomCustomer();
        $this->assertTrue($cliente->save(), 'cliente-cant-save');

        // creamos una cuenta bancaria con html en los campos
        $cuenta = new CuentaBancoCliente();
        $cuenta->codcliente = $cliente->codcliente;
        $cuenta->descripcion = '<p>Test Account</p>';
        $cuenta->iban = '<test>';
        $cuenta->swift = '<t>';
        $this->assertTrue($cuenta->save(), 'cuenta-cant-save');

        // comprobamos que el html se ha escapado
        $this->assertEquals('&lt;p&gt;Test Account&lt;/p&gt;', $cuenta->descripcion);
        $this->assertEquals('&lt;test&gt;', $cuenta->iban);
        $this->assertEquals('&lt;t&gt;', $cuenta->swift);

        // eliminamos
        $this->assertTrue($cuenta->delete(), 'cuenta-cant-delete');
        $this->assertTrue($cliente->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($cliente->delete(), 'cliente-cant-delete');
    }

    public function testDeleteWithCustomer(): void
    {
        // creamos un cliente
        $cliente = $this->getRandomCustomer();
        $this->assertTrue($cliente->save(), 'cliente-cant-save');

        // creamos una cuenta bancaria
        $cuenta = new CuentaBancoCliente();
        $cuenta->codcliente = $cliente->codcliente;
        $cuenta->descripcion = 'Test Account';
        $this->assertTrue($cuenta->save(), 'cuenta-cant-save');

        // eliminamos el cliente
        $this->assertTrue($cliente->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($cliente->delete(), 'cliente-cant-delete');

        // comprobamos que la cuenta se ha eliminado
        $this->assertFalse($cuenta->exists(), 'cuenta-persist');
    }

    public function testValidateIban(): void
    {
        // activamos la validación de IBAN
        Tools::settingsSet('default', 'validate_iban', '1');

        // creamos un cliente
        $cliente = $this->getRandomCustomer();
        $this->assertTrue($cliente->save(), 'cliente-cant-save');

        // creamos una cuenta bancaria con IBAN incorrecto
        $cuenta = new CuentaBancoCliente();
        $cuenta->codcliente = $cliente->codcliente;
        $cuenta->descripcion = 'Test Account';
        $cuenta->iban = 'ES912100041840123456789';
        $this->assertFalse($cuenta->save(), 'cuenta-can-save');

        // creamos una cuenta bancaria con IBAN correcto
        $cuenta = new CuentaBancoCliente();
        $cuenta->codcliente = $cliente->codcliente;
        $cuenta->descripcion = 'Test Account';
        $cuenta->iban = 'ES9121000418450200051332';
        $this->assertTrue($cuenta->save(), 'cuenta-cant-save');

        // eliminamos
        $this->assertTrue($cuenta->delete(), 'cuenta-cant-delete');
        $this->assertTrue($cliente->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($cliente->delete(), 'cliente-cant-delete');
    }

    public function testMultipleEmptyMandato(): void
    {
        // creamos un cliente
        $cliente = $this->getRandomCustomer();
        $this->assertTrue($cliente->save(), 'cliente-cant-save');

        // creamos una cuenta bancaria
        $cuenta1 = new CuentaBancoCliente();
        $cuenta1->codcliente = $cliente->codcliente;
        $cuenta1->descripcion = 'Test Account 1';
        $cuenta1->mandato = '';
        $this->assertTrue($cuenta1->save(), 'cuenta-cant-save');

        // creamos otra cuenta bancaria
        $cuenta2 = new CuentaBancoCliente();
        $cuenta2->codcliente = $cliente->codcliente;
        $cuenta2->descripcion = 'Test Account 2';
        $cuenta2->mandato = '';
        $this->assertTrue($cuenta2->save(), 'cuenta-can-save');

        // eliminamos
        $this->assertTrue($cuenta1->delete(), 'cuenta1-cant-delete');
        $this->assertTrue($cuenta2->delete(), 'cuenta2-cant-delete');
        $this->assertTrue($cliente->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($cliente->delete(), 'cliente-cant-delete');
    }
}
