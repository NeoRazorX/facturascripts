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

use FacturaScripts\Core\Model\FormaPago;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class FormaPagoTest extends TestCase
{
    use LogErrorsTrait;

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

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
