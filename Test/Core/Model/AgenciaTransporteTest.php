<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\AgenciaTransporte;
use FacturaScripts\Test\Core\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class AgenciaTransporteTest extends TestCase
{
    use LogErrorsTrait;

    public function testDataInstalled()
    {
        $agency = new AgenciaTransporte();
        $this->assertNotEmpty($agency->all(), 'agency-data-not-installed-from-csv');
    }

    public function testCreate()
    {
        $agency = new AgenciaTransporte();
        $agency->codtrans = 'Test';
        $agency->nombre = 'Test Agency';
        $this->assertTrue($agency->save(), 'agency-cant-save');
        $this->assertNotNull($agency->primaryColumnValue(), 'agency-not-stored');
        $this->assertTrue($agency->exists(), 'agency-cant-persist');
        $this->assertTrue($agency->delete(), 'agency-cant-delete');
    }

    public function testCreateWithNewCode()
    {
        $agency = new AgenciaTransporte();
        $agency->nombre = 'Test Agency with new code';
        $this->assertTrue($agency->save(), 'agency-cant-save');
        $this->assertTrue($agency->delete(), 'agency-cant-delete');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
