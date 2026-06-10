<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025  Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\ConceptoPartida;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class ConceptoPartidaTest extends TestCase
{
    use LogErrorsTrait;

    public function testCreate(): void
    {
        // creamos un concepto de partida
        $concept = new ConceptoPartida();
        $concept->codconcepto = 'Test';
        $concept->descripcion = 'Test Concept';
        $this->assertTrue($concept->save(), 'concept-cant-save');

        // comprobamos que se ha guardado correctamente
        $this->assertNotNull($concept->id(), 'concept-not-stored');
        $this->assertTrue($concept->exists(), 'concept-cant-persist');

        // eliminamos
        $this->assertTrue($concept->delete(), 'concept-cant-delete');
    }

    public function testCreateWithNoCode(): void
    {
        $concept = new ConceptoPartida();
        $concept->descripcion = 'Test Concept with new code';
        $this->assertTrue($concept->save(), 'concept-cant-save');
        $this->assertTrue($concept->delete(), 'concept-cant-delete');
    }

    public function testEscapeHtml(): void
    {
        $html = '<b>Test Concept</b>';
        $escaped = Tools::noHtml($html);

        // creamos un concepto de partida con HTML
        $concept = new ConceptoPartida();
        $concept->codconcepto = 'Test';
        $concept->descripcion = $html;
        $this->assertTrue($concept->save(), 'concept-cant-save');

        // comprobamos que se ha escapado correctamente
        $this->assertEquals($escaped, $concept->descripcion, 'concept-description-not-escaped');

        // eliminamos
        $this->assertTrue($concept->delete(), 'concept-cant-delete');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
