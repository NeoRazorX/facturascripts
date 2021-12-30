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
use FacturaScripts\Core\Model\CuentaEspecial;
use FacturaScripts\Test\Core\LogErrorsTrait;
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
        $account = new CuentaEspecial();
        $account->codcuentaesp = 'Test';
        $account->descripcion = 'Test Special Account';
        $this->assertTrue($account->save(), 'account-special-cant-save');
        $this->assertNotNull($account->primaryColumnValue(), 'account-special-not-stored');
        $this->assertTrue($account->exists(), 'account-special-cant-persist');
        $this->assertTrue($account->delete(), 'account-special-cant-delete');
    }

    public function testCreateHTMLDescription()
    {
        $account = new CuentaEspecial();
        $account->codcuentaesp = 'Test';
        $account->descripcion = 'Test <b>Special Account</b>';
        $this->assertTrue($account->save(), 'account-special-cant-save');

        $account->loadFromCode($account->codcuentaesp);
        $description = $this->toolBox()->utils()->noHtml('Test <b>Special Account</b>');
        $this->assertTrue($account->descripcion == $description, 'account-html-descripcion');
        $this->assertTrue($account->delete(), 'account-special-cant-delete');
    }

    public function testCreateWrongCode()
    {
        $account = new CuentaEspecial();
        $account->descripcion = 'Test Special Account';
        $this->assertFalse($account->save(), 'account-special-cant-save-without-code');

        $account->codcuentaesp = 'Test 1';
        $this->assertFalse($account->save(), 'account-special-cant-save-with-spaces');
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
