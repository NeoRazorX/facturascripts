<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018  Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 * Copyright (C) 2018  Carlos García Gómez      <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Test\Core\Model;

use FacturaScripts\Core\Model\Empresa;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Empresa
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
final class EmpresaTest extends TestCase
{
    public function testNewEmpresa()
    {
        $model = new Empresa();

        $this->assertInstanceOf(Empresa::class, $model);
        $this->assertEquals('', $model->administrador);
        $this->assertEquals('', $model->ciudad);
        $this->assertEquals('', $model->codpostal);
        $this->assertEquals('', $model->direccion);
        $this->assertEquals('', $model->nombre);
        $this->assertEquals('', $model->nombrecorto);
        $this->assertEquals('', $model->provincia);
        $this->assertEquals('', $model->web);

        $this->assertFalse($model->test());

        $model->administrador = 'Test name admin';
        $model->ciudad = 'Test city';
        $model->codpostal = 22222;
        $model->direccion = 'Test address';
        $model->nombre = 'A long text for name';
        $model->nombrecorto = 'Shortname';
        $model->provincia = 'Test name';
        $model->web = 'http://www.example.com';

        $this->assertTrue($model->test());
        $this->assertTrue($model->save());
    }

    public function testTable()
    {
        $model = new Empresa();

        $this->assertInternalType('string', $model::tableName());
    }

    public function testPrimaryColumn()
    {
        $model = new Empresa();

        $this->assertInternalType('string', $model::primaryColumn());
    }

    public function testAll()
    {
        $model = new Empresa();
        $list = $model->all();

        if (!empty($list)) {
            $this->assertInternalType('array', $list);
        } else {
            $this->assertSame([], $list);
        }
    }
}
