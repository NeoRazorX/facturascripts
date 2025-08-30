<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Test\Core\Model;

use FacturaScripts\Core\Model\Ciudad;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class CiudadTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    public function testCreate(): void
    {
        // creamos un país
        $country = $this->getRandomCountry();
        $this->assertTrue($country->save());

        // creamos una provincia
        $province = $this->getRandomProvince($country->codpais);
        $this->assertTrue($province->save());

        // creamos una ciudad
        $city = new Ciudad();
        $city->ciudad = 'Test';
        $city->idprovincia = $province->idprovincia;
        $this->assertTrue($city->save());

        // comprobamos que existe en la base de datos
        $this->assertTrue($city->exists());

        // comprobamos que la provincia es la correcta
        $this->assertEquals($province->idprovincia, $city->getProvince()->idprovincia);

        // eliminamos
        $this->assertTrue($city->delete());
        $this->assertTrue($province->delete());
        $this->assertTrue($country->delete());
    }

    public function testCreateHtml(): void
    {
        // creamos un país
        $country = $this->getRandomCountry();
        $this->assertTrue($country->save());

        // creamos una provincia
        $province = $this->getRandomProvince($country->codpais);
        $this->assertTrue($province->save());

        // creamos una ciudad con un nombre con html
        $city = new Ciudad();
        $city->ciudad = '<b>Test</b>';
        $city->idprovincia = $province->idprovincia;
        $this->assertTrue($city->save());

        // comprobamos que el html ha sido escapado
        $noHtml = Tools::noHtml('<b>Test</b>');
        $this->assertEquals($noHtml, $city->ciudad);

        // eliminamos
        $this->assertTrue($city->delete());
        $this->assertTrue($province->delete());
        $this->assertTrue($country->delete());
    }

    public function testCreateWithoutProvince(): void
    {
        // creamos una ciudad sin provincia
        $city = new Ciudad();
        $city->ciudad = 'Test';
        $this->assertFalse($city->save(), 'city-must-have-province');

        // asignamos una provincia que no existe
        $city->idprovincia = -1;
        $this->assertFalse($city->save(), 'city-must-exist-province');
    }

    public function testDeleteProvince(): void
    {
        // creamos un país
        $country = $this->getRandomCountry();
        $this->assertTrue($country->save());

        // creamos una provincia
        $province = $this->getRandomProvince($country->codpais);
        $this->assertTrue($province->save());

        // creamos una ciudad
        $city = new Ciudad();
        $city->ciudad = 'Test';
        $city->idprovincia = $province->idprovincia;
        $this->assertTrue($city->save());

        // eliminamos la provincia
        $this->assertTrue($province->delete());

        // comprobamos que la ciudad ya no existe
        $this->assertFalse($city->exists());

        // eliminamos
        $this->assertTrue($country->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
