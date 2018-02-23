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
use FacturaScripts\Core\Model\Asiento;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Asiento
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
final class AsientoTest extends TestCase
{
    public function testNewAsiento()
    {
        $model = new Asiento();

        $this->assertInstanceOf(Asiento::class, $model);
        $this->assertEquals(date('d-m-Y'), $model->fecha);
        $this->assertEquals(true, $model->editable);
        $this->assertEquals(0.0, $model->importe);
        $this->assertFalse($model->test());
    }

    public function testTable()
    {
        $model = new Asiento();

        $this->assertInternalType('string', $model::tableName());
    }

    public function testPrimaryColumn()
    {
        $model = new Asiento();

        $this->assertInternalType('string', $model::primaryColumn());
    }

    public function testInstall()
    {
        $model = new Asiento();

        $this->assertInternalType('string', $model->install());
    }

    public function testSave()
    {
        $dataBase = new DataBase();

        $this->assertEquals(true, $dataBase->connect());

        $model = new Asiento();
        $sql = $model->install();

        if ($sql !== '') {
            $result = $dataBase->exec($sql);
            $this->assertFalse($result);
        }
    }

    public function testAll()
    {
        $model = new Asiento();
        $list = $model->all();

        if (!empty($list)) {
            $this->assertInternalType('array', $list);
        } else {
            $this->assertSame([], $list);
        }
    }
}
