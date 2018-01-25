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
use FacturaScripts\Core\Model\LineaPedidoProveedor;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LineaPedidoProveedor
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
final class LineaPedidoProveedorTest extends TestCase
{
    public function testNewLineaPedidoProveedor()
    {
        $model = new LineaPedidoProveedor();

        $this->assertInstanceOf(LineaPedidoProveedor::class, $model);
        $this->assertEquals(0.0, $model->cantidad);
        $this->assertEquals('', $model->descripcion);
        $this->assertEquals(0.0, $model->dtopor);
        $this->assertEquals(0.0, $model->irpf);
        $this->assertEquals(0.0, $model->iva);
        $this->assertEquals(0.0, $model->pvpsindto);
        $this->assertEquals(0.0, $model->pvptotal);
        $this->assertEquals(0.0, $model->pvpunitario);
        $this->assertEquals(0.0, $model->recargo);
        $this->assertTrue($model->test());
    }

    public function testTable()
    {
        $model = new LineaPedidoProveedor();

        $this->assertInternalType('string', $model::tableName());
    }

    public function testPrimaryColumn()
    {
        $model = new LineaPedidoProveedor();

        $this->assertInternalType('string', $model::primaryColumn());
    }

    public function testInstall()
    {
        $model = new LineaPedidoProveedor();

        $this->assertInternalType('string', $model->install());
    }

    public function testSave()
    {
        $dataBase = new DataBase();

        $this->assertEquals(true, $dataBase->connect());

        $model = new LineaPedidoProveedor();
        $sql = $model->install();

        if ($sql !== '') {
            $result = $dataBase->exec($sql);
            $this->assertFalse($result);
        }
    }

    public function testAll()
    {
        $model = new LineaPedidoProveedor();
        $list = $model->all();

        if (!empty($list)) {
            $this->assertInternalType('array', $list);
        } else {
            $this->assertSame([], $list);
        }
    }
}
