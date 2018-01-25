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
use FacturaScripts\Core\Model\Ejercicio;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ejercicio
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
final class EjercicioTest extends TestCase
{
    public function testNewEjercicio()
    {
        $model = new Ejercicio();

        $now = new \DateTime();
        $this->assertInstanceOf(Ejercicio::class, $model);
        $this->assertEquals('', $model->nombre);
        $this->assertFalse($model->test());

        $model->codejercicio = 'COD1';
        $model->nombre = 'Test name';
        $model->fechainicio = $now->add(new \DateInterval('P1Y'))->format('01-01-Y');
        $model->fechafin = $now->add(new \DateInterval('P1Y'))->format('31-12-Y');
        $model->estado = 'CERRADO';
        $model->longsubcuenta = 11;

        $this->assertTrue($model->test());
    }

    public function testTable()
    {
        $model = new Ejercicio();

        $this->assertInternalType('string', $model::tableName());
    }

    public function testPrimaryColumn()
    {
        $model = new Ejercicio();

        $this->assertInternalType('string', $model::primaryColumn());
    }

    public function testInstall()
    {
        $model = new Ejercicio();

        $this->assertInternalType('string', $model->install());
    }

    public function testSave()
    {
        $dataBase = new DataBase();

        $this->assertEquals(true, $dataBase->connect());

        $model = new Ejercicio();
        $sql = $model->install();

        if ($sql !== '') {
            $result = $dataBase->exec($sql);
            $this->assertFalse($result);
        }
    }

    public function testAll()
    {
        $model = new Ejercicio();
        $list = $model->all();

        if (!empty($list)) {
            $this->assertInternalType('array', $list);
        } else {
            $this->assertSame([], $list);
        }
    }
}
