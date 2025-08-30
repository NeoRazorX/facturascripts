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

use FacturaScripts\Core\Model\Provincia;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class ProvinciaTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    public function testDataInstalled(): void
    {
        $this->assertNotEmpty(Provincia::all(), 'state-data-not-installed-from-csv');
    }

    public function testCreate(): void
    {
        // creamos un pais
        $country = $this->getRandomCountry();
        $this->assertTrue($country->save());

        $province = new Provincia();
        $province->provincia = 'Test';
        $province->codpais = $country->codpais;
        $this->assertTrue($province->save(), 'state-cant-save');
        $this->assertTrue($province->exists(), 'state-cant-persist');

        // eliminamos
        $this->assertTrue($province->delete());
        $this->assertTrue($country->delete());
    }

    public function testCreateWithoutCountry(): void
    {
        $province = new Provincia();
        $province->provincia = 'Test without country';
        $province->codpais = 'XXX';
        $this->assertFalse($province->save());
    }

    public function testCreateHtml(): void
    {
        // creamos contenido con html
        $province = new Provincia();
        $province->codpais = 'ESP';
        $province->provincia = '<b>Test Html</b>';
        $this->assertTrue($province->save());

        // comprobamos que el html ha sido escapado
        $noHtml = Tools::noHtml('<b>Test Html</b>');
        $this->assertEquals($noHtml, $province->provincia, 'state-wrong-html');

        // eliminamos
        $this->assertTrue($province->delete());
    }

    public function testDeleteCountry(): void
    {
        // creamos un pais
        $country = $this->getRandomCountry();
        $this->assertTrue($country->save());

        // creamos una provincia
        $province = new Provincia();
        $province->provincia = 'Test';
        $province->codpais = $country->codpais;
        $this->assertTrue($province->save());

        // eliminamos el pais
        $this->assertTrue($country->delete());

        // comprobamos que la provincia se ha eliminado
        $this->assertFalse($province->exists());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
