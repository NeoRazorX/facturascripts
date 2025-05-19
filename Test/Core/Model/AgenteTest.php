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

use FacturaScripts\Core\Lib\Vies;
use FacturaScripts\Core\Model\Agente;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class AgenteTest extends TestCase
{
    use LogErrorsTrait;

    public function testCreate(): void
    {
        $agent = new Agente();
        $agent->codagente = 'Test';
        $agent->nombre = 'Test Agent';
        $this->assertTrue($agent->save(), 'agent-cant-save');
        $this->assertNotNull($agent->primaryColumnValue(), 'agent-not-stored');
        $this->assertTrue($agent->exists(), 'agent-cant-persist');
        $this->assertTrue($agent->getContact()->delete(), 'contacto-cant-delete');
        $this->assertTrue($agent->delete(), 'agent-cant-delete');
    }

    public function testCreateWithNewCode(): void
    {
        $agent = new Agente();
        $agent->nombre = 'Test Agent with new code';
        $this->assertTrue($agent->save(), 'agent-cant-save');
        $this->assertTrue($agent->getContact()->delete(), 'contacto-cant-delete');
        $this->assertTrue($agent->delete(), 'agent-cant-delete');
    }

    public function testNotNullFields(): void
    {
        $agent = new Agente();
        $agent->codagente = 'Test';
        $agent->nombre = 'Test Agent';
        $this->assertTrue($agent->save(), 'agent-cant-save-2');

        // comprobamos que los teléfonos, fax, email y observaciones no sean nulos
        $this->assertNotNull($agent->telefono1, 'agent-telefono1-null');
        $this->assertNotNull($agent->telefono2, 'agent-telefono2-null');
        $this->assertNotNull($agent->fax, 'agent-fax-null');
        $this->assertNotNull($agent->email, 'agent-email-null');
        $this->assertNotNull($agent->observaciones, 'agent-observaciones-null');

        // eliminamos
        $this->assertTrue($agent->getContact()->delete(), 'contacto-cant-delete');
        $this->assertTrue($agent->delete(), 'agent-cant-delete-2');
    }

    public function testEmailField(): void
    {
        // probamos con un email mal formado
        $agent = new Agente();
        $agent->codagente = 'Test';
        $agent->nombre = 'Test Agent';
        $agent->email = 'test-test@';
        $this->assertFalse($agent->save(), 'agent-cant-save-3');

        // probamos con un email correcto
        $agent->email = 'pepe@facturascripts.com';
        $this->assertTrue($agent->save(), 'agent-cant-save-4');

        // eliminamos
        $this->assertTrue($agent->getContact()->delete(), 'contacto-cant-delete');
        $this->assertTrue($agent->delete(), 'agent-cant-delete-3');
    }

    public function testVies(): void
    {
        // creamos un agente sin cifnif
        $agent = new Agente();
        $agent->codagente = 'Test';
        $agent->nombre = 'Test Agent';

        $check1 = $agent->checkVies();
        if (Vies::getLastError() != '') {
            $this->markTestSkipped('Vies service error: ' . Vies::getLastError());
        }
        $this->assertFalse($check1);

        // asignamos un nif incorrecto
        $agent->cifnif = '12345678A';
        $check2 = $agent->checkVies();
        if (Vies::getLastError() != '') {
            $this->markTestSkipped('Vies service error: ' . Vies::getLastError());
        }
        $this->assertFalse($check2);

        // asignamos un cif correcto
        $agent->cifnif = 'B87533303';
        $check3 = $agent->checkVies();
        if (Vies::getLastError() != '') {
            $this->markTestSkipped('Vies service error: ' . Vies::getLastError());
        }
        $this->assertTrue($check3);
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
