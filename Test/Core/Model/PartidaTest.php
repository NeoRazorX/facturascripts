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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Model\Partida;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Partida
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
final class PartidaTest extends TestCase
{

    public function testNewPartida()
    {
        $model = new Partida();

        $this->assertInstanceOf(Partida::class, $model);
        $this->assertEquals('', $model->concepto);
        $this->assertFalse($model->punteada);
        $this->assertEquals(1.0, $model->tasaconv);
        $this->assertEquals(AppSettings::get('default', 'coddivisa'), $model->coddivisa);
        $this->assertEquals(0.0, $model->haberme);
        $this->assertEquals(0.0, $model->debeme);
        $this->assertEquals(0.0, $model->recargo);
        $this->assertEquals(0.0, $model->iva);
        $this->assertEquals(0.0, $model->baseimponible);
        $this->assertEquals(0.0, $model->debe);
        $this->assertEquals(0.0, $model->haber);
        $this->assertFalse($model->test());
    }

    public function testTable()
    {
        $model = new Partida();

        $this->assertInternalType('string', $model::tableName());
    }

    public function testPrimaryColumn()
    {
        $model = new Partida();

        $this->assertInternalType('string', $model::primaryColumn());
    }

    public function testInstall()
    {
        $model = new Partida();

        $this->assertInternalType('string', $model->install());
    }

    public function testSave()
    {
        $dataBase = new DataBase();

        $this->assertEquals(true, $dataBase->connect());

        $model = new Partida();
        $sql = $model->install();

        if ($sql !== '') {
            $result = $dataBase->exec($sql);
            $this->assertFalse($result);
        }
    }

    public function testAll()
    {
        $model = new Partida();
        $list = $model->all();

        if (!empty($list)) {
            $this->assertInternalType('array', $list);
        } else {
            $this->assertSame([], $list);
        }
    }
}
