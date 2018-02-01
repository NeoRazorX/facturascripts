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
use FacturaScripts\Core\Model\BalanceCuenta;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BalanceCuenta
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
final class BalanceCuentaTest extends TestCase
{
    public function testNewBalanceCuenta()
    {
        $model = new BalanceCuenta();

        $this->assertInstanceOf(BalanceCuenta::class, $model);
        $this->assertNull($model->codbalance);
        $this->assertNull($model->codcuenta);
        $this->assertNull($model->desccuenta);
        $this->assertTrue($model->test());

        $model->codbalance = 'CODB1';
        $model->codcuenta = 'CODC1';
        $model->desccuenta = 'DESC1';

        $this->assertTrue($model->test());
    }

    public function testTable()
    {
        $model = new BalanceCuenta();

        $this->assertInternalType('string', $model::tableName());
    }

    public function testPrimaryColumn()
    {
        $model = new BalanceCuenta();

        $this->assertInternalType('string', $model::primaryColumn());
    }

    public function testInstall()
    {
        $model = new BalanceCuenta();

        $this->assertInternalType('string', $model->install());
    }

    public function testSave()
    {
        $dataBase = new DataBase();

        $this->assertEquals(true, $dataBase->connect());

        $model = new BalanceCuenta();
        $sql = $model->install();

        if ($sql !== '') {
            $result = $dataBase->exec($sql);
            $this->assertFalse($result);
        }
    }

    public function testAll()
    {
        $model = new BalanceCuenta();
        $list = $model->all();

        if (!empty($list)) {
            $this->assertInternalType('array', $list);
        } else {
            $this->assertSame([], $list);
        }
    }
}
