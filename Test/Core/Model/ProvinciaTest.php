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
use PHPUnit\Framework\TestCase;

final class ProvinciaTest extends TestCase
{
    use LogErrorsTrait;

    public function testDataInstalled()
    {
        $state = new Provincia();
        $this->assertNotEmpty($state->all(), 'state-data-not-installed-from-csv');
    }

    public function testCreate()
    {
        $state = new Provincia();
        $state->provincia = 'Test';
        $state->codpais = 'ESP';
        $this->assertTrue($state->save(), 'state-cant-save');
        $this->assertTrue($state->exists(), 'state-cant-persist');

        // eliminamos
        $this->assertTrue($state->delete(), 'state-cant-delete');
    }

    public function testCreateWithoutCountry()
    {
        $state = new Provincia();
        $state->provincia = 'Test without country';
        $state->codpais = 'XXX';
        $this->assertFalse($state->save(), 'state-can-save');
    }

    public function testCreateHtml()
    {
        // creamos contenido con html
        $state = new Provincia();
        $state->codpais = 'ESP';
        $state->provincia = '<b>Test Html</b>';
        $this->assertTrue($state->save(), 'state-cant-save');

        // comprobamos que el html ha sido escapado
        $noHtml = Tools::noHtml('<b>Test Html</b>');
        $this->assertEquals($noHtml, $state->provincia, 'state-wrong-html');

        // eliminamos
        $this->assertTrue($state->delete(), 'state-cant-delete');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
