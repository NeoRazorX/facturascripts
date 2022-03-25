<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Test\Core\LogErrorsTrait;
use FacturaScripts\Test\Core\RandomDataTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers Asiento
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
final class AsientoTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    public function testCreate()
    {
        // creamos el asiento
        $asiento = new Asiento();
        $asiento->concepto = 'Test';
        $this->assertTrue($asiento->save(), 'asiento-cant-save');
        $this->assertNotNull($asiento->primaryColumnValue(), 'asiento-not-stored');
        $this->assertTrue($asiento->exists(), 'asiento-cant-persist');

        // eliminamos
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete');
    }

    public function testClosedExerciseCreate()
    {
        // creamos un ejercicio cerrado
        $exercise = $this->getRandomExercise();
        $exercise->estado = Ejercicio::EXERCISE_STATUS_CLOSED;
        $this->assertTrue($exercise->save(), 'can-not-close-exercise');

        // creamos el asiento
        $asiento = new Asiento();
        $asiento->clearExerciseCache();
        $asiento->concepto = 'Closed';
        $asiento->fecha = $exercise->fechafin;
        $asiento->idempresa = $exercise->idempresa;
        $this->assertFalse($asiento->save(), 'can-save-on-closed-exercise');

        // reabrimos el ejercicio
        $exercise->estado = Ejercicio::EXERCISE_STATUS_OPEN;
        $this->assertTrue($exercise->save(), 'can-not-open-exercise');
        $asiento->clearExerciseCache();

        // ahora se puede crear
        $this->assertTrue($asiento->save(), 'can-not-save-on-open-exercise');

        // eliminamos
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete');
    }

    public function testClosedExerciseModify()
    {
        // creamos el asiento
        $asiento = new Asiento();
        $asiento->concepto = 'Test';
        $this->assertTrue($asiento->save(), 'asiento-cant-save');
        $this->assertNotNull($asiento->primaryColumnValue(), 'asiento-not-stored');
        $this->assertTrue($asiento->exists(), 'asiento-cant-persist');

        // cerramos el ejercicio
        $exercise = new Ejercicio();
        $exercise->loadFromCode($asiento->codejercicio);
        $exercise->estado = Ejercicio::EXERCISE_STATUS_CLOSED;
        $this->assertTrue($exercise->save(), 'can-not-close-exercise');
        $asiento->clearExerciseCache();

        // ahora no se puede modificar
        $asiento->concepto = 'Modify';
        $this->assertFalse($asiento->save(), 'can-save-on-closed-exercise');

        // abrimos el ejercicio
        $exercise->estado = Ejercicio::EXERCISE_STATUS_OPEN;
        $this->assertTrue($exercise->save(), 'can-not-open-exercise');
        $asiento->clearExerciseCache();

        // eliminamos
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete');
    }

    public function testCheckLogAudit()
    {
        // creamos el asiento
        $asiento = new Asiento();
        $asiento->concepto = 'Test';
        $this->assertTrue($asiento->save(), 'asiento-cant-save');
        $this->assertNotNull($asiento->primaryColumnValue(), 'asiento-not-stored');
        $this->assertTrue($asiento->exists(), 'asiento-cant-persist');

        $found = $this->searchAuditLog($asiento->modelClassName(), $asiento->idasiento);
        $this->assertTrue($found, 'asiento-log-audit-cant-persist');

        // eliminamos
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
