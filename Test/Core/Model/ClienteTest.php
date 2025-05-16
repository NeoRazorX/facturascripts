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

use FacturaScripts\Core\Lib\Vies;
use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class ClienteTest extends TestCase
{
    use LogErrorsTrait;

    public function testCreate(): void
    {
        $cliente = new Cliente();
        $cliente->nombre = 'Test';
        $cliente->cifnif = '12345678A';
        $this->assertTrue($cliente->save(), 'cliente-cant-save');
        $this->assertNotNull($cliente->primaryColumnValue(), 'cliente-not-stored');
        $this->assertTrue($cliente->exists(), 'cliente-cant-persist');

        // razón social es igual a nombre
        $this->assertEquals($cliente->nombre, $cliente->razonsocial);

        // comprobamos que se ha creado una dirección por defecto
        $addresses = $cliente->getAddresses();
        $this->assertCount(1, $addresses, 'cliente-default-address-not-created');
        foreach ($addresses as $address) {
            $this->assertEquals($address->cifnif, $cliente->cifnif);
            $this->assertEquals($address->codagente, $cliente->codagente);
            $this->assertEquals($address->codcliente, $cliente->codcliente);
            $this->assertEquals($address->idcontacto, $cliente->idcontactofact);
        }

        // eliminamos
        $this->assertTrue($cliente->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($cliente->delete(), 'cliente-cant-delete');
    }

    public function testCantCreateEmpty(): void
    {
        $cliente = new Cliente();
        $cliente->nombre = '';
        $cliente->cifnif = '';
        $this->assertFalse($cliente->save(), 'cliente-can-save');

        // el cliente no existe
        $this->assertFalse($cliente->exists(), 'cliente-persisted');
    }

    public function testBadEmail(): void
    {
        $cliente = new Cliente();
        $cliente->nombre = 'Test';
        $cliente->cifnif = '12345678A';
        $cliente->email = 'bademail';
        $this->assertFalse($cliente->save(), 'cliente-can-save');

        // el cliente no existe
        $this->assertFalse($cliente->exists(), 'cliente-persisted');

        // probamos con un email correcto
        $cliente->email = 'pepe@facturascripts.com';
        $this->assertTrue($cliente->save(), 'cliente-cant-save');

        // eliminamos
        $this->assertTrue($cliente->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($cliente->delete(), 'cliente-cant-delete');
    }

    public function testHtmlOnFields(): void
    {
        $cliente = new Cliente();
        $cliente->nombre = '<test>';
        $cliente->cifnif = '<test>';
        $cliente->razonsocial = '<test>';
        $cliente->telefono1 = '<test>';
        $cliente->telefono2 = '<test>';
        $cliente->fax = '<test>';
        $cliente->observaciones = '<test>';
        $this->assertTrue($cliente->save(), 'cliente-cant-save');

        // comprobamos que el html se ha escapado
        $this->assertEquals('&lt;test&gt;', $cliente->nombre, 'html-not-escaped-on-nombre');
        $this->assertEquals('&lt;test&gt;', $cliente->cifnif, 'html-not-escaped-on-cifnif');
        $this->assertEquals('&lt;test&gt;', $cliente->razonsocial, 'html-not-escaped-on-razonsocial');
        $this->assertEquals('&lt;test&gt;', $cliente->telefono1, 'html-not-escaped-on-telefono1');
        $this->assertEquals('&lt;test&gt;', $cliente->telefono2, 'html-not-escaped-on-telefono2');
        $this->assertEquals('&lt;test&gt;', $cliente->fax, 'html-not-escaped-on-fax');
        $this->assertEquals('&lt;test&gt;', $cliente->observaciones, 'html-not-escaped-on-observaciones');

        // eliminamos
        $this->assertTrue($cliente->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($cliente->delete(), 'cliente-cant-delete');
    }

    public function testBadWeb(): void
    {
        $cliente = new Cliente();
        $cliente->nombre = 'Test';
        $cliente->cifnif = '12345678A';
        $cliente->web = 'javascript:alert(origin)';
        $this->assertFalse($cliente->save(), 'cliente-can-save-bad-web');

        // javascript con forma de url
        $cliente->web = 'javascript://example.com//%0aalert(document.domain);//';
        $this->assertFalse($cliente->save(), 'cliente-can-save-bad-web-2');

        // javascript con mayúsculas
        $cliente->web = 'jAvAsCriPt://sadas.com/%0aalert(11);//';
        $this->assertFalse($cliente->save(), 'cliente-can-save-bad-web-3');
    }

    public function testGoodWeb(): void
    {
        $cliente = new Cliente();
        $cliente->nombre = 'Test';
        $cliente->cifnif = '12345678A';
        $cliente->web = 'https://www.example.com';
        $this->assertTrue($cliente->save(), 'cliente-cant-save-web');
        $this->assertTrue($cliente->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($cliente->delete(), 'cliente-cant-delete');
    }

    public function testNotNullFields(): void
    {
        $cliente = new Cliente();
        $cliente->nombre = 'Test';
        $cliente->cifnif = '12345678A';
        $this->assertTrue($cliente->save(), 'cliente-cant-save');

        // comprobamos que los teléfonos, fax, email y observaciones no sean nulos
        $this->assertNotNull($cliente->telefono1, 'telefono1-is-null');
        $this->assertNotNull($cliente->telefono2, 'telefono2-is-null');
        $this->assertNotNull($cliente->fax, 'fax-is-null');
        $this->assertNotNull($cliente->email, 'email-is-null');
        $this->assertNotNull($cliente->observaciones, 'observaciones-is-null');

        // eliminamos
        $this->assertTrue($cliente->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($cliente->delete(), 'cliente-cant-delete');
    }

    public function testPaymentDays(): void
    {
        // creamos un cliente sin días de pago
        $cliente = new Cliente();
        $cliente->nombre = 'Test';
        $cliente->cifnif = '12345678A';

        // comprobamos que no tiene días de pago
        $this->assertEmpty($cliente->getPaymentDays(), 'cliente-has-payment-days');

        // añadimos un día de pago
        $cliente->diaspago = '1';
        $this->assertEquals([1], $cliente->getPaymentDays(), 'cliente-has-payment-days');

        // añadimos un segundo día de pago
        $cliente->diaspago = '1,5';
        $this->assertEquals([1, 5], $cliente->getPaymentDays(), 'cliente-has-payment-days');
    }

    public function testVies(): void
    {
        // creamos un cliente sin cifnif
        $cliente = new Cliente();
        $cliente->nombre = 'Test';
        $cliente->cifnif = '';
        $this->assertTrue($cliente->save());

        $check1 = $cliente->checkVies();
        if (Vies::getLastError() != '') {
            $this->markTestSkipped('Vies service error: ' . Vies::getLastError());
        }
        $this->assertFalse($check1);

        // asignamos dirección de Portugal
        $address = $cliente->getDefaultAddress();
        $address->codpais = 'PRT';
        $this->assertTrue($address->save());

        // asignamos un cifnif incorrecto
        $cliente->cifnif = '12345678A';
        $this->assertFalse($cliente->checkVies());

        // asignamos un cifnif correcto
        $cliente->cifnif = '503297887';
        $this->assertTrue($cliente->checkVies());

        // eliminamos
        $this->assertTrue($address->delete());
        $this->assertTrue($cliente->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
