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

use FacturaScripts\Core\Model\AgenciaTransporte;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class AgenciaTransporteTest extends TestCase
{
    use LogErrorsTrait;

    public function testDataInstalled(): void
    {
        // llamamos de forma estática
        $this->assertNotEmpty(AgenciaTransporte::all(), 'agency-data-not-installed-from-csv');

        // llamamos de forma dinámica
        $agency = new AgenciaTransporte();
        $this->assertNotEmpty($agency->all(), 'agency-data-not-installed-from-csv');
    }

    public function testCreate(): void
    {
        $agency = new AgenciaTransporte();
        $agency->codtrans = 'Test';
        $agency->nombre = 'Test Agency';
        $this->assertTrue($agency->save(), 'agency-cant-save');
        $this->assertNotNull($agency->primaryColumnValue(), 'agency-not-stored');
        $this->assertTrue($agency->exists(), 'agency-cant-persist');
        $this->assertTrue($agency->delete(), 'agency-cant-delete');
    }

    public function testCreateWithNewCode(): void
    {
        $agency = new AgenciaTransporte();
        $agency->nombre = 'Test Agency with new code';
        $this->assertTrue($agency->save(), 'agency-cant-save');
        $this->assertTrue($agency->delete(), 'agency-cant-delete');
    }

    public function testBadWeb(): void
    {
        $agency = new AgenciaTransporte();
        $agency->codtrans = 'Test';
        $agency->nombre = 'Test Agency';
        $agency->web = 'javascript:alert(origin)';
        $this->assertFalse($agency->save(), 'agency-can-save-bad-web');

        // javascript con forma de url
        $agency->web = 'javascript://example.com//%0aalert(document.domain);//';
        $this->assertFalse($agency->save(), 'agency-can-save-bad-web-2');

        // javascript con mayúsculas
        $agency->web = 'jAvAsCriPt://sadas.com/%0aalert(11);//';
        $this->assertFalse($agency->save(), 'agency-can-save-bad-web-3');
    }

    public function testGoodWeb(): void
    {
        $agency = new AgenciaTransporte();
        $agency->codtrans = 'Test';
        $agency->nombre = 'Test Agency';
        $agency->web = 'https://www.facturascripts.com';
        $this->assertTrue($agency->save(), 'agency-cant-save-good-web');
        $this->assertTrue($agency->delete(), 'agency-cant-delete');
    }

    public function testLoadFromData(): void
    {
        $agency = new AgenciaTransporte();
        $agency->loadFromData([
            'activo' => true,
            'codtrans' => 'Test',
            'nombre' => 'Test Agency',
            'telefono' => '+34 922 000 000',
            'web' => 'https://www.facturascripts.com'
        ]);

        $this->assertEquals(true, $agency->activo, 'agency-cant-load-activo');
        $this->assertEquals('Test', $agency->codtrans, 'agency-cant-load-codtrans');
        $this->assertEquals('Test Agency', $agency->nombre, 'agency-cant-load-nombre');
        $this->assertEquals('+34 922 000 000', $agency->telefono, 'agency-cant-load-telefono');
        $this->assertEquals('https://www.facturascripts.com', $agency->web, 'agency-cant-load-web');

        // ahora probamos a cambiar datos
        $agency->loadFromData([
            'activo' => false,
            'codtrans' => 'Test2',
            'nombre' => 'Test Agency 2',
            'telefono' => '+34 922 000 001',
            'web' => 'https://www.facturascripts.com/test'
        ]);

        $this->assertEquals(false, $agency->activo, 'agency-cant-load-activo-2');
        $this->assertEquals('Test2', $agency->codtrans, 'agency-cant-load-codtrans-2');
        $this->assertEquals('Test Agency 2', $agency->nombre, 'agency-cant-load-nombre-2');
        $this->assertEquals('+34 922 000 001', $agency->telefono, 'agency-cant-load-telefono-2');
        $this->assertEquals('https://www.facturascripts.com/test', $agency->web, 'agency-cant-load-web-2');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
