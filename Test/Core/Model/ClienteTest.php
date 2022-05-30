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

namespace FacturaScripts\Test\Core\Model;

use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Test\Core\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class ClienteTest extends TestCase
{
    use LogErrorsTrait;

    public function testCreate()
    {
        $cliente = new Cliente();
        $cliente->nombre = 'Test';
        $cliente->cifnif = '12345678A';
        $this->assertTrue($cliente->save(), 'cliente-cant-save');
        $this->assertNotNull($cliente->primaryColumnValue(), 'cliente-not-stored');
        $this->assertTrue($cliente->exists(), 'cliente-cant-persist');

        // razón social es igual a nombre
        $this->assertEquals($cliente->nombre, $cliente->razonsocial);

        // eliminamos
        $this->assertTrue($cliente->delete(), 'cliente-cant-delete');
    }

    public function testCantCreateEmpty()
    {
        $cliente = new Cliente();
        $cliente->nombre = '';
        $cliente->cifnif = '';
        $this->assertFalse($cliente->save(), 'cliente-can-save');

        // el cliente no existe
        $this->assertFalse($cliente->exists(), 'cliente-persisted');
    }

    public function testBadEmail()
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
        $this->assertTrue($cliente->delete(), 'cliente-cant-delete');
    }

    public function testHtmlOnFields()
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
        $this->assertTrue($cliente->delete(), 'cliente-cant-delete');
    }

    public function testBadWeb()
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

    public function testGoodWeb()
    {
        $cliente = new Cliente();
        $cliente->nombre = 'Test';
        $cliente->cifnif = '12345678A';
        $cliente->web = 'https://www.example.com';
        $this->assertTrue($cliente->save(), 'cliente-cant-save-web');
        $this->assertTrue($cliente->delete(), 'cliente-cant-delete');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
