<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017    Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
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
use FacturaScripts\Core\Model\Agente;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Agente
 */
final class AgenteTest extends TestCase
{
    public function testNewAgente()
    {
        $model = new Agente();

        $this->assertInstanceOf(
            Agente::class,
            $model
        );

        $this->assertNull(
            $model->codagente
        );

        $this->assertEquals(
            '',
            $model->nombre
        );

        $this->assertEquals(
            '',
            $model->apellidos
        );

        $this->assertEquals(
            '',
            $model->cifnif
        );

        $this->assertEquals(
            0.00,
            $model->porcomision
        );

        $this->assertEquals(
            null,
            $model->seg_social
        );

        $this->assertEquals(
            null,
            $model->banco
        );

        $this->assertEquals(
            null,
            $model->cargo
        );

        $this->assertEquals(
            date('d-m-Y'),
            $model->fechaalta
        );

        $this->assertEquals(
            null,
            $model->fechabaja
        );

        $this->assertEquals(
            null,
            $model->fechanacimiento
        );

        $this->assertInternalType(
            'string',
            $model->primaryDescription()
        );

        $this->assertTrue($model->test());
    }

    public function testTable()
    {
        $model = new Agente();

        $this->assertInternalType(
            'string',
            $model::tableName()
        );
    }

    public function testPrimaryColumn()
    {
        $model = new Agente();

        $this->assertInternalType(
            'string',
            $model::primaryColumn()
        );
    }

    public function testInstall()
    {
        $model = new Agente();

        $this->assertInternalType(
            'string',
            $model->install()
        );
    }

    public function testSave()
    {
        $dataBase = new DataBase();

        $this->assertEquals(true, $dataBase->connect());

        $model = new Agente();
        $sql = $model->install();

        if ($sql !== '') {
            $result = $dataBase->exec($sql);
            $this->assertFalse($result);
        }
    }

    public function testAll()
    {
        $model = new Agente();
        $list = $model->all();

        if (!empty($list)) {
            $this->assertInternalType('array', $list);
        } else {
            $this->assertSame([], $list);
        }
    }
}
