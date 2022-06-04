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

use FacturaScripts\Core\Model\Base\ModelCore;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Core\Model\RegularizacionImpuesto;
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Test\Core\DefaultSettingsTrait;
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
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
        self::removeTaxRegularization();
    }

    public function testCreate()
    {
        // creamos el asiento
        $asiento = new Asiento();
        $asiento->concepto = 'Test';
        $this->assertTrue($asiento->save(), 'asiento-cant-save-1');
        $this->assertNotNull($asiento->primaryColumnValue(), 'asiento-not-stored');
        $this->assertTrue($asiento->exists(), 'asiento-cant-persist');

        // eliminamos
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete-1');
    }

    public function testCheckLogAudit()
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

    public function testCreateOnClosedExercise()
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

    public function testUpdateOnClosedExercise()
    {
        // cargamos un ejercicio
        $exercise = $this->getRandomExercise();
        $this->assertTrue($exercise->save(), 'can-not-save-exercise-4');

        // creamos el asiento
        $asiento = new Asiento();
        $asiento->concepto = 'Test';
        $asiento->codejercicio = $exercise->codejercicio;
        $asiento->fecha = $exercise->fechafin;
        $asiento->idempresa = $exercise->idempresa;
        $this->assertTrue($asiento->save(), 'asiento-cant-save-4');

        // cerramos el ejercicio
        $exercise->estado = Ejercicio::EXERCISE_STATUS_CLOSED;
        $this->assertTrue($exercise->save(), 'can-not-close-exercise-2');

        // ahora no se puede modificar
        $asiento->concepto = 'Modify';
        $this->assertFalse($asiento->save(), 'can-save-on-closed-exercise-2');

        // tampoco se puede eliminar
        $this->assertFalse($asiento->delete(), 'can-delete-on-closed-exercise');

        // abrimos el ejercicio
        $exercise->estado = Ejercicio::EXERCISE_STATUS_OPEN;
        $this->assertTrue($exercise->save(), 'can-not-open-exercise-2');

        // eliminamos
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete-4');
    }

    public function testCreateOnTaxRegularization()
    {
        // obtenemos un ejercicio
        $exercise = $this->getRandomExercise();
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

    public function testUpdateOnTaxRegularization()
    {
        // obtenemos un ejercicio
        $exercise = $this->getRandomExercise();
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

    public function testRenumber()
    {
        // obtenemos un ejercicio
        $exercise = $this->getRandomExercise();
        $this->assertTrue($exercise->save(), 'can-not-save-exercise-7');

        // eliminamos todos sus asientos
        $asientoModel = new Asiento();
        foreach ($asientoModel->all([], [], 0, 0) as $asiento) {
            $this->assertTrue($asiento->delete(), 'asiento-cant-delete-7');
        }

        // creamos 100 asientos
        for ($i = 1; $i <= 100; $i++) {
            $asiento = new Asiento();
            $asiento->concepto = 'Test';
            $asiento->codejercicio = $exercise->codejercicio;
            $asiento->fecha = date(ModelCore::DATE_STYLE, strtotime('-' . $i . ' days', strtotime($exercise->fechafin)));
            $asiento->idempresa = $exercise->idempresa;
            $this->assertTrue($asiento->save(), 'asiento-cant-save-7');
            $this->assertEquals($i, $asiento->numero, 'asiento-number-not-correct');
        }

        // renumeramo
        $this->assertTrue($asientoModel->renumber($exercise->codejercicio), 'can-not-renumber-asientos');

        // comprobamos que los números se han renumerado
        $numero = 1;
        foreach ($asientoModel->all([], ['numero' => 'ASC'], 0, 0) as $asiento) {
            $this->assertEquals($numero, $asiento->numero, 'asiento-number-not-correct');
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
}
