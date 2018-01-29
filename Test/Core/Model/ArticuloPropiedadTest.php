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
use FacturaScripts\Core\Model\ArticuloPropiedad;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ArticuloPropiedad
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
final class ArticuloPropiedadTest extends TestCase
{
    public function testNewArticuloPropiedad()
    {
        $model = new ArticuloPropiedad();

        $this->assertInstanceOf(ArticuloPropiedad::class, $model);

        $ref = 'REF1';
        $values = [
            'Name1' => 'Text1',
            'Name2' => 'Text2',
            'Name3' => 'Text3',
            'Name4' => 'Text4',
            'Name5' => 'Text5',
        ];

        $this->assertFalse($model->arraySave($ref, $values));
    }

    public function testTable()
    {
        $model = new ArticuloPropiedad();

        $this->assertInternalType('string', $model::tableName());
    }

    public function testPrimaryColumn()
    {
        $model = new ArticuloPropiedad();

        $this->assertInternalType('string', $model::primaryColumn());
    }

    public function testInstall()
    {
        $model = new ArticuloPropiedad();

        $this->assertInternalType('string', $model->install());
    }

    public function testSave()
    {
        $dataBase = new DataBase();

        $this->assertEquals(true, $dataBase->connect());

        $model = new ArticuloPropiedad();
        $sql = $model->install();

        if ($sql !== '') {
            $result = $dataBase->exec($sql);
            $this->assertFalse($result);
        }
    }

    public function testAll()
    {
        $model = new ArticuloPropiedad();
        $ref = 'REF1';
        $list = $model->arrayGet($ref);

        if (!empty($list)) {
            $this->assertInternalType('array', $list);
        } else {
            $this->assertSame([], $list);
        }
    }
}
