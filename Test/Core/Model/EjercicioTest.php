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

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
