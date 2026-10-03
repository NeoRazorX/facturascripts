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

use FacturaScripts\Core\Lib\Accounting\BankStatementMatcher;
use FacturaScripts\Core\Model\Asiento;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Subcuenta;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class BankStatementMatcherTest extends TestCase
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

    public function testMatchesByAmountAndSign(): void
    {
        // asiento: DR subcuenta1 8642.97 / CR subcuenta2 8642.97
        $asiento = new Asiento();
        $asiento->concepto = 'Test conciliación';
        $this->assertTrue($asiento->save(), 'asiento-cant-save');
        $codejercicio = $asiento->getExercise()->codejercicio;

        $sub1 = $this->getSampleSubaccount($codejercicio, 0);
        $sub2 = $this->getSampleSubaccount($codejercicio, 1);
        $debitLine = $this->addLine($asiento, $sub1, 8642.97, 0);
        $creditLine = $this->addLine($asiento, $sub2, 0, 8642.97);

        $result = BankStatementMatcher::match([
            ['fecha' => $asiento->fecha, 'importe' => 8642.97, 'concepto' => 'entrada banco'],
            ['fecha' => $asiento->fecha, 'importe' => -8642.97, 'concepto' => 'salida banco'],
            ['fecha' => $asiento->fecha, 'importe' => 999888.77, 'concepto' => 'sin correspondencia'],
        ], ['codejercicio' => $codejercicio]);

        $this->assertCount(2, $result['matched'], 'wrong-matched-count');
        $this->assertCount(1, $result['unmatched'], 'wrong-unmatched-count');

        // el importe positivo casa con la partida del debe, el negativo con la del haber
        $this->assertEquals($debitLine->idpartida, $result['matched'][0]['idpartida'], 'debit-not-matched');
        $this->assertEquals($creditLine->idpartida, $result['matched'][1]['idpartida'], 'credit-not-matched');
        $this->assertEquals('sin correspondencia', $result['unmatched'][0]['concepto'], 'wrong-unmatched-line');

        $this->assertTrue($asiento->delete(), 'asiento-cant-delete');
    }

    public function testDateTolerance(): void
    {
        $asiento = new Asiento();
        $asiento->concepto = 'Test tolerancia fechas';
        $this->assertTrue($asiento->save(), 'asiento-cant-save');
        $codejercicio = $asiento->getExercise()->codejercicio;

        $sub1 = $this->getSampleSubaccount($codejercicio, 0);
        $sub2 = $this->getSampleSubaccount($codejercicio, 1);
        $this->addLine($asiento, $sub1, 7531.86, 0);
        $this->addLine($asiento, $sub2, 0, 7531.86);

        // la línea del extracto llega 2 días después del asiento
        $extractDate = date('Y-m-d', strtotime($asiento->fecha . ' +2 days'));
        $lines = [['fecha' => $extractDate, 'importe' => 7531.86]];

        // con la tolerancia por defecto (3 días) casa
        $result = BankStatementMatcher::match($lines, ['codejercicio' => $codejercicio]);
        $this->assertCount(1, $result['matched'], 'not-matched-within-tolerance');
        $this->assertEquals(2, $result['matched'][0]['dias'], 'wrong-date-distance');

        // con tolerancia de 1 día no casa
        $result = BankStatementMatcher::match($lines, ['codejercicio' => $codejercicio, 'days' => 1]);
        $this->assertCount(0, $result['matched'], 'matched-outside-tolerance');
        $this->assertCount(1, $result['unmatched'], 'wrong-unmatched-count');

        $this->assertTrue($asiento->delete(), 'asiento-cant-delete');
    }

    public function testEachPartidaMatchesOnce(): void
    {
        $asiento = new Asiento();
        $asiento->concepto = 'Test partida única';
        $this->assertTrue($asiento->save(), 'asiento-cant-save');
        $codejercicio = $asiento->getExercise()->codejercicio;

        $sub1 = $this->getSampleSubaccount($codejercicio, 0);
        $sub2 = $this->getSampleSubaccount($codejercicio, 1);
        $this->addLine($asiento, $sub1, 6420.13, 0);
        $this->addLine($asiento, $sub2, 0, 6420.13);

        // dos líneas idénticas del extracto, pero solo hay una partida del debe
        $result = BankStatementMatcher::match([
            ['fecha' => $asiento->fecha, 'importe' => 6420.13],
            ['fecha' => $asiento->fecha, 'importe' => 6420.13],
        ], ['codejercicio' => $codejercicio]);

        $this->assertCount(1, $result['matched'], 'partida-matched-twice');
        $this->assertCount(1, $result['unmatched'], 'wrong-unmatched-count');

        $this->assertTrue($asiento->delete(), 'asiento-cant-delete');
    }

    public function testSubaccountFilter(): void
    {
        $asiento = new Asiento();
        $asiento->concepto = 'Test filtro subcuenta';
        $this->assertTrue($asiento->save(), 'asiento-cant-save');
        $codejercicio = $asiento->getExercise()->codejercicio;

        $sub1 = $this->getSampleSubaccount($codejercicio, 0);
        $sub2 = $this->getSampleSubaccount($codejercicio, 1);
        $this->addLine($asiento, $sub1, 5319.24, 0);
        $this->addLine($asiento, $sub2, 0, 5319.24);

        $lines = [['fecha' => $asiento->fecha, 'importe' => 5319.24]];

        // filtrando por la subcuenta correcta casa
        $result = BankStatementMatcher::match($lines, [
            'codejercicio' => $codejercicio,
            'codsubcuenta' => $sub1->codsubcuenta
        ]);
        $this->assertCount(1, $result['matched'], 'not-matched-with-filter');

        // filtrando por otra subcuenta no casa (el debe está en sub1)
        $result = BankStatementMatcher::match($lines, [
            'codejercicio' => $codejercicio,
            'codsubcuenta' => $sub2->codsubcuenta
        ]);
        $this->assertCount(0, $result['matched'], 'matched-with-wrong-filter');

        $this->assertTrue($asiento->delete(), 'asiento-cant-delete');
    }

    private function addLine(Asiento $asiento, Subcuenta $subcuenta, float $debe, float $haber)
    {
        $line = $asiento->getNewLine();
        $line->setAccount($subcuenta);
        $line->concepto = 'Test partida';
        $line->debe = $debe;
        $line->haber = $haber;
        $this->assertTrue($line->save(), 'linea-cant-save');

        return $line;
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
