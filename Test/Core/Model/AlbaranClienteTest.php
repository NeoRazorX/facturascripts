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
use FacturaScripts\Core\Model\AlbaranCliente;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AlbaranCliente
 */
final class AlbaranClienteTest extends TestCase
{
    public function testNewAlbaranCliente()
    {
        $model = new AlbaranCliente();
        $this->assertInstanceOf(AlbaranCliente::class, $model);
        $this->assertNull($model->apartado);
        $this->assertNull($model->cifnif);
        $this->assertNull($model->ciudad);
        $this->assertNull($model->codagente);
        $this->assertEquals(AppSettings::get('default', 'codalmacen'), $model->codalmacen);
        $this->assertEquals(null, $model->codcliente);
        $this->assertEquals(AppSettings::get('default', 'coddivisa'), $model->coddivisa);
        $this->assertEquals(null, $model->coddir);
        $this->assertEquals(null, $model->codejercicio);
        $this->assertEquals(null, $model->codigo);
        $this->assertEquals(AppSettings::get('default', 'codpago'), $model->codpago);
        $this->assertEquals(null, $model->codpostal);
        $this->assertEquals(AppSettings::get('default', 'codserie'), $model->codserie);
        $this->assertEquals(null, $model->direccion);
        $this->assertEquals(null, $model->codtrans);
        $this->assertEquals(null, $model->codigoenv);
        $this->assertEquals(null, $model->nombreenv);
        $this->assertEquals(null, $model->apellidosenv);
        $this->assertEquals(null, $model->apartadoenv);
        $this->assertEquals(null, $model->direccionenv);
        $this->assertEquals(null, $model->codpostalenv);
        $this->assertEquals(null, $model->ciudadenv);
        $this->assertEquals(null, $model->provinciaenv);
        $this->assertEquals(null, $model->codpaisenv);
        $this->assertEquals(date('d-m-Y'), $model->fecha);
        $this->assertEquals(null, $model->femail);
        $this->assertEquals(AppSettings::get('default', 'idempresa'), $model->idempresa);
        $this->assertEquals(0.0, $model->irpf);
        $this->assertEquals(0.0, $model->neto);
        $this->assertEquals(null, $model->nombrecliente);
        $this->assertEquals(null, $model->numero);
        $this->assertEquals(null, $model->numero2);
        $this->assertEquals(null, $model->porcomision);
        $this->assertEquals(null, $model->provincia);
        $this->assertEquals(1.0, $model->tasaconv);
        $this->assertEquals(0.0, $model->total);
        $this->assertEquals(0.0, $model->totaliva);
        $this->assertEquals(0.0, $model->totaleuros);
        $this->assertEquals(0.0, $model->totalirpf);
        $this->assertEquals(0.0, $model->totalrecargo);
        $this->assertEquals(null, $model->observaciones);
        $this->assertTrue($model->test());
    }

    public function testTable()
    {
        $model = new AlbaranCliente();
        $this->assertInternalType('string', $model::tableName());
    }

    public function testPrimaryColumn()
    {
        $model = new AlbaranCliente();
        $this->assertInternalType('string', $model::primaryColumn());
    }

    public function testInstall()
    {
        $model = new AlbaranCliente();
        $this->assertInternalType('string', $model->install());
    }

    public function testSave()
    {
        $dataBase = new DataBase();
        $this->assertEquals(true, $dataBase->connect());

        $model = new AlbaranCliente();
        $sql = $model->install();

        if ($sql !== '') {
            $result = $dataBase->exec($sql);
            $this->assertFalse($result);
        }
    }

    public function testAll()
    {
        $model = new AlbaranCliente();
        $list = $model->all();

        if (!empty($list)) {
            $this->assertInternalType('array', $list);
        } else {
            $this->assertSame([], $list);
        }
    }
}
