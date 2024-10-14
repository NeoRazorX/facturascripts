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
use FacturaScripts\Core\Model\Provincia;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class CiudadTest extends TestCase
{
    use LogErrorsTrait;

    public function testCreate()
    {
        $provinceModel = new Provincia();
        foreach ($provinceModel->all() as $provincia) {
            // creamos una ciudad
            $city = new Ciudad();
            $city->ciudad = 'Test';
            $city->idprovincia = $provincia->idprovincia;
            $this->assertTrue($city->save(), 'city-cant-save');

            // comprobamos que existe en la base de datos
            $this->assertTrue($city->exists(), 'city-cant-persist');

            // eliminamos
            $this->assertTrue($city->delete(), 'city-cant-delete');
        }
    }

    public function testCreateHtml()
    {
        $provinceModel = new Provincia();
        foreach ($provinceModel->all() as $provincia) {
            // creamos una ciudad con un nombre con html
            $city = new Ciudad();
            $city->ciudad = '<b>Test</b>';
            $city->idprovincia = $provincia->idprovincia;
            $this->assertTrue($city->save(), 'city-cant-save');

            // comprobamos que el html ha sido escapado
            $noHtml = Tools::noHtml('<b>Test</b>');
            $this->assertEquals($noHtml, $city->ciudad, 'city-wrong-html');

            // eliminamos
            $this->assertTrue($city->delete(), 'city-cant-delete');
        }
    }

    public function testCreateWithoutProvince()
    {
        // creamos una ciudad sin provincia
        $city = new Ciudad();
        $city->ciudad = 'Test';
        $this->assertFalse($city->save(), 'city-must-have-province');

        // asignamos una provincia que no existe
        $city->idprovincia = -1;
        $this->assertFalse($city->save(), 'city-must-exist-province');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
