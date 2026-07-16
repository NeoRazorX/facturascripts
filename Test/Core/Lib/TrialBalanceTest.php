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

use FacturaScripts\Core\Lib\Accounting\TrialBalance;
use FacturaScripts\Core\Model\Asiento;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Subcuenta;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class TrialBalanceTest extends TestCase
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

    public function testTrialBalanceByGroup(): void
    {
        // creamos el asiento para conocer el ejercicio por defecto
        $asiento = new Asiento();
        $asiento->concepto = 'Test balance por grupo';
        $this->assertTrue($asiento->save(), 'asiento-cant-save');
        $codejercicio = $asiento->getExercise()->codejercicio;

        $sub5 = $this->getSubaccountByGroup($codejercicio, '5');
        $sub6 = $this->getSubaccountByGroup($codejercicio, '6');
        $sub7 = $this->getSubaccountByGroup($codejercicio, '7');
        $this->assertNotNull($sub5, 'no-group-5-subaccount');
        $this->assertNotNull($sub6, 'no-group-6-subaccount');
        $this->assertNotNull($sub7, 'no-group-7-subaccount');

        // el balance puede contener datos de otros tests: comparamos diferencias
        $before = $this->indexByCode(TrialBalance::generate($codejercicio));
        $resultBefore = TrialBalance::result($codejercicio);

        // DR 6xx 100, DR 5xx 50 / CR 7xx 150
        $this->addLine($asiento, $sub6, 100, 0);
        $this->addLine($asiento, $sub5, 50, 0);
        $this->addLine($asiento, $sub7, 0, 150);

        $after = $this->indexByCode(TrialBalance::generate($codejercicio));
        $this->assertEqualsWithDelta(100.0, ($after['6']['debe'] ?? 0) - ($before['6']['debe'] ?? 0), 0.001, 'group-6-debe-wrong');
        $this->assertEqualsWithDelta(50.0, ($after['5']['debe'] ?? 0) - ($before['5']['debe'] ?? 0), 0.001, 'group-5-debe-wrong');
        $this->assertEqualsWithDelta(150.0, ($after['7']['haber'] ?? 0) - ($before['7']['haber'] ?? 0), 0.001, 'group-7-haber-wrong');

        // resultado = (haber - debe) de los grupos 6 y 7: -100 + 150 = +50
        $this->assertEqualsWithDelta(50.0, TrialBalance::result($codejercicio) - $resultBefore, 0.001, 'result-wrong');

        // eliminamos y comprobamos que el balance vuelve al estado anterior
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete');
        $this->assertEqualsWithDelta($resultBefore, TrialBalance::result($codejercicio), 0.001, 'result-not-restored');
    }

    public function testTrialBalanceByAccountAndSubaccount(): void
    {
        $asiento = new Asiento();
        $asiento->concepto = 'Test balance por cuenta';
        $this->assertTrue($asiento->save(), 'asiento-cant-save');
        $codejercicio = $asiento->getExercise()->codejercicio;

        $sub6 = $this->getSubaccountByGroup($codejercicio, '6');
        $sub7 = $this->getSubaccountByGroup($codejercicio, '7');

        $account6 = substr($sub6->codsubcuenta, 0, 3);
        $beforeAccount = $this->indexByCode(TrialBalance::generate($codejercicio, TrialBalance::LEVEL_ACCOUNT));
        $beforeSub = $this->indexByCode(TrialBalance::generate($codejercicio, TrialBalance::LEVEL_SUBACCOUNT));

        // DR 6xx 30 / CR 7xx 30
        $this->addLine($asiento, $sub6, 30, 0);
        $this->addLine($asiento, $sub7, 0, 30);

        // nivel cuenta: la cuenta de la subcuenta 6xx suma 30 al debe
        $afterAccount = $this->indexByCode(TrialBalance::generate($codejercicio, TrialBalance::LEVEL_ACCOUNT));
        $this->assertEqualsWithDelta(30.0,
            ($afterAccount[$account6]['debe'] ?? 0) - ($beforeAccount[$account6]['debe'] ?? 0), 0.001,
            'account-debe-wrong');

        // nivel subcuenta: la subcuenta exacta suma 30 al debe
        $afterSub = $this->indexByCode(TrialBalance::generate($codejercicio, TrialBalance::LEVEL_SUBACCOUNT));
        $this->assertEqualsWithDelta(30.0,
            ($afterSub[$sub6->codsubcuenta]['debe'] ?? 0) - ($beforeSub[$sub6->codsubcuenta]['debe'] ?? 0), 0.001,
            'subaccount-debe-wrong');

        $this->assertTrue($asiento->delete(), 'asiento-cant-delete');
    }

    public function testExcludesClosingEntries(): void
    {
        // asiento de regularización (operacion R)
        $asiento = new Asiento();
        $asiento->concepto = 'Test regularización';
        $asiento->operacion = Asiento::OPERATION_REGULARIZATION;
        $this->assertTrue($asiento->save(), 'asiento-cant-save');
        $codejercicio = $asiento->getExercise()->codejercicio;

        $sub6 = $this->getSubaccountByGroup($codejercicio, '6');
        $sub7 = $this->getSubaccountByGroup($codejercicio, '7');

        $before = $this->indexByCode(TrialBalance::generate($codejercicio));
        $beforeIncluding = $this->indexByCode(TrialBalance::generate($codejercicio, TrialBalance::LEVEL_GROUP, ['include_closing' => true]));

        $this->addLine($asiento, $sub6, 77, 0);
        $this->addLine($asiento, $sub7, 0, 77);

        // por defecto los asientos R quedan fuera del balance
        $after = $this->indexByCode(TrialBalance::generate($codejercicio));
        $this->assertEqualsWithDelta(0.0, ($after['6']['debe'] ?? 0) - ($before['6']['debe'] ?? 0), 0.001, 'closing-not-excluded');

        // con include_closing sí aparecen
        $afterIncluding = $this->indexByCode(TrialBalance::generate($codejercicio, TrialBalance::LEVEL_GROUP, ['include_closing' => true]));
        $this->assertEqualsWithDelta(77.0,
            ($afterIncluding['6']['debe'] ?? 0) - ($beforeIncluding['6']['debe'] ?? 0), 0.001,
            'closing-not-included');

        $this->assertTrue($asiento->delete(), 'asiento-cant-delete');
    }

    public function testDateFilter(): void
    {
        $asiento = new Asiento();
        $asiento->concepto = 'Test filtro fechas';
        $this->assertTrue($asiento->save(), 'asiento-cant-save');
        $codejercicio = $asiento->getExercise()->codejercicio;

        $sub6 = $this->getSubaccountByGroup($codejercicio, '6');
        $sub7 = $this->getSubaccountByGroup($codejercicio, '7');

        // rango que excluye la fecha del asiento (posterior a hoy)
        $tomorrow = date('Y-m-d', strtotime($asiento->fecha . ' +1 day'));
        $beforeOut = $this->indexByCode(TrialBalance::generate($codejercicio, TrialBalance::LEVEL_GROUP, ['fecha_desde' => $tomorrow]));
        $beforeIn = $this->indexByCode(TrialBalance::generate($codejercicio, TrialBalance::LEVEL_GROUP, ['fecha_hasta' => $asiento->fecha]));

        $this->addLine($asiento, $sub6, 11, 0);
        $this->addLine($asiento, $sub7, 0, 11);

        // fuera del rango: sin cambios
        $afterOut = $this->indexByCode(TrialBalance::generate($codejercicio, TrialBalance::LEVEL_GROUP, ['fecha_desde' => $tomorrow]));
        $this->assertEqualsWithDelta(0.0, ($afterOut['6']['debe'] ?? 0) - ($beforeOut['6']['debe'] ?? 0), 0.001, 'date-filter-not-applied');

        // dentro del rango: aparece
        $afterIn = $this->indexByCode(TrialBalance::generate($codejercicio, TrialBalance::LEVEL_GROUP, ['fecha_hasta' => $asiento->fecha]));
        $this->assertEqualsWithDelta(11.0, ($afterIn['6']['debe'] ?? 0) - ($beforeIn['6']['debe'] ?? 0), 0.001, 'date-filter-wrong');

        $this->assertTrue($asiento->delete(), 'asiento-cant-delete');
    }

    private function addLine(Asiento $asiento, Subcuenta $subcuenta, float $debe, float $haber): void
    {
        $line = $asiento->getNewLine();
        $line->setAccount($subcuenta);
        $line->concepto = 'Test partida';
        $line->debe = $debe;
        $line->haber = $haber;
        $this->assertTrue($line->save(), 'linea-cant-save');
    }

    private function getSubaccountByGroup(string $codejercicio, string $group): ?Subcuenta
    {
        $where = [
            Where::eq('codejercicio', $codejercicio),
            Where::gte('codsubcuenta', $group),
            Where::lt('codsubcuenta', (string)((int)$group + 1))
        ];
        foreach (Subcuenta::all($where, ['codsubcuenta' => 'ASC'], 0, 1) as $item) {
            return $item;
        }

        return null;
    }

    private function indexByCode(array $rows): array
    {
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[$row['codigo']] = $row;
        }

        return $indexed;
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
