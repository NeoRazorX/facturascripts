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

use FacturaScripts\Core\Model\Asiento;
use FacturaScripts\Core\Model\Base\ModelCore;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Core\Model\RegularizacionImpuesto;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers Asiento
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
final class AsientoTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
        self::removeTaxRegularization();
    }

    public function testCreate(): void
    {
        // creamos el asiento
        $asiento = new Asiento();
        $asiento->concepto = 'Test';
        $this->assertTrue($asiento->save(), 'asiento-cant-save-1');
        $this->assertNotNull($asiento->primaryColumnValue(), 'asiento-not-stored');
        $this->assertTrue($asiento->exists(), 'asiento-cant-persist');

        // añadimos una línea
        $firstLine = $asiento->getNewLine();
        $firstLine->codsubcuenta = '1000000000';
        $firstLine->concepto = 'Test linea 1';
        $firstLine->debe = 100;
        $this->assertTrue($firstLine->save(), 'linea-cant-save-1');

        // añadimos otra línea
        $secondLine = $asiento->getNewLine();
        $secondLine->codsubcuenta = '5700000000';
        $secondLine->concepto = 'Test linea 2';
        $secondLine->haber = 100;
        $this->assertTrue($secondLine->save(), 'linea-cant-save-2');

        // el asiento está cuadrado
        $this->assertTrue($asiento->isBalanced(), 'asiento-is-descuadrado');

        // eliminamos
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete-1');

        // las líneas se han eliminado
        $this->assertFalse($firstLine->exists(), 'linea-cant-delete-1');
        $this->assertFalse($secondLine->exists(), 'linea-cant-delete-2');
    }

    public function testCheckLogAudit(): void
    {
        // creamos el asiento
        $asiento = new Asiento();
        $asiento->concepto = 'Test';
        $this->assertTrue($asiento->save(), 'asiento-cant-save-2');

        // comprobamos que se ha guardado en el log
        $found = $this->searchAuditLog($asiento->modelClassName(), $asiento->idasiento);
        $this->assertTrue($found, 'asiento-log-audit-missing');

        // eliminamos
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete-2');
    }

    public function testUpdate(): void
    {
        // creamos el asiento
        $asiento = new Asiento();
        $asiento->concepto = 'Test';
        $this->assertTrue($asiento->save(), 'asiento-cant-save-3');

        // añadimos una línea
        $firstLine = $asiento->getNewLine();
        $firstLine->codsubcuenta = '1000000000';
        $firstLine->concepto = 'Test linea 1';
        $firstLine->debe = 100;
        $this->assertTrue($firstLine->save(), 'linea-cant-save-3');

        // añadimos otra línea
        $secondLine = $asiento->getNewLine();
        $secondLine->codsubcuenta = '5700000000';
        $secondLine->concepto = 'Test linea 2';
        $secondLine->haber = 100;
        $this->assertTrue($secondLine->save(), 'linea-cant-save-4');

        // actualizamos el asiento
        $asiento->concepto = 'Test 2';
        $asiento->importe = 100;
        $this->assertTrue($asiento->save(), 'asiento-cant-save-4');

        // eliminamos
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete-3');
    }

    public function testUpdateLocked(): void
    {
        // creamos el asiento
        $asiento = new Asiento();
        $asiento->concepto = 'Test';
        $this->assertTrue($asiento->save(), 'asiento-cant-save-5');

        // añadimos una línea
        $firstLine = $asiento->getNewLine();
        $firstLine->codsubcuenta = '1000000000';
        $firstLine->concepto = 'Test linea 1';
        $firstLine->debe = 100;
        $this->assertTrue($firstLine->save(), 'linea-cant-save-5');

        // añadimos otra línea
        $secondLine = $asiento->getNewLine();
        $secondLine->codsubcuenta = '5700000000';
        $secondLine->concepto = 'Test linea 2';
        $secondLine->haber = 100;

        // bloqueamos el asiento
        $asiento->editable = false;
        $this->assertTrue($asiento->save(), 'asiento-cant-save-6');

        // intentamos añadir una línea
        $thirdLine = $asiento->getNewLine();
        $thirdLine->codsubcuenta = '5720000000';
        $thirdLine->concepto = 'Test linea 3';
        $thirdLine->haber = 100;
        $this->assertFalse($thirdLine->save(), 'linea-cant-save-7');

        // intentamos actualizar el asiento
        $asiento->concepto = 'Test 2';
        $asiento->importe = 100;
        $this->assertFalse($asiento->save(), 'asiento-cant-save-7');

        // intentamos eliminar el asiento
        $this->assertFalse($asiento->delete(), 'asiento-cant-delete-4');

        // intentamos eliminar una línea
        $this->assertFalse($firstLine->delete(), 'linea-cant-delete-5');

        // desbloqueamos el asiento
        $asiento->editable = true;
        $this->assertTrue($asiento->save(), 'asiento-cant-save-8');

        // eliminamos
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete-5');
    }

    public function testCreateOnClosedExercise(): void
    {
        // creamos un ejercicio cerrado
        $exercise = $this->getRandomExercise();
        $exercise->estado = Ejercicio::EXERCISE_STATUS_CLOSED;
        $this->assertTrue($exercise->save(), 'can-not-close-exercise-1');

        // creamos el asiento
        $asiento = new Asiento();
        $asiento->concepto = 'Closed';
        $asiento->codejercicio = $exercise->codejercicio;
        $asiento->fecha = $exercise->fechafin;
        $asiento->idempresa = $exercise->idempresa;
        $this->assertFalse($asiento->save(), 'can-save-on-closed-exercise');

        // reabrimos el ejercicio
        $exercise->estado = Ejercicio::EXERCISE_STATUS_OPEN;
        $this->assertTrue($exercise->save(), 'can-not-open-exercise-1');

        // ahora se puede crear
        $this->assertTrue($asiento->save(), 'can-not-save-on-open-exercise');

        // eliminamos
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete-3');
    }

    public function testUpdateOnClosedExercise(): void
    {
        // cargamos un ejercicio
        $exercise = $this->getRandomExercise();
        $exercise->estado = Ejercicio::EXERCISE_STATUS_OPEN;
        $this->assertTrue($exercise->save(), 'can-not-save-exercise-4');

        // creamos el asiento
        $asiento = new Asiento();
        $asiento->concepto = 'Test';
        $asiento->codejercicio = $exercise->codejercicio;
        $asiento->fecha = $exercise->fechafin;
        $asiento->idempresa = $exercise->idempresa;
        $this->assertTrue($asiento->save(), 'asiento-cant-save-4');

        // añadimos una línea
        $firstLine = $asiento->getNewLine();
        $firstLine->codsubcuenta = '1000000000';
        $firstLine->concepto = 'Test linea 1';
        $firstLine->debe = 100;
        $this->assertTrue($firstLine->save(), 'linea-cant-save-4');

        // cerramos el ejercicio
        $exercise->estado = Ejercicio::EXERCISE_STATUS_CLOSED;
        $this->assertTrue($exercise->save(), 'can-not-close-exercise-2');

        // ahora no se puede modificar
        $asiento->concepto = 'Modify';
        $this->assertFalse($asiento->save(), 'can-save-on-closed-exercise-2');

        // tampoco se puede eliminar
        $this->assertFalse($asiento->delete(), 'can-delete-on-closed-exercise');

        // no se puede añadir una línea
        $line = $asiento->getNewLine();
        $line->codsubcuenta = '5720000000';
        $line->concepto = 'Test linea 3';
        $line->debe = 100;
        $this->assertFalse($line->save(), 'can-add-line-on-closed-exercise');

        // no se puede modificar una línea
        $firstLine->concepto = 'Modify linea 1';
        $this->assertFalse($firstLine->save(), 'can-modify-line-on-closed-exercise');

        // no se puede eliminar una línea
        $this->assertFalse($firstLine->delete(), 'can-delete-line-on-closed-exercise');

        // abrimos el ejercicio
        $exercise->estado = Ejercicio::EXERCISE_STATUS_OPEN;
        $this->assertTrue($exercise->save(), 'can-not-open-exercise-2');

        // eliminamos
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete-4');
    }

    public function testCreateOnTaxRegularization(): void
    {
        // obtenemos un ejercicio
        $exercise = $this->getRandomExercise();
        $exercise->estado = Ejercicio::EXERCISE_STATUS_OPEN;
        $this->assertTrue($exercise->save(), 'can-not-save-exercise-5');

        // creamos una regularización de impuestos
        $regularization = new RegularizacionImpuesto();
        $regularization->bloquear = true;
        $regularization->codejercicio = $exercise->codejercicio;
        $regularization->periodo = 'T4';
        $this->assertTrue($regularization->save(), 'can-not-save-regularization');

        // Creamos el asiento. Debe fallar
        $asiento = new Asiento();
        $asiento->concepto = 'Test';
        $asiento->codejercicio = $exercise->codejercicio;
        $asiento->fecha = $exercise->fechafin;
        $asiento->idempresa = $exercise->idempresa;
        $this->assertFalse($asiento->save(), 'asiento-can-save-on-regularization');

        // eliminamos
        $this->assertTrue($regularization->delete(), 'regularization-cant-delete');
    }

    public function testUpdateOnTaxRegularization(): void
    {
        // obtenemos un ejercicio
        $exercise = $this->getRandomExercise();
        $exercise->estado = Ejercicio::EXERCISE_STATUS_OPEN;
        $this->assertTrue($exercise->save(), 'can-not-save-exercise-6');

        // creamos un asiento
        $asiento = new Asiento();
        $asiento->concepto = 'Test';
        $asiento->codejercicio = $exercise->codejercicio;
        $asiento->fecha = $exercise->fechafin;
        $asiento->idempresa = $exercise->idempresa;
        $this->assertTrue($asiento->save(), 'asiento-cant-save-6');

        // creamos una regularización de impuestos
        $regularization = new RegularizacionImpuesto();
        $regularization->bloquear = true;
        $regularization->codejercicio = $exercise->codejercicio;
        $regularization->periodo = 'T4';
        $this->assertTrue($regularization->save(), 'can-not-save-regularization-2');

        // ahora no se puede modificar
        $asiento->concepto = 'Modify';
        $this->assertFalse($asiento->save(), 'can-save-on-regularization-2');

        // eliminamos
        $this->assertTrue($regularization->delete(), 'regularization-cant-delete-2');
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete-6');
    }

    public function testCreateOnUnrelatedTaxRegularization(): void
    {
        // obtenemos un ejercicio
        $exercise = $this->getRandomExercise();
        $exercise->estado = Ejercicio::EXERCISE_STATUS_OPEN;
        $this->assertTrue($exercise->save(), 'can-not-save-exercise-6');

        // creamos una nueva empresa
        $newCompany = $this->getRandomCompany();
        $this->assertTrue($newCompany->save(), 'can-not-save-company-1');

        // creamos un ejercicio en la nueva empresa
        $newExercise = new Ejercicio();
        $newExercise->idempresa = $newCompany->idempresa;
        $newExercise->nombre = 'Test ' . date('Y');
        $newExercise->codejercicio = $newExercise->newCode();
        $this->assertTrue($newExercise->save(), 'can-not-save-exercise-8');

        // creamos una regularización de impuestos para el ejercicio de la nueva empresa
        $regularization = new RegularizacionImpuesto();
        $regularization->bloquear = true;
        $regularization->codejercicio = $newExercise->codejercicio;
        $regularization->periodo = 'T4';
        $this->assertTrue($regularization->save(), 'can-not-save-regularization-3');

        // Creamos un asiento en el ejercicio original. No debe fallar
        $asiento = new Asiento();
        $asiento->concepto = 'Test';
        $asiento->codejercicio = $exercise->codejercicio;
        $asiento->fecha = $exercise->fechafin;
        $asiento->idempresa = $exercise->idempresa;
        $this->assertTrue($asiento->save(), 'asiento-cant-save-7');

        // eliminamos
        $this->assertTrue($asiento->delete());
        $this->assertTrue($regularization->delete());
        $this->assertTrue($newExercise->delete());
        $this->assertTrue($newCompany->delete());
    }

    public function testChangeExercise(): void
    {
        // obtenemos un ejercicio
        $exercise = $this->getRandomExercise();
        $exercise->estado = Ejercicio::EXERCISE_STATUS_OPEN;
        $this->assertTrue($exercise->save(), 'can-not-save-exercise-7');

        // creamos un asiento
        $asiento = new Asiento();
        $asiento->concepto = 'Test';
        $asiento->codejercicio = $exercise->codejercicio;
        $asiento->fecha = $exercise->fechafin;
        $asiento->idempresa = $exercise->idempresa;
        $this->assertTrue($asiento->save(), 'asiento-cant-save-7');

        // obtenemos una fecha posterior a la de cierre del ejercicio
        $nextDate = date(ModelCore::DATE_STYLE, strtotime($exercise->fechafin . ' +1 day'));

        // asignamos esa fecha al asiento
        $asiento->fecha = $nextDate;
        $this->assertFalse($asiento->save(), 'asiento-can-set-date-after-exercise-end');

        // obtenemos el ejercicio siguiente
        $nextExercise = new Ejercicio();
        $nextExercise->idempresa = $exercise->idempresa;
        $this->assertTrue($nextExercise->loadFromDate($nextDate), 'can-not-load-next-exercise');

        // cambiamos el ejercicio del asiento
        $asiento->codejercicio = $nextExercise->codejercicio;
        $asiento->fecha = $nextDate;
        $this->assertFalse($asiento->save(), 'asiento-cant-change-exercise');

        // eliminamos
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete-7');
        $this->assertTrue($nextExercise->delete(), 'next-exercise-cant-delete');
    }

    public function testRenumber(): void
    {
        // obtenemos un ejercicio
        $exercise = $this->getRandomExercise();
        $exercise->estado = Ejercicio::EXERCISE_STATUS_OPEN;
        $this->assertTrue($exercise->save(), 'can-not-save-exercise-7');

        // eliminamos todos sus asientos
        $asientoModel = new Asiento();
        foreach ($asientoModel->all([], [], 0, 0) as $asiento) {
            $asiento->editable = true;
            $this->assertTrue($asiento->delete(), 'asiento-cant-delete-7');
        }

        // creamos 100 asientos
        for ($i = 1; $i <= 100; $i++) {
            $asiento = new Asiento();
            $asiento->concepto = 'Test';
            $asiento->codejercicio = $exercise->codejercicio;
            $asiento->idempresa = $exercise->idempresa;
            $asiento->fecha = ($i > 1)
                ? date(ModelCore::DATE_STYLE, strtotime('-' . $i . ' days', strtotime($exercise->fechafin)))
                : $this->getFirstDay($exercise->fechafin);

            $this->assertTrue($asiento->save(), 'asiento-cant-save-7');
            $this->assertEquals($i, $asiento->numero, 'asiento-number-not-correct');
        }

        // creamos asiento de apertura
        $asiento = new Asiento();
        $asiento->operacion = Asiento::OPERATION_OPENING;
        $asiento->concepto = 'Test Apertura';
        $asiento->codejercicio = $exercise->codejercicio;
        $asiento->idempresa = $exercise->idempresa;
        $asiento->fecha = $this->getFirstDay($exercise->fechafin);
        $this->assertTrue($asiento->save(), 'asiento-cant-save-7');

        // renumeramos
        $this->assertTrue($asientoModel->renumber($exercise->codejercicio), 'can-not-renumber-asientos');

        // comprobamos que los números se han renumerado
        $numero = 1;
        foreach ($asientoModel->all([], ['numero' => 'ASC'], 0, 0) as $asiento) {
            $this->assertEquals($numero, $asiento->numero, 'asiento-number-not-correct');
            if ($numero === 1) {
                $this->assertTrue($asiento->operacion === Asiento::OPERATION_OPENING, 'asiento-first-not-is-opening');
            }
            $numero++;
        }

        // eliminamos
        foreach ($asientoModel->all([], [], 0, 0) as $asiento) {
            $this->assertTrue($asiento->delete(), 'asiento-cant-delete-7');
        }
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }

    private function getFirstDay(string $date): string
    {
        return '01-01-' . date('Y', strtotime($date));
    }
}
