<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Model\Join;

use FacturaScripts\Core\Model\Asiento;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Core\Model\Join\SubcuentaSaldo;
use FacturaScripts\Core\Model\Partida;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Subcuenta;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers SubcuentaSaldo
 */
final class SubcuentaSaldoTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
    }

    public function testTotalSumDebeAndHaber(): void
    {
        // creamos el asiento
        $asiento = new Asiento();
        $asiento->concepto = 'Test SubcuentaSaldo totalSum';
        $this->assertTrue($asiento->save(), 'asiento-cant-save');

        $ejercicio = $asiento->getExercise();
        $codsubcuenta = $this->getSampleSubaccount($ejercicio);
        $this->assertNotNull($codsubcuenta, 'subcuenta-not-found');

        // dos partidas al debe (100 + 50 = 150)
        $partida1 = new Partida();
        $partida1->idasiento = $asiento->idasiento;
        $partida1->codsubcuenta = $codsubcuenta;
        $partida1->concepto = 'Test Partida debe 1';
        $partida1->debe = 100;
        $this->assertTrue($partida1->save(), 'partida1-cant-save');

        $partida2 = new Partida();
        $partida2->idasiento = $asiento->idasiento;
        $partida2->codsubcuenta = $codsubcuenta;
        $partida2->concepto = 'Test Partida debe 2';
        $partida2->debe = 50;
        $this->assertTrue($partida2->save(), 'partida2-cant-save');

        // dos partidas al haber (70 + 30 = 100)
        $partida3 = new Partida();
        $partida3->idasiento = $asiento->idasiento;
        $partida3->codsubcuenta = $codsubcuenta;
        $partida3->concepto = 'Test Partida haber 1';
        $partida3->haber = 70;
        $this->assertTrue($partida3->save(), 'partida3-cant-save');

        $partida4 = new Partida();
        $partida4->idasiento = $asiento->idasiento;
        $partida4->codsubcuenta = $codsubcuenta;
        $partida4->concepto = 'Test Partida haber 2';
        $partida4->haber = 30;
        $this->assertTrue($partida4->save(), 'partida4-cant-save');

        // filtramos por el asiento para aislar las partidas de este test.
        // debe y haber son campos SUM(...), totalSum no debe doble-envolverlos.
        $where = [Where::eq('partidas.idasiento', $asiento->idasiento)];
        $ss = new SubcuentaSaldo();
        $this->assertEqualsWithDelta(150.0, $ss->totalSum('debe', $where), 0.001, 'total-sum-debe-wrong');
        $this->assertEqualsWithDelta(100.0, $ss->totalSum('haber', $where), 0.001, 'total-sum-haber-wrong');

        // eliminamos
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete');
    }

    private function getSampleSubaccount(Ejercicio $eje): ?string
    {
        $where = [Where::eq('codejercicio', $eje->codejercicio)];
        foreach (Subcuenta::all($where) as $item) {
            return $item->codsubcuenta;
        }

        return null;
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
