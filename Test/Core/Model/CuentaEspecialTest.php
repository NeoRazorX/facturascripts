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

use FacturaScripts\Core\Model\CuentaEspecial;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class CuentaEspecialTest extends TestCase
{
    use LogErrorsTrait;

    public function testDataInstalled()
    {
        $account = new CuentaEspecial();
        $this->assertNotEmpty($account->all(), 'account-special-data-not-installed-from-csv');
    }

    public function testCreate()
    {
        // creamos una cuenta
        $account = new CuentaEspecial();
        $account->codcuentaesp = 'Test';
        $account->descripcion = 'Test Special Account';
        $this->assertTrue($account->save(), 'account-special-cant-save');
        $this->assertTrue($account->exists(), 'account-special-cant-persist');

        // eliminamos
        $this->assertTrue($account->delete(), 'account-special-cant-delete');
    }

    public function testCreateHTMLDescription()
    {
        // creamos una cuenta con html en la descripción
        $account = new CuentaEspecial();
        $account->codcuentaesp = 'Test';
        $account->descripcion = 'Test <b>Special Account</b>';
        $this->assertTrue($account->save(), 'account-special-cant-save');

        // comprobamos que el html haya sido escapado
        $noHtml = Tools::noHtml('Test <b>Special Account</b>');
        $this->assertEquals($noHtml, $account->descripcion, 'account-html-description');

        // eliminamos
        $this->assertTrue($account->delete(), 'account-special-cant-delete');
    }

    public function testCreateWithoutCode()
    {
        $account = new CuentaEspecial();
        $account->descripcion = 'Test Special Account';
        $this->assertFalse($account->save(), 'account-special-cant-save-without-code');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
