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

use FacturaScripts\Core\Model\Asiento;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Core\Model\Partida;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Subcuenta;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers Partida
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
final class PartidaTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
    }

    public function testCreate(): void
    {
        // creamos el asiento
        $asiento = new Asiento();
        $asiento->concepto = 'Test Asiento';
        $this->assertTrue($asiento->save(), 'asiento-cant-save');
        $this->assertNotNull($asiento->id(), 'asiento-not-stored');

        $ejercicio = $asiento->getExercise();

        // creamos una partida
        $partida = new Partida();
        $partida->idasiento = $asiento->idasiento;
        $partida->codsubcuenta = $this->getSampleSubaccount($ejercicio);
        $partida->concepto = 'Test Partida';
        $partida->debe = 100;
        $this->assertTrue($partida->save(), 'partida-cant-save-1');
        $this->assertNotNull($partida->id(), 'partida-not-stored');
        $this->assertTrue($partida->exists(), 'partida-cant-persist');

        // verificamos que se ha asignado el idsubcuenta
        $this->assertNotEmpty($partida->idsubcuenta, 'partida-idsubcuenta-not-set');

        // eliminamos
        $this->assertTrue($partida->delete(), 'partida-cant-delete-1');
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete-1');
    }

    public function testCreateWithCounterpart(): void
    {
        // creamos el asiento
        $asiento = new Asiento();
        $asiento->concepto = 'Test Asiento';
        $this->assertTrue($asiento->save(), 'asiento-cant-save-2');

        $ejercicio = $asiento->getExercise();

        // creamos una partida con contrapartida
        $partida = new Partida();
        $partida->idasiento = $asiento->idasiento;
        $partida->codsubcuenta = $this->getSampleSubaccount($ejercicio);
        $partida->codcontrapartida = $this->getSampleSubaccount($ejercicio);
        $partida->concepto = 'Test Partida con contrapartida';
        $partida->haber = 100;
        $this->assertTrue($partida->save(), 'partida-cant-save-2');

        // verificamos que se ha asignado el idcontrapartida
        $this->assertNotEmpty($partida->idcontrapartida, 'partida-idcontrapartida-not-set');

        // eliminamos
        $this->assertTrue($partida->delete(), 'partida-cant-delete-2');
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete-2');
    }

    public function testUpdate(): void
    {
        // creamos el asiento
        $asiento = new Asiento();
        $asiento->concepto = 'Test Asiento';
        $this->assertTrue($asiento->save(), 'asiento-cant-save-3');

        $ejercicio = $asiento->getExercise();

        // creamos una partida
        $partida = new Partida();
        $partida->idasiento = $asiento->idasiento;
        $partida->codsubcuenta = $this->getSampleSubaccount($ejercicio);
        $partida->concepto = 'Test Partida';
        $partida->debe = 100;
        $this->assertTrue($partida->save(), 'partida-cant-save-3');

        // actualizamos la partida
        $partida->concepto = 'Test Partida Modificada';
        $partida->debe = 200;
        $this->assertTrue($partida->save(), 'partida-cant-save-4');

        // recargamos y verificamos
        $partida2 = new Partida();
        $this->assertTrue($partida2->load($partida->idpartida), 'partida-cant-reload');
        $this->assertEquals('Test Partida Modificada', $partida2->concepto, 'partida-concepto-not-updated');
        $this->assertEquals(200, $partida2->debe, 'partida-debe-not-updated');

        // eliminamos
        $this->assertTrue($partida->delete(), 'partida-cant-delete-3');
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete-3');
    }

    public function testCantSaveWithoutConcept(): void
    {
        // creamos el asiento
        $asiento = new Asiento();
        $asiento->concepto = 'Test Asiento';
        $this->assertTrue($asiento->save(), 'asiento-cant-save-4');

        $ejercicio = $asiento->getExercise();

        // intentamos crear una partida sin concepto
        $partida = new Partida();
        $partida->idasiento = $asiento->idasiento;
        $partida->codsubcuenta = $this->getSampleSubaccount($ejercicio);
        $partida->concepto = '';
        $partida->debe = 100;
        $this->assertFalse($partida->save(), 'partida-can-save-without-concept');

        // eliminamos
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete-4');
    }

    public function testCantSaveWithLongConcept(): void
    {
        // creamos el asiento
        $asiento = new Asiento();
        $asiento->concepto = 'Test Asiento';
        $this->assertTrue($asiento->save(), 'asiento-cant-save-5');

        $ejercicio = $asiento->getExercise();

        // intentamos crear una partida con concepto demasiado largo
        $partida = new Partida();
        $partida->idasiento = $asiento->idasiento;
        $partida->codsubcuenta = $this->getSampleSubaccount($ejercicio);
        $partida->concepto = str_repeat('a', 256);
        $partida->debe = 100;
        $this->assertFalse($partida->save(), 'partida-can-save-with-long-concept');

        // eliminamos
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete-5');
    }

    public function testSetAccount(): void
    {
        // creamos el asiento
        $asiento = new Asiento();
        $asiento->concepto = 'Test Asiento';
        $this->assertTrue($asiento->save(), 'asiento-cant-save-6');

        $ejercicio = $asiento->getExercise();

        // obtenemos una subcuenta
        $subcuenta = $this->getSubcuenta($ejercicio);
        $this->assertNotNull($subcuenta, 'subcuenta-not-found');

        // creamos una partida usando setAccount
        $partida = new Partida();
        $partida->idasiento = $asiento->idasiento;
        $partida->setAccount($subcuenta);
        $partida->concepto = 'Test Partida setAccount';
        $partida->debe = 100;
        $this->assertTrue($partida->save(), 'partida-cant-save-7');

        // verificamos que se han establecido correctamente
        $this->assertEquals($subcuenta->codsubcuenta, $partida->codsubcuenta, 'partida-codsubcuenta-not-set');
        $this->assertEquals($subcuenta->idsubcuenta, $partida->idsubcuenta, 'partida-idsubcuenta-not-set-2');

        // eliminamos
        $this->assertTrue($partida->delete(), 'partida-cant-delete-7');
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete-7');
    }

    public function testSetCounterpart(): void
    {
        // creamos el asiento
        $asiento = new Asiento();
        $asiento->concepto = 'Test Asiento';
        $this->assertTrue($asiento->save(), 'asiento-cant-save-8');

        $ejercicio = $asiento->getExercise();

        // obtenemos una subcuenta
        $subcuenta = $this->getSubcuenta($ejercicio);
        $this->assertNotNull($subcuenta, 'subcuenta-not-found-2');

        // creamos una partida usando setCounterpart
        $partida = new Partida();
        $partida->idasiento = $asiento->idasiento;
        $partida->codsubcuenta = $this->getSampleSubaccount($ejercicio);
        $partida->setCounterpart($subcuenta);
        $partida->concepto = 'Test Partida setCounterpart';
        $partida->haber = 100;
        $this->assertTrue($partida->save(), 'partida-cant-save-8');

        // verificamos que se han establecido correctamente
        $this->assertEquals($subcuenta->codsubcuenta, $partida->codcontrapartida, 'partida-codcontrapartida-not-set');
        $this->assertEquals($subcuenta->idsubcuenta, $partida->idcontrapartida, 'partida-idcontrapartida-not-set-2');

        // eliminamos
        $this->assertTrue($partida->delete(), 'partida-cant-delete-8');
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete-8');
    }

    public function testSetDottedStatus(): void
    {
        // creamos el asiento
        $asiento = new Asiento();
        $asiento->concepto = 'Test Asiento';
        $this->assertTrue($asiento->save(), 'asiento-cant-save-9');

        $ejercicio = $asiento->getExercise();

        // creamos una partida
        $partida = new Partida();
        $partida->idasiento = $asiento->idasiento;
        $partida->codsubcuenta = $this->getSampleSubaccount($ejercicio);
        $partida->concepto = 'Test Partida punteada';
        $partida->debe = 100;
        $this->assertTrue($partida->save(), 'partida-cant-save-9');

        // verificamos que no está punteada
        $this->assertFalse($partida->punteada, 'partida-is-punteada-by-default');

        // punteamos la partida
        $partida->setDottedStatus(true);
        $this->assertTrue($partida->punteada, 'partida-cant-set-punteada');

        // despunteamos la partida
        $partida->setDottedStatus(false);
        $this->assertFalse($partida->punteada, 'partida-cant-unset-punteada');

        // eliminamos
        $this->assertTrue($partida->delete(), 'partida-cant-delete-9');
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete-9');
    }

    public function testCantSaveOnLockedEntry(): void
    {
        // creamos el asiento
        $asiento = new Asiento();
        $asiento->concepto = 'Test Asiento';
        $this->assertTrue($asiento->save(), 'asiento-cant-save-10');

        $ejercicio = $asiento->getExercise();

        // creamos una partida
        $partida = new Partida();
        $partida->idasiento = $asiento->idasiento;
        $partida->codsubcuenta = $this->getSampleSubaccount($ejercicio);
        $partida->concepto = 'Test Partida';
        $partida->debe = 100;
        $this->assertTrue($partida->save(), 'partida-cant-save-10');

        // bloqueamos el asiento
        $asiento->editable = false;
        $this->assertTrue($asiento->save(), 'asiento-cant-save-11');

        // intentamos crear una nueva partida
        $partida2 = new Partida();
        $partida2->idasiento = $asiento->idasiento;
        $partida2->codsubcuenta = $this->getSampleSubaccount($ejercicio);
        $partida2->concepto = 'Test Partida 2';
        $partida2->debe = 50;
        $this->assertFalse($partida2->save(), 'partida-can-save-on-locked-entry');

        // intentamos actualizar la partida existente
        $partida->concepto = 'Test Partida Modificada';
        $this->assertFalse($partida->save(), 'partida-can-update-on-locked-entry');

        // intentamos eliminar la partida
        $this->assertFalse($partida->delete(), 'partida-can-delete-on-locked-entry');

        // desbloqueamos el asiento
        $asiento->editable = true;
        $this->assertTrue($asiento->save(), 'asiento-cant-save-12');

        // eliminamos
        $this->assertTrue($partida->delete(), 'partida-cant-delete-10');
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete-10');
    }

    public function testCantSaveOnClosedExercise(): void
    {
        // creamos un ejercicio cerrado
        $exercise = $this->getRandomExercise();
        $exercise->estado = Ejercicio::EXERCISE_STATUS_CLOSED;
        $this->assertTrue($exercise->save(), 'can-not-close-exercise');

        // creamos un asiento
        $asiento = new Asiento();
        $asiento->concepto = 'Test';
        $asiento->codejercicio = $exercise->codejercicio;
        $asiento->fecha = $exercise->fechafin;
        $asiento->idempresa = $exercise->idempresa;

        // no se puede crear el asiento en ejercicio cerrado
        $this->assertFalse($asiento->save(), 'asiento-can-save-on-closed-exercise');

        // reabrimos el ejercicio
        $exercise->estado = Ejercicio::EXERCISE_STATUS_OPEN;
        $this->assertTrue($exercise->save(), 'can-not-open-exercise');

        // ahora sí se puede crear
        $this->assertTrue($asiento->save(), 'asiento-cant-save-on-open-exercise');

        // creamos una partida
        $partida = new Partida();
        $partida->idasiento = $asiento->idasiento;
        $partida->codsubcuenta = $this->getSampleSubaccount($exercise);
        $partida->concepto = 'Test Partida';
        $partida->debe = 100;
        $this->assertTrue($partida->save(), 'partida-cant-save-on-open-exercise');

        // cerramos el ejercicio
        $exercise->estado = Ejercicio::EXERCISE_STATUS_CLOSED;
        $this->assertTrue($exercise->save(), 'can-not-close-exercise-2');

        // no se puede crear una nueva partida
        $partida2 = new Partida();
        $partida2->idasiento = $asiento->idasiento;
        $partida2->codsubcuenta = $this->getSampleSubaccount($exercise);
        $partida2->concepto = 'Test Partida 2';
        $partida2->debe = 50;
        $this->assertFalse($partida2->save(), 'partida-can-save-on-closed-exercise');

        // no se puede modificar la partida existente
        $partida->concepto = 'Test Partida Modificada';
        $this->assertFalse($partida->save(), 'partida-can-update-on-closed-exercise');

        // no se puede eliminar la partida
        $this->assertFalse($partida->delete(), 'partida-can-delete-on-closed-exercise');

        // reabrimos el ejercicio
        $exercise->estado = Ejercicio::EXERCISE_STATUS_OPEN;
        $this->assertTrue($exercise->save(), 'can-not-open-exercise-2');

        // eliminamos
        $this->assertTrue($partida->delete(), 'partida-cant-delete-11');
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete-11');
    }

    public function testGetSubcuenta(): void
    {
        // creamos el asiento
        $asiento = new Asiento();
        $asiento->concepto = 'Test Asiento';
        $this->assertTrue($asiento->save(), 'asiento-cant-save-13');

        $ejercicio = $asiento->getExercise();

        // obtenemos una subcuenta de ejemplo
        $codsubcuenta = $this->getSampleSubaccount($ejercicio);
        $this->assertNotNull($codsubcuenta, 'subcuenta-code-not-found');

        // creamos una partida
        $partida = new Partida();
        $partida->idasiento = $asiento->idasiento;
        $partida->codsubcuenta = $codsubcuenta;
        $partida->concepto = 'Test Partida getSubcuenta';
        $partida->debe = 100;
        $this->assertTrue($partida->save(), 'partida-cant-save-14');

        // obtenemos la subcuenta desde la partida
        $subcuenta = $partida->getSubcuenta();
        $this->assertNotNull($subcuenta, 'subcuenta-not-retrieved');
        $this->assertEquals($codsubcuenta, $subcuenta->codsubcuenta, 'subcuenta-code-mismatch');

        // obtenemos otra subcuenta por parámetro
        $subcuenta2 = $partida->getSubcuenta($codsubcuenta);
        $this->assertNotNull($subcuenta2, 'subcuenta-not-retrieved-by-param');
        $this->assertEquals($codsubcuenta, $subcuenta2->codsubcuenta, 'subcuenta-code-mismatch-2');

        // eliminamos
        $this->assertTrue($partida->delete(), 'partida-cant-delete-14');
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete-14');
    }

    public function testClear(): void
    {
        $partida = new Partida();
        $partida->clear();

        // verificamos valores por defecto
        $this->assertEquals(0.0, $partida->baseimponible, 'baseimponible-not-zero');
        $this->assertEquals(0.0, $partida->debe, 'debe-not-zero');
        $this->assertEquals(0.0, $partida->haber, 'haber-not-zero');
        $this->assertEquals(0, $partida->orden, 'orden-not-zero');
        $this->assertFalse($partida->punteada, 'punteada-not-false');
        $this->assertEquals(0.0, $partida->recargo, 'recargo-not-zero');
        $this->assertEquals(0.0, $partida->saldo, 'saldo-not-zero');
        $this->assertEquals(1.0, $partida->tasaconv, 'tasaconv-not-one');
    }

    public function testDisableAdditionalTest(): void
    {
        // creamos el asiento
        $asiento = new Asiento();
        $asiento->concepto = 'Test Asiento';
        $this->assertTrue($asiento->save(), 'asiento-cant-save-15');

        $ejercicio = $asiento->getExercise();

        // creamos una partida
        $partida = new Partida();
        $partida->idasiento = $asiento->idasiento;
        $partida->codsubcuenta = $this->getSampleSubaccount($ejercicio);
        $partida->concepto = 'Test Partida';
        $partida->debe = 100;
        $this->assertTrue($partida->save(), 'partida-cant-save-15');

        // bloqueamos el asiento
        $asiento->editable = false;
        $this->assertTrue($asiento->save(), 'asiento-cant-save-16');

        // no se puede guardar
        $partida->concepto = 'Test Partida Modificada';
        $this->assertFalse($partida->save(), 'partida-can-save-on-locked-entry-2');

        // deshabilitamos el test adicional
        $partida->disableAdditionalTest(true);
        $this->assertTrue($partida->save(), 'partida-cant-save-with-disabled-test');

        // desbloqueamos el asiento
        $asiento->editable = true;
        $this->assertTrue($asiento->save(), 'asiento-cant-save-17');

        // eliminamos
        $this->assertTrue($partida->delete(), 'partida-cant-delete-15');
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete-15');
    }

    private function getSampleSubaccount(Ejercicio $eje): ?string
    {
        $where = [Where::eq('codejercicio', $eje->codejercicio)];
        foreach (Subcuenta::all($where) as $item) {
            return $item->codsubcuenta;
        }

        return null;
    }

    private function getSubcuenta(Ejercicio $eje): ?Subcuenta
    {
        $where = [Where::eq('codejercicio', $eje->codejercicio)];
        foreach (Subcuenta::all($where) as $item) {
            return $item;
        }

        return null;
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
