<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Retencion;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class RetencionTest extends TestCase
{
    use LogErrorsTrait;

    public function testCreate(): void
    {
        // creamos una retención
        $retencion = new Retencion();
        $retencion->codretencion = 'TEST15';
        $retencion->descripcion = 'Test Retención 15%';
        $retencion->porcentaje = 15;
        $retencion->codsubcuentaret = '4750001';
        $retencion->codsubcuentaacr = '4750002';
        $this->assertTrue($retencion->save());

        // comprobamos que existe en la base de datos
        $this->assertTrue($retencion->exists());

        // comprobamos valores por defecto
        $this->assertTrue($retencion->activa);

        // eliminamos
        $this->assertTrue($retencion->delete());
    }

    public function testCreateWithInvalidCode(): void
    {
        // creamos una retención con código inválido (demasiado largo)
        $retencion = new Retencion();
        $retencion->codretencion = 'CODIGO_DEMASIADO_LARGO';
        $retencion->descripcion = 'Test Retención';
        $retencion->porcentaje = 15;
        $this->assertFalse($retencion->save(), 'code-too-long-should-fail');

        // código con caracteres inválidos
        $retencion->codretencion = 'TEST@';
        $this->assertFalse($retencion->save(), 'invalid-characters-should-fail');
    }

    public function testCreateWithInvalidPercentage(): void
    {
        // creamos una retención con porcentaje inválido (0)
        $retencion = new Retencion();
        $retencion->codretencion = 'TEST0';
        $retencion->descripcion = 'Test Retención 0%';
        $retencion->porcentaje = 0;
        $this->assertFalse($retencion->save(), 'zero-percentage-should-fail');

        // porcentaje negativo
        $retencion->porcentaje = -5;
        $this->assertFalse($retencion->save(), 'negative-percentage-should-fail');
    }

    public function testCreateWithoutCode(): void
    {
        // sin código, debe usar 3 letras de la descripción + porcentaje
        $retencion = new Retencion();
        $retencion->descripcion = 'Test Retención';
        $retencion->porcentaje = 20;
        $this->assertTrue($retencion->save());
        $this->assertEquals('TES20', $retencion->codretencion);
        $this->assertTrue($retencion->delete());
    }

    public function testCreateWithoutCodeFallsBackTo4Letters(): void
    {
        // ocupamos 'TES15'
        $retencion1 = new Retencion();
        $retencion1->codretencion = 'TES15';
        $retencion1->descripcion = 'TES15 ocupado';
        $retencion1->porcentaje = 15;
        $this->assertTrue($retencion1->save());

        // al estar 'TES15' ocupado, debe usar 'TEST15' (4 letras + porcentaje)
        $retencion2 = new Retencion();
        $retencion2->descripcion = 'Test Retención';
        $retencion2->porcentaje = 15;
        $this->assertTrue($retencion2->save());
        $this->assertEquals('TEST15', $retencion2->codretencion);

        // eliminamos
        $this->assertTrue($retencion2->delete());
        $this->assertTrue($retencion1->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
