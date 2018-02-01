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
use FacturaScripts\Core\Model\ArticuloCombinacion;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ArticuloCombinacion
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
final class ArticuloCombinacionTest extends TestCase
{
    public function testNewArticuloCombinacion()
    {
        $model = new ArticuloCombinacion();

        $this->assertInstanceOf(ArticuloCombinacion::class, $model);
        $this->assertNull($model->codbarras);
        $this->assertEquals(0, $model->impactoprecio);
        $this->assertEquals(0, $model->stockfis);
        $this->assertTrue($model->test());

        $model->codigo = 'REF1';
        $model->nombreatributo = 'AC1';

        $this->assertTrue($model->test());
    }

    public function testTable()
    {
        $model = new ArticuloCombinacion();

        $this->assertInternalType('string', $model::tableName());
    }

    public function testPrimaryColumn()
    {
        $model = new ArticuloCombinacion();

        $this->assertInternalType('string', $model::primaryColumn());
    }

    public function testInstall()
    {
        $model = new ArticuloCombinacion();

        $this->assertInternalType('string', $model->install());
    }

    public function testSave()
    {
        $dataBase = new DataBase();

        $this->assertEquals(true, $dataBase->connect());

        $model = new ArticuloCombinacion();
        $sql = $model->install();

        if ($sql !== '') {
            $result = $dataBase->exec($sql);
            $this->assertFalse($result);
        }
    }

    public function testAll()
    {
        $model = new ArticuloCombinacion();
        $list = $model->all();

        if (!empty($list)) {
            $this->assertInternalType('array', $list);
        } else {
            $this->assertSame([], $list);
        }
    }
}
