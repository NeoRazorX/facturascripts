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

use FacturaScripts\Core\Model\Proveedor;
use FacturaScripts\Test\Core\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class ProveedorTest extends TestCase
{
    use LogErrorsTrait;

    public function testCreate()
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
        $this->assertTrue($proveedor->delete(), 'proveedor-cant-delete');
    }

    public function testCreateEmpty()
    {
        $proveedor = new Proveedor();
        $proveedor->nombre = '';
        $proveedor->cifnif = '';
        $this->assertFalse($proveedor->save(), 'proveedor-can-save');

        // el proveedor no existe
        $this->assertFalse($proveedor->exists(), 'proveedor-persisted');
    }

    public function testBadWeb()
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

    public function testGoodWeb()
    {
        $proveedor = new Proveedor();
        $proveedor->nombre = 'Test';
        $proveedor->cifnif = '12345678A';
        $proveedor->web = 'https://www.example.com';
        $this->assertTrue($proveedor->save(), 'cliente-can-save-good-web');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
