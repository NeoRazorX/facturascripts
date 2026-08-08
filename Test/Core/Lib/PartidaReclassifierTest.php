<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Lib;

use FacturaScripts\Core\Lib\Accounting\PartidaReclassifier;
use FacturaScripts\Core\Model\Asiento;
use FacturaScripts\Core\Model\Cuenta;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Core\Model\Partida;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Subcuenta;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class PartidaReclassifierTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();

        // nos aseguramos de que existe el ejercicio actual antes de instalar el plan contable
        $exercise = new Ejercicio();
        $exercise->idempresa = Tools::settings('default', 'idempresa', 1);
        $exercise->loadFromDate(Tools::date());

        self::installAccountingPlan();
        self::removeTaxRegularization();
    }

    public function testMoveLine(): void
    {
        // creamos un asiento con dos líneas
        $asiento = new Asiento();
        $asiento->concepto = 'Test reclasificar';
        $this->assertTrue($asiento->save(), 'asiento-cant-save');

        $codejercicio = $asiento->getExercise()->codejercicio;
        $subcuenta1 = $this->getSampleSubaccount($codejercicio, 0);
        $subcuenta2 = $this->getSampleSubaccount($codejercicio, 1);
        $target = $this->getSampleSubaccount($codejercicio, 2);

        $firstLine = $asiento->getNewLine();
        $firstLine->setAccount($subcuenta1);
        $firstLine->concepto = 'Test linea 1';
        $firstLine->debe = 100;
        $this->assertTrue($firstLine->save(), 'linea-cant-save-1');

        $secondLine = $asiento->getNewLine();
        $secondLine->setAccount($subcuenta2);
        $secondLine->concepto = 'Test linea 2';
        $secondLine->haber = 100;
        $this->assertTrue($secondLine->save(), 'linea-cant-save-2');

        // movemos la primera línea a la subcuenta de destino
        $this->assertTrue(
            PartidaReclassifier::move([$firstLine->idpartida], $target),
            'reclassifier-failed'
        );

        // la partida apunta ahora a la subcuenta de destino, con el mismo concepto
        $moved = new Partida();
        $this->assertTrue($moved->load($firstLine->idpartida), 'partida-not-found');
        $this->assertEquals($target->idsubcuenta, $moved->idsubcuenta, 'idsubcuenta-not-updated');
        $this->assertEquals($target->codsubcuenta, $moved->codsubcuenta, 'codsubcuenta-not-updated');
        $this->assertEquals('Test linea 1', $moved->concepto, 'concepto-changed');

        // los saldos de origen y destino se han actualizado
        $subcuenta1->load($subcuenta1->idsubcuenta);
        $this->assertEqualsWithDelta(0.0, $subcuenta1->debe, 0.001, 'old-subaccount-not-updated');

        $target->load($target->idsubcuenta);
        $this->assertEqualsWithDelta(100.0, $target->debe, 0.001, 'new-subaccount-not-updated');

        // eliminamos
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete');
    }

    public function testMoveLineWithNewConcept(): void
    {
        // creamos un asiento con dos líneas
        $asiento = new Asiento();
        $asiento->concepto = 'Test reclasificar concepto';
        $this->assertTrue($asiento->save(), 'asiento-cant-save');

        $codejercicio = $asiento->getExercise()->codejercicio;
        $subcuenta1 = $this->getSampleSubaccount($codejercicio, 0);
        $subcuenta2 = $this->getSampleSubaccount($codejercicio, 1);
        $target = $this->getSampleSubaccount($codejercicio, 2);

        $firstLine = $asiento->getNewLine();
        $firstLine->setAccount($subcuenta1);
        $firstLine->concepto = 'Concepto antiguo';
        $firstLine->debe = 50;
        $this->assertTrue($firstLine->save(), 'linea-cant-save-1');

        $secondLine = $asiento->getNewLine();
        $secondLine->setAccount($subcuenta2);
        $secondLine->concepto = 'Contrapartida';
        $secondLine->haber = 50;
        $this->assertTrue($secondLine->save(), 'linea-cant-save-2');

        // movemos reescribiendo el concepto
        $this->assertTrue(
            PartidaReclassifier::move([$firstLine->idpartida], $target, 'Concepto nuevo'),
            'reclassifier-failed'
        );

        $moved = new Partida();
        $this->assertTrue($moved->load($firstLine->idpartida), 'partida-not-found');
        $this->assertEquals('Concepto nuevo', $moved->concepto, 'concepto-not-updated');
        $this->assertEquals($target->idsubcuenta, $moved->idsubcuenta, 'idsubcuenta-not-updated');

        // eliminamos
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete');
    }

    public function testRejectDifferentExercise(): void
    {
        // creamos un asiento con una línea equilibrada
        $asiento = new Asiento();
        $asiento->concepto = 'Test reclasificar otro ejercicio';
        $this->assertTrue($asiento->save(), 'asiento-cant-save');

        $codejercicio = $asiento->getExercise()->codejercicio;
        $subcuenta1 = $this->getSampleSubaccount($codejercicio, 0);

        $firstLine = $asiento->getNewLine();
        $firstLine->setAccount($subcuenta1);
        $firstLine->concepto = 'Test linea 1';
        $firstLine->debe = 25;
        $this->assertTrue($firstLine->save(), 'linea-cant-save-1');

        // creamos otro ejercicio con una subcuenta
        $otherExercise = new Ejercicio();
        $otherExercise->codejercicio = '2001';
        $otherExercise->nombre = 'Test 2001';
        $otherExercise->fechainicio = '2001-01-01';
        $otherExercise->fechafin = '2001-12-31';
        $this->assertTrue($otherExercise->save(), 'ejercicio-cant-save');

        $otherAccount = new Cuenta();
        $otherAccount->codejercicio = $otherExercise->codejercicio;
        $otherAccount->codcuenta = '100';
        $otherAccount->descripcion = 'Capital';
        $this->assertTrue($otherAccount->save(), 'cuenta-cant-save');

        $otherSubaccount = new Subcuenta();
        $otherSubaccount->codejercicio = $otherExercise->codejercicio;
        $otherSubaccount->codsubcuenta = '1000000000';
        $otherSubaccount->descripcion = 'Capital social';
        $otherSubaccount->idcuenta = $otherAccount->idcuenta;
        $this->assertTrue($otherSubaccount->save(), 'subcuenta-cant-save');

        // no se puede mover una partida a una subcuenta de otro ejercicio
        $this->assertFalse(
            PartidaReclassifier::move([$firstLine->idpartida], $otherSubaccount),
            'reclassifier-should-fail'
        );

        // la partida no ha cambiado
        $unchanged = new Partida();
        $this->assertTrue($unchanged->load($firstLine->idpartida), 'partida-not-found');
        $this->assertEquals($subcuenta1->idsubcuenta, $unchanged->idsubcuenta, 'partida-changed');

        // eliminamos
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete');
        $this->assertTrue($otherSubaccount->delete(), 'subcuenta-cant-delete');
        $this->assertTrue($otherAccount->delete(), 'cuenta-cant-delete');
        $this->assertTrue($otherExercise->delete(), 'ejercicio-cant-delete');
    }

    public function testRejectEmptyList(): void
    {
        $subcuenta = new Subcuenta();
        $this->assertFalse(PartidaReclassifier::move([], $subcuenta), 'empty-list-should-fail');
    }

    private function getSampleSubaccount(string $codejercicio, int $offset): ?Subcuenta
    {
        $where = [Where::eq('codejercicio', $codejercicio)];
        foreach (Subcuenta::all($where, ['codsubcuenta' => 'ASC'], $offset, 1) as $item) {
            return $item;
        }

        return null;
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
