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
use FacturaScripts\Core\Model\Proveedor;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class ProveedorTest extends TestCase
{
    use LogErrorsTrait;

    public function testCreate(): void
    {
        $proveedor = new Proveedor();
        $proveedor->nombre = 'Test';
        $proveedor->cifnif = '12345678A';
        $this->assertTrue($proveedor->save(), 'proveedor-cant-save');
        $this->assertNotNull($proveedor->primaryColumnValue(), 'proveedor-not-stored');
        $this->assertTrue($proveedor->exists(), 'proveedor-cant-persist');

        // razón social es igual a nombre
        $this->assertEquals($proveedor->nombre, $proveedor->razonsocial);

        // eliminamos
        $this->assertTrue($proveedor->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($proveedor->delete(), 'proveedor-cant-delete');
    }

    public function testCreateEmpty(): void
    {
        $proveedor = new Proveedor();
        $proveedor->nombre = '';
        $proveedor->cifnif = '';
        $this->assertFalse($proveedor->save(), 'proveedor-can-save');

        // el proveedor no existe
        $this->assertFalse($proveedor->exists(), 'proveedor-persisted');
    }

    public function testHtmlOnFields(): void
    {
        $proveedor = new Proveedor();
        $proveedor->nombre = '<test>';
        $proveedor->cifnif = '<test>';
        $proveedor->razonsocial = '<test>';
        $proveedor->telefono1 = '<test>';
        $proveedor->telefono2 = '<test>';
        $proveedor->fax = '<test>';
        $proveedor->observaciones = '<test>';
        $this->assertTrue($proveedor->save(), 'proveedor-cant-save');

        // comprobamos que el html se ha escapado
        $this->assertEquals('&lt;test&gt;', $proveedor->nombre, 'html-not-escaped-on-nombre');
        $this->assertEquals('&lt;test&gt;', $proveedor->cifnif, 'html-not-escaped-on-cifnif');
        $this->assertEquals('&lt;test&gt;', $proveedor->razonsocial, 'html-not-escaped-on-razonsocial');
        $this->assertEquals('&lt;test&gt;', $proveedor->telefono1, 'html-not-escaped-on-telefono1');
        $this->assertEquals('&lt;test&gt;', $proveedor->telefono2, 'html-not-escaped-on-telefono2');
        $this->assertEquals('&lt;test&gt;', $proveedor->fax, 'html-not-escaped-on-fax');
        $this->assertEquals('&lt;test&gt;', $proveedor->observaciones, 'html-not-escaped-on-observaciones');

        // eliminamos
        $this->assertTrue($proveedor->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($proveedor->delete(), 'proveedor-cant-delete');
    }

    public function testNotNullFields(): void
    {
        // creamos un proveedor
        $proveedor = new Proveedor();
        $proveedor->nombre = 'Test';
        $proveedor->cifnif = '12345678A';
        $this->assertTrue($proveedor->save(), 'proveedor-cant-save');

        // comprobamos que los teléfonos, fax, email y observaciones no sean nulos
        $this->assertNotNull($proveedor->telefono1, 'telefono1-is-null');
        $this->assertNotNull($proveedor->telefono2, 'telefono2-is-null');
        $this->assertNotNull($proveedor->fax, 'fax-is-null');
        $this->assertNotNull($proveedor->email, 'email-is-null');
        $this->assertNotNull($proveedor->observaciones, 'observaciones-is-null');

        // eliminamos
        $this->assertTrue($proveedor->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($proveedor->delete(), 'proveedor-cant-delete');
    }

    public function testBadEmail(): void
    {
        $proveedor = new Proveedor();
        $proveedor->nombre = 'Test';
        $proveedor->cifnif = '12345678A';
        $proveedor->email = 'bad-email';
        $this->assertFalse($proveedor->save(), 'proveedor-can-save');

        // el proveedor no existe
        $this->assertFalse($proveedor->exists(), 'proveedor-persisted');

        // probamos un email correcto
        $proveedor->email = 'pepe@test.com';
        $this->assertTrue($proveedor->save(), 'proveedor-cant-save');

        // eliminamos
        $this->assertTrue($proveedor->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($proveedor->delete(), 'proveedor-cant-delete');
    }

    public function testBadWeb(): void
    {
        $proveedor = new Proveedor();
        $proveedor->nombre = 'Test';
        $proveedor->cifnif = '12345678A';
        $proveedor->web = 'javascript:alert(origin)';
        $this->assertFalse($proveedor->save(), 'cliente-can-save-bad-web');

        // javascript con forma de url
        $proveedor->web = 'javascript://example.com//%0aalert(document.domain);//';
        $this->assertFalse($proveedor->save(), 'cliente-can-save-bad-web-2');

        // javascript con mayúsculas
        $proveedor->web = 'jAvAsCriPt://sadas.com/%0aalert(15);//';
        $this->assertFalse($proveedor->save(), 'cliente-can-save-bad-web-3');
    }

    public function testGoodWeb(): void
    {
        $proveedor = new Proveedor();
        $proveedor->nombre = 'Test';
        $proveedor->cifnif = '12345678A';
        $proveedor->web = 'https://www.example.com';
        $this->assertTrue($proveedor->save(), 'cliente-can-save-good-web');

        // eliminamos
        $this->assertTrue($proveedor->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($proveedor->delete(), 'proveedor-cant-delete');
    }

    public function testVies(): void
    {
        // creamos un proveedor sin cif/nif
        $proveedor = new Proveedor();
        $proveedor->nombre = 'Test';
        $proveedor->cifnif = '';
        $this->assertTrue($proveedor->save());

        $check1 = $proveedor->checkVies();
        if (Vies::getLastError() != '') {
            $this->markTestSkipped('Vies service error: ' . Vies::getLastError());
        }
        $this->assertFalse($check1);

        // asignamos dirección de Italia
        $address = $proveedor->getDefaultAddress();
        $address->codpais = 'ITA';
        $this->assertTrue($address->save());

        // asignamos un cif/nif incorrecto
        $proveedor->cifnif = '12345678A';
        $check2 = $proveedor->checkVies();
        if (Vies::getLastError() != '') {
            $this->markTestSkipped('Vies service error: ' . Vies::getLastError());
        }
        $this->assertFalse($check2);

        // asignamos un cif/nif correcto
        $proveedor->cifnif = '02839750995';
        $check3 = $proveedor->checkVies();
        if (Vies::getLastError() != '') {
            $this->markTestSkipped('Vies service error: ' . Vies::getLastError());
        }
        $this->assertTrue($check3);

        // eliminamos
        $this->assertTrue($address->delete());
        $this->assertTrue($proveedor->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
