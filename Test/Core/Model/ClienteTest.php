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
