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

namespace FacturaScripts\Test\Core\Model;

use FacturaScripts\Core\Model\CuentaBanco;
use FacturaScripts\Core\Model\CuentaEspecial;
use FacturaScripts\Core\Model\FormaPago;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class FormaPagoTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
    }

    public function testDataInstalled(): void
    {
        $this->assertNotEmpty(FormaPago::all(), 'payment-method-data-not-installed-from-csv');
    }

    public function testCreate(): void
    {
        // creamos una forma de pago
        $payment = new FormaPago();
        $payment->codpago = 'Test';
        $payment->descripcion = 'Test Payment Method';
        $this->assertTrue($payment->save(), 'payment-method-cant-save');

        // comprobamos que se ha guardado
        $this->assertNotNull($payment->id(), 'payment-method-not-stored');
        $this->assertTrue($payment->exists(), 'payment-method-cant-persist');

        // eliminamos
        $this->assertTrue($payment->delete(), 'payment-method-cant-delete');
    }

    public function testCreateWithNoCode(): void
    {
        // sin código, debe usar las primeras 4 letras de la descripción
        $payment = new FormaPago();
        $payment->descripcion = 'Transferencia bancaria';
        $this->assertTrue($payment->save(), 'payment-method-cant-save');
        $this->assertEquals('TRAN', $payment->codpago);
        $this->assertTrue($payment->delete(), 'payment-method-cant-delete');
    }

    public function testCreateWithNoCodeFallsBackToDigit(): void
    {
        // ocupamos el código 'TRAN'
        $payment1 = new FormaPago();
        $payment1->codpago = 'TRAN';
        $payment1->descripcion = 'TRAN ocupada';
        $this->assertTrue($payment1->save(), 'payment-method-cant-save');

        // al estar 'TRAN' ocupado, debe usar 'TRAN2'
        $payment2 = new FormaPago();
        $payment2->descripcion = 'Transferencia bancaria';
        $this->assertTrue($payment2->save(), 'payment-method-cant-save');
        $this->assertEquals('TRAN2', $payment2->codpago);

        // eliminamos
        $this->assertTrue($payment2->delete(), 'payment-method-cant-delete');
        $this->assertTrue($payment1->delete(), 'payment-method-cant-delete');
    }

    public function testDeleteDefault(): void
    {
        foreach (FormaPago::all() as $row) {
            if ($row->isDefault()) {
                $this->assertFalse($row->delete(), 'payment-method-default-cant-delete');
                break;
            }
        }
    }

    public function testSubaccountWithBankAccountUsesBank(): void
    {
        $exercise = $this->getRandomExercise();

        // creamos una cuenta bancaria sin subcuenta asignada
        $bank = new CuentaBanco();
        $bank->descripcion = 'Test bank';
        $this->assertTrue($bank->save(), 'bank-account-cant-save');

        // creamos una forma de pago con esa cuenta bancaria
        $payment = new FormaPago();
        $payment->codpago = 'TestB';
        $payment->descripcion = 'Test bank payment';
        $payment->codcuentabanco = $bank->codcuenta;
        $this->assertTrue($payment->save(), 'payment-method-cant-save');

        // debe usar la subcuenta especial de bancos, o la de caja si el plan no define bancos
        $expected = $this->getSpecialSubaccountCode(['BANCO', 'CAJA'], $exercise->codejercicio);
        $subcuenta = $payment->getSubcuenta($exercise->codejercicio, true);
        $this->assertEquals($expected, $subcuenta->codsubcuenta, 'payment-subaccount-not-bank');

        // eliminamos
        $this->assertTrue($payment->delete(), 'payment-method-cant-delete');
        $this->assertTrue($bank->delete(), 'bank-account-cant-delete');
    }

    public function testSubaccountWithoutBankAccountUsesCash(): void
    {
        $exercise = $this->getRandomExercise();

        // creamos una forma de pago sin cuenta bancaria
        $payment = new FormaPago();
        $payment->codpago = 'TestC';
        $payment->descripcion = 'Test cash payment';
        $this->assertTrue($payment->save(), 'payment-method-cant-save');

        // debe usar la subcuenta especial de caja, o la de bancos si el plan no define caja
        $expected = $this->getSpecialSubaccountCode(['CAJA', 'BANCO'], $exercise->codejercicio);
        $subcuenta = $payment->getSubcuenta($exercise->codejercicio, true);
        $this->assertEquals($expected, $subcuenta->codsubcuenta, 'payment-subaccount-not-cash');

        // eliminamos
        $this->assertTrue($payment->delete(), 'payment-method-cant-delete');
    }

    /**
     * Devuelve el código de la primera subcuenta especial que encuentre en el ejercicio,
     * siguiendo el orden indicado. Omite el test si el plan contable no define ninguna.
     */
    private function getSpecialSubaccountCode(array $codes, string $codejercicio): string
    {
        foreach ($codes as $code) {
            $especial = new CuentaEspecial();
            if (false === $especial->load($code)) {
                continue;
            }

            $subcuenta = $especial->getSubcuenta($codejercicio);
            if (!empty($subcuenta->codsubcuenta)) {
                return $subcuenta->codsubcuenta;
            }
        }

        $this->markTestSkipped('accounting-plan-without-special-subaccounts: ' . implode(', ', $codes));
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
