<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024  Carlos Garcia Gomez     <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Familia;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class FamiliaTest extends TestCase
{
    use LogErrorsTrait;

    public function testCreate()
    {
        $family = new Familia();
        $family->codfamilia = 'Test';
        $family->descripcion = 'Test Family';
        $this->assertTrue($family->save(), 'family-cant-save');
        $this->assertNotNull($family->primaryColumnValue(), 'family-not-stored');
        $this->assertTrue($family->exists(), 'family-cant-persist');

        // eliminamos
        $this->assertTrue($family->delete(), 'family-cant-delete');
    }

    public function testCreateWithoutCode()
    {
        $family = new Familia();
        $family->descripcion = 'Test without code';
        $this->assertTrue($family->save(), 'family-cant-save');

        // eliminamos
        $this->assertTrue($family->delete(), 'family-cant-delete');
    }

    public function testCreateSpaceCode()
    {
        $family = new Familia();
        $family->codfamilia = 'Te st';
        $family->descripcion = 'Test with space code';
        $this->assertFalse($family->save(), 'family-can-save');
    }

    public function testCreateHtml()
    {
        // creamos contenido con html
        $family = new Familia();
        $family->codfamilia = 'Test';
        $family->descripcion = '<b>Test Html</b>';
        $this->assertTrue($family->save(), 'family-cant-save');

        // comprobamos que el html ha sido escapado
        $noHtml = Tools::noHtml('<b>Test Html</b>');
        $this->assertEquals($noHtml, $family->descripcion, 'family-wrong-html');

        // eliminamos
        $this->assertTrue($family->delete(), 'family-cant-delete');
    }

    public function testCreateMother()
    {
        $family = new Familia();
        $family->codfamilia = 'Test';
        $family->descripcion = 'Test Mother';
        $family->madre = 'Test';
        $this->assertTrue($family->save(), 'family-cant-save');
        $this->assertNull($family->madre, 'family-bad-mother');

        // eliminamos
        $this->assertTrue($family->delete(), 'family-cant-delete');
    }

    public function testFamiliesMother()
    {
        $family1 = new Familia();
        $family1->codfamilia = 'Test1';
        $family1->descripcion = 'Test 1';
        $this->assertTrue($family1->save(), 'family1-cant-save');

        $family2 = new Familia();
        $family2->codfamilia = 'Test2';
        $family2->descripcion = 'Test 2';
        $family2->madre = 'Test1';
        $this->assertTrue($family2->save(), 'family2-cant-save');

        $family1->madre = 'Test2';
        $this->assertFalse($family1->save(), 'family2-can-loop');

        // eliminamos
        $this->assertTrue($family1->delete(), 'family1-cant-delete');
        $this->assertTrue($family2->delete(), 'family1-cant-delete');
    }

    public function testCreateSubaccount()
    {
        $family = new Familia();
        $family->codfamilia = 'Test';
        $family->descripcion = 'Test Subaccount';
        $family->codsubcuentacom = '0000000000';
        $family->codsubcuentairpfcom = '0000000000';
        $family->codsubcuentaven = '0000000000';
        $this->assertFalse($family->save(), 'family-can-save');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
