<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017    Francesc Pineda Segarra    <francesc.pineda.segarra@gmail.com>
 * Copyright (C) 2018    Carlos García Gómez        <carlos@facturascripts.com>
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
use FacturaScripts\Core\Model\AlbaranProveedor;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AlbaranProveedor
 */
final class AlbaranProveedorTest extends TestCase
{
    public function testNewAlbaranProveedor()
    {
        $model = new AlbaranProveedor();

        $this->assertInstanceOf(AlbaranProveedor::class, $model);
        $this->assertNull($model->cifnif);
        $this->assertEquals(AppSettings::get('default', 'codalmacen'), $model->codalmacen);
        $this->assertEquals(null, $model->codproveedor);
        $this->assertEquals(AppSettings::get('default', 'coddivisa'), $model->coddivisa);
        $this->assertEquals(null, $model->codejercicio);
        $this->assertEquals(null, $model->codigo);
        $this->assertEquals(AppSettings::get('default', 'codpago'), $model->codpago);
        $this->assertEquals(AppSettings::get('default', 'codserie'), $model->codserie);
        $this->assertEquals(date('d-m-Y'), $model->fecha);
        $this->assertEquals(AppSettings::get('default', 'idempresa'), $model->idempresa);
        $this->assertEquals(0.0, $model->irpf);
        $this->assertEquals(0.0, $model->neto);
        $this->assertEquals(null, $model->nombre);
        $this->assertEquals(null, $model->numero);
        $this->assertEquals(null, $model->numproveedor);
        $this->assertEquals(1.0, $model->tasaconv);
        $this->assertEquals(0.0, $model->total);
        $this->assertEquals(0.0, $model->totaliva);
        $this->assertEquals(0.0, $model->totaleuros);
        $this->assertEquals(0.0, $model->totalirpf);
        $this->assertEquals(0.0, $model->totalrecargo);
        $this->assertEquals(null, $model->observaciones);
        $this->assertEquals(null, $model->idalbaran);
        $this->assertEquals(null, $model->idfactura);
        $this->assertTrue($model->test());
    }

    public function testTable()
    {
        $model = new AlbaranProveedor();

        $this->assertInternalType('string', $model::tableName());
    }

    public function testPrimaryColumn()
    {
        $model = new AlbaranProveedor();

        $this->assertInternalType('string', $model::primaryColumn());
    }

    public function testInstall()
    {
        $model = new AlbaranProveedor();

        $this->assertInternalType('string', $model->install());
    }

    public function testSave()
    {
        $dataBase = new DataBase();

        $this->assertEquals(true, $dataBase->connect());

        $model = new AlbaranProveedor();
        $sql = $model->install();

        if ($sql !== '') {
            $result = $dataBase->exec($sql);
            $this->assertFalse($result);
        }
    }

    public function testAll()
    {
        $model = new AlbaranProveedor();
        $list = $model->all();

        if (!empty($list)) {
            $this->assertInternalType('array', $list);
        } else {
            $this->assertSame([], $list);
        }
    }
}
