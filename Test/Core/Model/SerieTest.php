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

use FacturaScripts\Core\Model\Serie;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class SerieTest extends TestCase
{
    use LogErrorsTrait;

    public function testCreate(): void
    {
        // creamos una serie
        $serie = new Serie();
        $serie->codserie = 'TE';
        $serie->descripcion = 'Serie de prueba';
        $this->assertTrue($serie->save());

        // comprobamos que existe en la base de datos
        $this->assertTrue($serie->exists());

        // comprobamos valores por defecto
        $this->assertFalse($serie->siniva);
        $this->assertEquals(0, $serie->canal);
        $this->assertEquals(0, $serie->iddiario);

        // eliminamos
        $this->assertTrue($serie->delete());
    }

    public function testCreateWithInvalidCode(): void
    {
        // creamos una serie con código inválido (demasiado largo)
        $serie = new Serie();
        $serie->codserie = 'CODIGO_LARGO';
        $serie->descripcion = 'Serie con código largo';
        $this->assertFalse($serie->save(), 'code-too-long-should-fail');

        // código con caracteres inválidos
        $serie->codserie = 'TE@';
        $this->assertFalse($serie->save(), 'invalid-characters-should-fail');
    }

    public function testCreateWithoutCode(): void
    {
        // creamos una serie sin código
        $serie = new Serie();
        $serie->descripcion = 'Serie sin código';
        $this->assertTrue($serie->save());

        // comprobamos que se ha asignado un código automáticamente
        $this->assertNotEmpty($serie->codserie);

        // eliminamos
        $this->assertTrue($serie->delete());
    }

    public function testDescriptionSanitization(): void
    {
        $serie = new Serie();
        $serie->codserie = 'HT';
        $serie->descripcion = '<script/>';
        $this->assertTrue($serie->test());

        // verificamos que el HTML ha sido escapado
        $this->assertEquals(Tools::noHtml('<script/>'), $serie->descripcion);

        // eliminamos
        $this->assertTrue($serie->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
