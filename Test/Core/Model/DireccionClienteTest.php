<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018  Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
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

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Model\DireccionCliente;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DireccionCliente
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
final class DireccionClienteTest extends TestCase
{
    public function testNewDireccionCliente()
    {
        $model = new DireccionCliente();

        $this->assertInstanceOf(DireccionCliente::class, $model);
        $now = new \DateTime();
        $this->assertEquals('Principal', $model->descripcion);
        $this->assertTrue($model->domenvio);
        $this->assertTrue($model->domfacturacion);
        $this->assertEquals($now->format('d-m-Y'), $model->fecha);
        $this->assertTrue($model->test());

        $model->descripcion = 'Alternative';
        $model->domenvio = false;
        $model->domfacturacion = false;
        $model->fecha = $now->add(new \DateInterval('P10D'))->format('d-m-Y');

        $this->assertTrue($model->test());
    }

    public function testTable()
    {
        $model = new DireccionCliente();

        $this->assertInternalType('string', $model::tableName());
    }

    public function testPrimaryColumn()
    {
        $model = new DireccionCliente();

        $this->assertInternalType('string', $model::primaryColumn());
    }

    public function testInstall()
    {
        $model = new DireccionCliente();

        $this->assertInternalType('string', $model->install());
    }

    public function testSave()
    {
        $dataBase = new DataBase();

        $this->assertEquals(true, $dataBase->connect());

        $model = new DireccionCliente();
        $sql = $model->install();

        if ($sql !== '') {
            $result = $dataBase->exec($sql);
            $this->assertFalse($result);
        }
    }

    public function testAll()
    {
        $model = new DireccionCliente();
        $list = $model->all();

        if (!empty($list)) {
            $this->assertInternalType('array', $list);
        } else {
            $this->assertSame([], $list);
        }
    }
}
