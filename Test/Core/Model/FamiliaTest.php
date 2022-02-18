<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021  Carlos Garcia Gomez     <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Model\Familia;
use FacturaScripts\Core\Model\Subcuenta;
use FacturaScripts\Test\Core\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class FamiliaTest extends TestCase
{
    use LogErrorsTrait;

    protected function tearDown()
    {
        $this->logErrors();
    }

    public function testCreate()
    {
        $family = new Familia();
        $family->codfamilia = 'Test';
        $family->descripcion = 'Test Family';
        $this->assertTrue($family->save(), 'family-cant-save');
        $this->assertNotNull($family->primaryColumnValue(), 'family-not-stored');
        $this->assertTrue($family->exists(), 'family-cant-persist');
        $this->assertTrue($family->delete(), 'family-cant-delete');
    }

    public function testCreateSubaccount()
    {
        $subaccount = new Subcuenta();
        $family = new Familia();
        $family->codfamilia = 'Test';
        $family->descripcion = 'Test Subaccount';
        $family->codsubcuentacom = '1000000000';
        $family->codsubcuentairpfcom = '1000000000';
        $family->codsubcuentaven = '1000000000';

        $where = [new DataBaseWhere('codsubcuenta', $family->codsubcuentacom)];
        if (false === empty($family->codsubcuentacom) && false === $subaccount->loadFromCode('', $where)) {
            $this->assertFalse($family->save(), 'family-can-save');
        }

        $where = [new DataBaseWhere('codsubcuenta', $family->codsubcuentairpfcom)];
        if (false === empty($family->codsubcuentairpfcom) && false === $subaccount->loadFromCode('', $where)) {
            $this->assertFalse($family->save(), 'family-can-save');
        }

        $where = [new DataBaseWhere('codsubcuenta', $family->codsubcuentaven)];
        if (false === empty($family->codsubcuentaven) && false === $subaccount->loadFromCode('', $where)) {
            $this->assertFalse($family->save(), 'family-can-save');
        }
    }

    public function testCreateHtml()
    {
        // creamos contenido con html
        $family = new Familia();
        $family->codfamilia = 'Test';
        $family->descripcion = '<b>Test Html</b>';
        $this->assertTrue($family->save(), 'family-cant-save');

        // comprobamos que el html ha sido escapado
        $noHtml = ToolBox::utils()::noHtml('<b>Test Html</b>');
        $this->assertEquals($noHtml, $family->descripcion, 'family-wrong-html');

        // eliminamos
        $this->assertTrue($family->delete(), 'family-cant-delete');
    }

    public function testCreateMother()
    {
        $family = new Familia();
        $family->codfamilia = 'Test';
        $family->descripcion = 'Test Mother';
        $family->madre = 'Test2';

        if ($family->codfamilia === $family->madre) {
            $this->assertFalse($family->save(), 'family-can-save');
        }
    }

    public function testCreateSpaceCode()
    {
        $family = new Familia();
        $family->codfamilia = 'Te st';
        $family->descripcion = 'Test with space code';
        $this->assertFalse($family->save(), 'family-can-save');
    }

    public function testCreateWithoutCode()
    {
        $family = new Familia();
        $family->descripcion = 'Test without code';
        $this->assertTrue($family->save(), 'family-cant-save');
    }

    public function testDataInstalled()
    {
        $family = new Familia();
        $this->assertNotEmpty($family->all(), 'family-data-not-installed-from-csv');
    }
}