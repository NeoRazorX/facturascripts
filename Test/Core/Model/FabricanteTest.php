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

use FacturaScripts\Core\Model\Fabricante;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class FabricanteTest extends TestCase
{
    use LogErrorsTrait;

    public function testCreate()
    {
        $manufacturer = new Fabricante();
        $manufacturer->codfabricante = 'Test';
        $manufacturer->nombre = 'Test Manufacturer';
        $this->assertTrue($manufacturer->save(), 'manufacturer-cant-save');
        $this->assertNotNull($manufacturer->primaryColumnValue(), 'manufacturer-not-stored');
        $this->assertTrue($manufacturer->exists(), 'manufacturer-cant-persist');

        // eliminamos
        $this->assertTrue($manufacturer->delete(), 'manufacturer-cant-delete');
    }

    public function testCreateHtml()
    {
        // creamos contenido con html
        $manufacturer = new Fabricante();
        $manufacturer->codfabricante = 'Test';
        $manufacturer->nombre = '<b>Test Manufacturer</b>';
        $this->assertTrue($manufacturer->save(), 'manufacturer-cant-save');

        // comprobamos que el html ha sido escapado
        $noHtml = Tools::noHtml('<b>Test Manufacturer</b>');
        $this->assertEquals($noHtml, $manufacturer->nombre, 'manufacturer-wrong-html');

        // eliminamos
        $this->assertTrue($manufacturer->delete(), 'manufacturer-cant-delete');
    }

    public function testCreateWithNewCode()
    {
        $manufacturer = new Fabricante();
        $manufacturer->nombre = 'Test Manufacturer with new code';
        $this->assertTrue($manufacturer->save(), 'manufacturer-cant-save');

        // guardamos el codfabricante original
        $codfabricante = $manufacturer->codfabricante;

        // No se puede añadir un código con espacios
        $manufacturer->codfabricante = 'Te st';
        $this->assertFalse($manufacturer->save(), 'manufacturer-can-save');

        // reestablecemos el código original
        $manufacturer->codfabricante = $codfabricante;

        // eliminamos
        $this->assertTrue($manufacturer->delete(), 'manufacturer-cant-delete');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
