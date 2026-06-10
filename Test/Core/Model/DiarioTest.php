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

use FacturaScripts\Core\Model\Diario;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class DiarioTest extends TestCase
{
    use LogErrorsTrait;

    public function testCreate(): void
    {
        // creamos un diario
        $diario = new Diario();
        $diario->descripcion = 'Test Diario';
        $this->assertTrue($diario->save());

        // comprobamos que existe en la base de datos
        $this->assertTrue($diario->exists());

        // comprobamos que se ha asignado un id
        $this->assertNotNull($diario->iddiario);

        // eliminamos
        $this->assertTrue($diario->delete());
    }

    public function testCreateHtml(): void
    {
        // creamos un diario con descripción con html
        $diario = new Diario();
        $diario->descripcion = '<b>Test Diario</b>';
        $this->assertTrue($diario->save());

        // comprobamos que el html ha sido escapado
        $noHtml = Tools::noHtml('<b>Test Diario</b>');
        $this->assertEquals($noHtml, $diario->descripcion);

        // eliminamos
        $this->assertTrue($diario->delete());
    }

    public function testCreateEmpty(): void
    {
        // creamos un diario sin descripción
        $diario = new Diario();
        $diario->descripcion = '';
        $this->assertFalse($diario->save(), 'diario-must-have-description');
    }

    public function testCreateTooLong(): void
    {
        // creamos un diario con descripción muy larga
        $diario = new Diario();
        $diario->descripcion = str_repeat('a', 101);
        $this->assertFalse($diario->save(), 'diario-description-too-long');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
