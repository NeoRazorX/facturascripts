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

use FacturaScripts\Core\Model\Agente;
use FacturaScripts\Test\Core\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class AgenteTest extends TestCase
{
    use LogErrorsTrait;

    public function testCreate()
    {
        $agent = new Agente();
        $agent->codagente = 'Test';
        $agent->nombre = 'Test Agent';
        $this->assertTrue($agent->save(), 'agent-cant-save');
        $this->assertNotNull($agent->primaryColumnValue(), 'agent-not-stored');
        $this->assertTrue($agent->exists(), 'agent-cant-persist');
        $this->assertTrue($agent->delete(), 'agent-cant-delete');
    }

    public function testCreateWithNewCode()
    {
        $agent = new Agente();
        $agent->nombre = 'Test Agent with new code';
        $this->assertTrue($agent->save(), 'agent-cant-save');
        $this->assertTrue($agent->delete(), 'agent-cant-delete');
    }

    protected function tearDown()
    {
        $this->logErrors();
    }
}
