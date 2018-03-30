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
use FacturaScripts\Core\Model\Stock;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Stock
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
final class StockTest extends TestCase
{
    public function testNewStock()
    {
        $model = new Stock();

        $this->assertInstanceOf(Stock::class, $model);
        $this->assertEquals(0, $model->cantidad);
        $this->assertEquals(0, $model->reservada);
        $this->assertEquals(0, $model->disponible);
        $this->assertEquals(0, $model->pterecibir);
        $this->assertEquals(0, $model->stockmin);
        $this->assertEquals(0, $model->stockmax);
        $this->assertTrue($model->test());

        $model->cantidad = 2;
        $model->reservada = 1;
        $model->disponible = 1;
        $model->stockmin = 1;
        $model->stockmax = 5;

        $this->assertTrue($model->test());
    }

    public function testTable()
    {
        $model = new Stock();

        $this->assertInternalType('string', $model::tableName());
    }

    public function testPrimaryColumn()
    {
        $model = new Stock();

        $this->assertInternalType('string', $model::primaryColumn());
    }

    public function testInstall()
    {
        $model = new Stock();

        $this->assertInternalType('string', $model->install());
    }

    public function testSave()
    {
        $dataBase = new DataBase();

        $this->assertEquals(true, $dataBase->connect());

        $model = new Stock();
        $sql = $model->install();

        if ($sql !== '') {
            $result = $dataBase->exec($sql);
            $this->assertFalse($result);
        }
    }

    public function testAll()
    {
        $model = new Stock();
        $list = $model->all();

        if (!empty($list)) {
            $this->assertInternalType('array', $list);
        } else {
            $this->assertSame([], $list);
        }
    }
}
