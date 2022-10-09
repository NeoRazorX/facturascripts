<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021  Carlos Garcia Gomez     <carlos@facturascripts.com>
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

    public function testDataInstalled()
    {
        $payment = new FormaPago();
        $this->assertNotEmpty($payment->all(), 'payment-method-data-not-installed-from-csv');
    }

    public function testCreate()
    {
        $payment = new FormaPago();
        $payment->codpago = 'Test';
        $payment->descripcion = 'Test Payment Method';
        $this->assertTrue($payment->save(), 'payment-method-cant-save');
        $this->assertNotNull($payment->primaryColumnValue(), 'payment-method-not-stored');
        $this->assertTrue($payment->exists(), 'payment-method-cant-persist');
        $this->assertTrue($payment->delete(), 'payment-method-cant-delete');
    }

    public function testCreateWithNoCode()
    {
        $payment = new FormaPago();
        $payment->descripcion = 'Test Payment Method';
        $this->assertTrue($payment->save(), 'payment-method-cant-save');
        $this->assertTrue($payment->delete(), 'payment-method-cant-delete');
    }

    public function testDeleteDefault()
    {
        $payment = new FormaPago();
        foreach ($payment->all([], [], 0, 0) as $row) {
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
