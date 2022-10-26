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

use FacturaScripts\Core\Model\GrupoClientes;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class GrupoClientesTest extends TestCase
{

    use LogErrorsTrait;

    public function testCreate()
    {
        $group = new GrupoClientes();
        $group->codgrupo = 'Test';
        $group->nombre = 'Test Customer Group';
        $this->assertTrue($group->save(), 'customer-group-cant-save');
        $this->assertNotNull($group->primaryColumnValue(), 'customer-group-not-stored');
        $this->assertTrue($group->exists(), 'customer-group-cant-persist');
        $this->assertTrue($group->delete(), 'customer-group-cant-delete');
    }

    public function testCreateWithNoCode()
    {
        $group = new GrupoClientes();
        $group->nombre = 'Test Customer Group';
        $this->assertTrue($group->save(), 'customer-group-cant-save');
        $this->assertTrue($group->delete(), 'customer-group-cant-delete');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
