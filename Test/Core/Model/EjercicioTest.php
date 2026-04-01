<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class EjercicioTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;
    use DefaultSettingsTrait;

    protected function setUp(): void
    {
        self::setDefaultSettings();
    }

    // Comprobar que se puede crear un ejercicio (y que se crea abierto) y borrarlo.
    public function testItCanCreateExercise(): void
    {
        // Lo creamos con fecha del próximo año porque al pasar los
        // test se instancia la clase 'Ejercicio' y se crea un ejercicio para este año automaticamente
        // por lo que da un error si se quiere crear otro ejercicio para este mismo año
        $nextYear = date('Y', strtotime(date('Y') . ' + 1 year'));

        $codejercicio = 't001';

        $ejercicio = new Ejercicio();
        $ejercicio->codejercicio = $codejercicio;
        $ejercicio->nombre = 'exercise-test';
        $ejercicio->fechainicio = $nextYear . '-01-01';
        $ejercicio->fechafin = $nextYear . '-12-31';
        $this->assertTrue($ejercicio->save());

        // Obtenemos el ejercicio de la base de datos
        // y comprobamos que se crea abierto
        $ejercicio = new Ejercicio();
        $ejercicio->loadFromCode($codejercicio);
        $this->assertEquals(Ejercicio::EXERCISE_STATUS_OPEN, $ejercicio->estado);

        // Comprobamos que se elimina correctamente
        $this->assertTrue($ejercicio->delete());

        // Obtenemos el ejercicio de la base de datos
        // y comprobamos que ya no existe
        $ejercicio = new Ejercicio();
        $this->assertFalse($ejercicio->loadFromCode($codejercicio));
    }

    // Comprobar que no se puede crear un ejercicio con fecha de inicio posterior a la fecha de fin.
    public function testItCanNotCreateExerciseWrongDate(): void
    {
        $ejercicio = new Ejercicio();
        $ejercicio->codejercicio = 't002';
        $ejercicio->nombre = 'exercise-test';
        $ejercicio->fechainicio = '2033-06-16';
        $ejercicio->fechafin = '2033-06-15';
        $this->assertFalse($ejercicio->save());
    }

    // Crear un ejercicio para 2099 y comprobar que si solicitamos un ejercicio para esa fecha, lo devuelve.
    public function testItCanReturnExerciseFromDate(): void
    {
        $idempresa = 1;

        $ejercicio = new Ejercicio();
        $ejercicio->idempresa = $idempresa;
        $ejercicio->codejercicio = 't003';
        $ejercicio->nombre = 'exercise-test';
        $ejercicio->fechainicio = '2099-01-01';
        $ejercicio->fechafin = '2099-12-31';
        $this->assertTrue($ejercicio->save());

        $ejercicio2 = new Ejercicio();
        $ejercicio2->idempresa = $idempresa;
        $this->assertTrue($ejercicio2->loadFromDate('2099-06-15', true, false));
        $this->assertEquals($ejercicio->codejercicio, $ejercicio2->codejercicio);

        // Eliminamos
        $this->assertTrue($ejercicio->delete());
    }

    // Comprobar que se pueden crear dos ejercicios para la misma fecha pero distinta empresa.
    public function testItCanCreateExercisesFromDifferentCompanies(): void
    {
        $inicio = '2099-01-01';
        $fin = '2099-12-31';

        $empresa1 = $this->getRandomCompany();
        $this->assertTrue($empresa1->save());

        $empresa2 = $this->getRandomCompany();
        $this->assertTrue($empresa2->save());

        $ejercicioEmpresa1 = new Ejercicio();
        $ejercicioEmpresa1->idempresa = $empresa1->idempresa;
        $ejercicioEmpresa1->codejercicio = 'E-1';
        $ejercicioEmpresa1->nombre = 'exercise-test-1';
        $ejercicioEmpresa1->fechainicio = $inicio;
        $ejercicioEmpresa1->fechafin = $fin;
        $this->assertTrue($ejercicioEmpresa1->save());

        $ejercicioEmpresa2 = new Ejercicio();
        $ejercicioEmpresa2->idempresa = $empresa2->idempresa;
        $ejercicioEmpresa2->codejercicio = 'E-2';
        $ejercicioEmpresa2->nombre = 'exercise-test-2';
        $ejercicioEmpresa2->fechainicio = $inicio;
        $ejercicioEmpresa2->fechafin = $fin;
        $this->assertTrue($ejercicioEmpresa2->save());

        $ejercicio = new Ejercicio();
        $this->assertTrue($ejercicio->loadFromCode($ejercicioEmpresa1->codejercicio));

        $ejercicio = new Ejercicio();
        $this->assertTrue($ejercicio->loadFromCode($ejercicioEmpresa2->codejercicio));

        // Eliminamos
        $this->assertTrue($ejercicioEmpresa1->delete());
        $this->assertTrue($ejercicioEmpresa2->delete());
        $this->assertTrue($empresa1->delete());
        $this->assertTrue($empresa2->delete());
    }

    // Comprobar que no se puede crear un ejercicio con longsubcuenta fuera del rango permitido.
    public function testLongsubcuentaLimit(): void
    {
        $nextYear = date('Y', strtotime(date('Y') . ' + 1 year'));

        // intentamos crear un ejercicio con longsubcuenta = 0 (inválido)
        $ejercicio = new Ejercicio();
        $ejercicio->codejercicio = 't004';
        $ejercicio->nombre = 'exercise-test';
        $ejercicio->fechainicio = $nextYear . '-01-01';
        $ejercicio->fechafin = $nextYear . '-12-31';
        $ejercicio->longsubcuenta = 0;
        $this->assertFalse($ejercicio->save(), 'exercise-should-not-save-with-longsubcuenta-0');

        // intentamos crear un ejercicio con longsubcuenta = 3 (inválido)
        $ejercicio->codejercicio = 't005';
        $ejercicio->longsubcuenta = 3;
        $this->assertFalse($ejercicio->save(), 'exercise-should-not-save-with-longsubcuenta-3');

        // intentamos crear un ejercicio con longsubcuenta = 16 (inválido)
        $ejercicio->codejercicio = 't006';
        $ejercicio->longsubcuenta = 16;
        $this->assertFalse($ejercicio->save(), 'exercise-should-not-save-with-longsubcuenta-16');

        // intentamos crear un ejercicio con longsubcuenta = 20 (inválido)
        $ejercicio->codejercicio = 't007';
        $ejercicio->longsubcuenta = 20;
        $this->assertFalse($ejercicio->save(), 'exercise-should-not-save-with-longsubcuenta-20');

        // intentamos crear un ejercicio con longsubcuenta negativo (inválido)
        $ejercicio->codejercicio = 't008';
        $ejercicio->longsubcuenta = -5;
        $this->assertFalse($ejercicio->save(), 'exercise-should-not-save-with-negative-longsubcuenta');

        // creamos un ejercicio con longsubcuenta = 4 (válido - límite inferior)
        $ejercicio->codejercicio = 't009';
        $ejercicio->longsubcuenta = 4;
        $this->assertTrue($ejercicio->save(), 'exercise-should-save-with-longsubcuenta-4');
        $this->assertTrue($ejercicio->delete(), 'exercise-cant-delete');

        // creamos un ejercicio con longsubcuenta = 10 (válido - valor por defecto)
        $ejercicio->codejercicio = 't010';
        $ejercicio->longsubcuenta = 10;
        $this->assertTrue($ejercicio->save(), 'exercise-should-save-with-longsubcuenta-10');
        $this->assertTrue($ejercicio->delete(), 'exercise-cant-delete');

        // creamos un ejercicio con longsubcuenta = 15 (válido - límite superior)
        $ejercicio->codejercicio = 't011';
        $ejercicio->longsubcuenta = 15;
        $this->assertTrue($ejercicio->save(), 'exercise-should-save-with-longsubcuenta-15');
        $this->assertTrue($ejercicio->delete(), 'exercise-cant-delete');
    }

    // Comprobar que al crear ejercicios automáticamente se asignan los códigos alternativos correctos.
    // La secuencia es: año (2098), 0098, 0+idempresa+98, 0001-9999.
    public function testAlternativeCodesOnCreate(): void
    {
        $idempresa = 1;
        $year = '2098';
        $year2 = '98';
        $inicio = $year . '-01-01';
        $fin = $year . '-12-31';

        // Creamos el primer ejercicio manualmente con código = año
        $ej1 = new Ejercicio();
        $ej1->idempresa = $idempresa;
        $ej1->codejercicio = $year;
        $ej1->nombre = 'alt-code-test-1';
        $ej1->fechainicio = $inicio;
        $ej1->fechafin = $fin;
        $this->assertTrue($ej1->save(), 'exercise-1-should-save');

        // Creamos una segunda empresa
        $empresa2 = $this->getRandomCompany();
        $this->assertTrue($empresa2->save());

        // loadFromDate para empresa2 debe crear ejercicio con código 0098
        $ej2 = new Ejercicio();
        $ej2->idempresa = $empresa2->idempresa;
        $this->assertTrue($ej2->loadFromDate($inicio));
        $this->assertEquals('00' . $year2, $ej2->codejercicio, 'exercise-2-should-be-00' . $year2);

        // Ocupamos el código 0+idempresa+98 manualmente para la siguiente prueba
        $code3 = sprintf('%04s', '0' . $empresa2->idempresa . $year2);

        // Creamos una tercera empresa
        $empresa3 = $this->getRandomCompany();
        $this->assertTrue($empresa3->save());

        // loadFromDate para empresa3 debe crear ejercicio con código 0+idempresa+98
        $ej3 = new Ejercicio();
        $ej3->idempresa = $empresa3->idempresa;
        $this->assertTrue($ej3->loadFromDate($inicio));
        $this->assertEquals(sprintf('%04s', '0' . $empresa3->idempresa . $year2), $ej3->codejercicio,
            'exercise-3-should-be-0' . $empresa3->idempresa . $year2);

        // Ocupamos también el código 0+idempresa4+98
        $empresa4 = $this->getRandomCompany();
        $this->assertTrue($empresa4->save());

        // Creamos manualmente un ejercicio para ocupar el código que le correspondería
        $code4 = sprintf('%04s', '0' . $empresa4->idempresa . $year2);
        $ej4block = new Ejercicio();
        $ej4block->idempresa = $empresa4->idempresa;
        $ej4block->codejercicio = $code4;
        $ej4block->nombre = 'alt-code-block';
        $ej4block->fechainicio = '2097-01-01';
        $ej4block->fechafin = '2097-12-31';
        $this->assertTrue($ej4block->save(), 'exercise-block-should-save');

        // loadFromDate para empresa4 debe caer en el rango 0001-9999
        $ej4 = new Ejercicio();
        $ej4->idempresa = $empresa4->idempresa;
        $this->assertTrue($ej4->loadFromDate($inicio));
        $this->assertNotEquals($year, $ej4->codejercicio);
        $this->assertNotEquals('00' . $year2, $ej4->codejercicio);
        $this->assertNotEquals($code4, $ej4->codejercicio);
        $this->assertMatchesRegularExpression('/^\d{4}$/', $ej4->codejercicio,
            'exercise-4-should-be-numeric-4-digits');

        // Eliminamos en orden inverso
        $this->assertTrue($ej4->delete());
        $this->assertTrue($ej4block->delete());
        $this->assertTrue($ej3->delete());
        $this->assertTrue($ej2->delete());
        $this->assertTrue($ej1->delete());
        $this->assertTrue($empresa4->delete());
        $this->assertTrue($empresa3->delete());
        $this->assertTrue($empresa2->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
