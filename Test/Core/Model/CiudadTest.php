<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2022  Carlos Garcia Gomez     <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Model\Ciudad;
use FacturaScripts\Core\Model\Provincia;
use FacturaScripts\Test\Core\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class CiudadTest extends TestCase
{
    use LogErrorsTrait;

    public function testDataInstalled()
    {
        $city = new Ciudad();
        $this->assertNotEmpty($city->all(), 'city-data-not-installed-from-csv');
    }

    public function testCreate()
    {
        $province = new Provincia();
        $province->provincia = 'Test';
        $province->codpais = 'ESP';
        $this->assertTrue($province->save(), 'province-cant-save');

        $city = new Ciudad();
        $city->ciudad = 'Test';
        $city->idprovincia = $province->idprovincia;
        $this->assertTrue($city->save(), 'city-cant-save');
        $this->assertNotNull($city->primaryColumnValue(), 'agency-not-stored');
        $this->assertTrue($city->exists(), 'city-cant-persist');
        $this->assertTrue($city->delete(), 'city-cant-delete');
        $this->assertTrue($province->delete(), 'province-cant-delete');
    }

    public function testCreateHtml()
    {
        $province = new Provincia();
        $province->provincia = 'Test';
        $province->codpais = 'ESP';
        $this->assertTrue($province->save(), 'province-cant-save');

        $city = new Ciudad();
        $city->ciudad = '<b>Test</b>';
        $city->idprovincia = $province->idprovincia;
        $this->assertTrue($city->save(), 'city-cant-save');

        $description = $this->toolBox()->utils()->noHtml('<b>Test</b>');
        $city->loadFromCode($city->idciudad);
        $this->assertTrue($city->ciudad == $description, 'city-wrong-html');
        $this->assertTrue($city->delete(), 'city-cant-delete');
        $this->assertTrue($province->delete(), 'province-cant-delete');
    }

    public function testCreateWithoutProvince()
    {
        $city = new Ciudad();
        $city->ciudad = 'Test';
        $this->assertFalse($city->save(), 'city-must-have-province');

        $city->idprovincia = -1;
        $this->assertFalse($city->save(), 'city-must-exist-province');
    }

    protected function tearDown()
    {
        $this->logErrors();
    }

    protected function tools()
    {
        return new ToolBox();
    }
}
