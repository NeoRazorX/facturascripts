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

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Lib\Accounting\BalanceRecalculator;
use FacturaScripts\Core\Model\Asiento;
use FacturaScripts\Core\Model\Cuenta;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Core\Model\Subcuenta;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class BalanceRecalculatorTest extends TestCase
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

    public function testRecalculateSubaccountBalances(): void
    {
        // creamos un asiento con dos líneas
        $asiento = new Asiento();
        $asiento->concepto = 'Test recalculo saldos';
        $this->assertTrue($asiento->save(), 'asiento-cant-save');

        $ejercicio = $asiento->getExercise();
        $subcuenta1 = $this->getSampleSubaccount($ejercicio->codejercicio, 0);
        $subcuenta2 = $this->getSampleSubaccount($ejercicio->codejercicio, 1);
        $this->assertNotNull($subcuenta1, 'no-subaccount-1');
        $this->assertNotNull($subcuenta2, 'no-subaccount-2');

        $firstLine = $asiento->getNewLine();
        $firstLine->codsubcuenta = $subcuenta1->codsubcuenta;
        $firstLine->concepto = 'Test linea 1';
        $firstLine->debe = 100;
        $this->assertTrue($firstLine->save(), 'linea-cant-save-1');

        $secondLine = $asiento->getNewLine();
        $secondLine->codsubcuenta = $subcuenta2->codsubcuenta;
        $secondLine->concepto = 'Test linea 2';
        $secondLine->haber = 100;
        $this->assertTrue($secondLine->save(), 'linea-cant-save-2');

        // corrompemos los saldos en caché de las subcuentas
        $db = new DataBase();
        $sql = 'UPDATE ' . Subcuenta::tableName() . ' SET debe = 999, haber = 888, saldo = 111'
            . ' WHERE idsubcuenta IN (' . $db->var2str($subcuenta1->idsubcuenta)
            . ', ' . $db->var2str($subcuenta2->idsubcuenta) . ')';
        $this->assertTrue($db->exec($sql), 'cant-corrupt-subaccounts');

        // recalculamos
        $this->assertTrue(BalanceRecalculator::run($ejercicio->codejercicio), 'recalculator-failed');

        // comprobamos que los saldos vuelven a coincidir con las partidas
        $subcuenta1->load($subcuenta1->idsubcuenta);
        $this->assertEqualsWithDelta(100.0, $subcuenta1->debe, 0.001, 'sub1-debe-wrong');
        $this->assertEqualsWithDelta(0.0, $subcuenta1->haber, 0.001, 'sub1-haber-wrong');
        $this->assertEqualsWithDelta(100.0, $subcuenta1->saldo, 0.001, 'sub1-saldo-wrong');

        $subcuenta2->load($subcuenta2->idsubcuenta);
        $this->assertEqualsWithDelta(0.0, $subcuenta2->debe, 0.001, 'sub2-debe-wrong');
        $this->assertEqualsWithDelta(100.0, $subcuenta2->haber, 0.001, 'sub2-haber-wrong');
        $this->assertEqualsWithDelta(-100.0, $subcuenta2->saldo, 0.001, 'sub2-saldo-wrong');

        // eliminamos el asiento y comprobamos que el recálculo deja las subcuentas a cero
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete');
        $this->assertTrue(BalanceRecalculator::run($ejercicio->codejercicio), 'recalculator-failed-2');

        $subcuenta1->load($subcuenta1->idsubcuenta);
        $this->assertEqualsWithDelta(0.0, $subcuenta1->debe, 0.001, 'sub1-debe-not-zero');
        $this->assertEqualsWithDelta(0.0, $subcuenta1->saldo, 0.001, 'sub1-saldo-not-zero');
    }

    public function testRecalculateAccountBalances(): void
    {
        // creamos un asiento con dos líneas
        $asiento = new Asiento();
        $asiento->concepto = 'Test recalculo cuentas';
        $this->assertTrue($asiento->save(), 'asiento-cant-save');

        $ejercicio = $asiento->getExercise();
        $subcuenta1 = $this->getSampleSubaccount($ejercicio->codejercicio, 0);
        $subcuenta2 = $this->getSampleSubaccount($ejercicio->codejercicio, 1);

        $firstLine = $asiento->getNewLine();
        $firstLine->codsubcuenta = $subcuenta1->codsubcuenta;
        $firstLine->concepto = 'Test linea 1';
        $firstLine->debe = 50;
        $this->assertTrue($firstLine->save(), 'linea-cant-save-1');

        $secondLine = $asiento->getNewLine();
        $secondLine->codsubcuenta = $subcuenta2->codsubcuenta;
        $secondLine->concepto = 'Test linea 2';
        $secondLine->haber = 50;
        $this->assertTrue($secondLine->save(), 'linea-cant-save-2');

        // corrompemos el saldo en caché de la cuenta de la primera subcuenta
        $db = new DataBase();
        $sql = 'UPDATE ' . Cuenta::tableName() . ' SET debe = 777, haber = 666, saldo = 555'
            . ' WHERE idcuenta = ' . $db->var2str($subcuenta1->idcuenta);
        $this->assertTrue($db->exec($sql), 'cant-corrupt-account');

        // recalculamos
        $this->assertTrue(BalanceRecalculator::run($ejercicio->codejercicio), 'recalculator-failed');

        // la cuenta debe sumar los saldos de sus subcuentas
        $cuenta = new Cuenta();
        $this->assertTrue($cuenta->load($subcuenta1->idcuenta), 'account-not-found');

        $expectedDebe = 0.0;
        $expectedHaber = 0.0;
        foreach (Subcuenta::all([Where::eq('idcuenta', $cuenta->idcuenta)], [], 0, 0) as $sub) {
            $expectedDebe += $sub->debe;
            $expectedHaber += $sub->haber;
        }
        foreach ($cuenta->getChildren() as $child) {
            $expectedDebe += $child->debe;
            $expectedHaber += $child->haber;
        }
        $this->assertEqualsWithDelta($expectedDebe, $cuenta->debe, 0.001, 'account-debe-wrong');
        $this->assertEqualsWithDelta($expectedHaber, $cuenta->haber, 0.001, 'account-haber-wrong');
        $this->assertEqualsWithDelta($expectedDebe - $expectedHaber, $cuenta->saldo, 0.001, 'account-saldo-wrong');

        // eliminamos
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete');
    }

    public function testUnusedSubaccountIsZeroed(): void
    {
        // necesitamos el ejercicio por defecto: lo obtenemos de un asiento temporal
        $asiento = new Asiento();
        $asiento->concepto = 'Test subcuenta sin uso';
        $this->assertTrue($asiento->save(), 'asiento-cant-save');
        $codejercicio = $asiento->getExercise()->codejercicio;
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete');

        // buscamos una subcuenta sin partidas y le ponemos un saldo falso
        $subcuenta = $this->getSampleSubaccount($codejercicio, 2);
        $this->assertNotNull($subcuenta, 'no-subaccount');

        $db = new DataBase();
        $sql = 'UPDATE ' . Subcuenta::tableName() . ' SET debe = 123, haber = 45, saldo = 78'
            . ' WHERE idsubcuenta = ' . $db->var2str($subcuenta->idsubcuenta);
        $this->assertTrue($db->exec($sql), 'cant-corrupt-subaccount');

        // recalculamos y comprobamos que vuelve a cero (no tiene partidas)
        $this->assertTrue(BalanceRecalculator::run($codejercicio), 'recalculator-failed');

        $subcuenta->load($subcuenta->idsubcuenta);
        $this->assertEqualsWithDelta(0.0, $subcuenta->debe, 0.001, 'debe-not-zero');
        $this->assertEqualsWithDelta(0.0, $subcuenta->haber, 0.001, 'haber-not-zero');
        $this->assertEqualsWithDelta(0.0, $subcuenta->saldo, 0.001, 'saldo-not-zero');
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
