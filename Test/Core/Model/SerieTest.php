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
        // creamos una serie sin código, con descripción que empieza por T
        $serie = new Serie();
        $serie->descripcion = 'Test serie';
        $this->assertTrue($serie->save());

        // comprobamos que se ha asignado un código automáticamente usando la primera letra de la descripción
        $this->assertEquals('T', $serie->codserie);

        // eliminamos
        $this->assertTrue($serie->delete());
    }

    public function testCreateWithoutCodeFallsBackToMoreLetters(): void
    {
        // ocupamos el código de 1 letra 'T'
        $serie1 = new Serie();
        $serie1->codserie = 'T';
        $serie1->descripcion = 'T ocupada';
        $this->assertTrue($serie1->save());

        // creamos otra serie sin código con descripción que empieza por T
        $serie2 = new Serie();
        $serie2->descripcion = 'Test serie';
        $this->assertTrue($serie2->save());

        // al estar 'T' ocupada, debe usar 'TE' (primeras 2 letras de la descripción)
        $this->assertEquals('TE', $serie2->codserie);

        // eliminamos
        $this->assertTrue($serie1->delete());
        $this->assertTrue($serie2->delete());
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
