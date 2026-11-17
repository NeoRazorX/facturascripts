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

namespace FacturaScripts\Test\Core\Controller;

use FacturaScripts\Core\Controller\EditFacturaCliente;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class EditFacturaClienteTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
    }

    public function testReceiptAmountsCanNotExceedPendingAmount(): void
    {
        // la factura se crea con un recibo por el total
        $invoice = $this->getRandomCustomerInvoice();
        $this->assertTrue($invoice->exists(), 'can-not-create-random-invoice');
        $this->assertCount(1, $invoice->getReceipts(), 'bad-invoice-receipts-count');

        $user = $this->getRandomUser();
        $this->assertTrue($user->save(), 'can-not-save-user');

        $controller = new EditFacturaCliente('EditFacturaCliente', '/EditFacturaCliente');
        $controller->user = $user;
        $method = new ReflectionMethod($controller, 'generateReceiptsWithAmounts');
        $method->setAccessible(true);

        try {
            // no queda nada pendiente: no se crean recibos
            $method->invoke($controller, $invoice, [30]);
            $this->assertCount(1, $invoice->getReceipts(), 'receipt-created-without-pending-amount');
            $this->assertEquals($invoice->total, $this->sumReceipts($invoice), 'receipts-above-invoice-total');

            // dejamos 30 pendientes bajando el recibo
            $receipt = $invoice->getReceipts()[0];
            $receipt->importe = $invoice->total - 30;
            $this->assertTrue($receipt->save(), 'can-not-save-receipt');

            // más de lo pendiente: no se crean recibos
            $method->invoke($controller, $invoice, [20, 20]);
            $this->assertCount(1, $invoice->getReceipts(), 'receipts-created-above-pending-amount');

            // lo pendiente: se crea el recibo
            $method->invoke($controller, $invoice, [30]);
            $this->assertCount(2, $invoice->getReceipts(), 'receipt-not-created-for-pending-amount');
            $this->assertEquals($invoice->total, $this->sumReceipts($invoice), 'bad-receipts-total');
        } finally {
            // eliminamos
            $subject = $invoice->getSubject();
            $this->assertTrue($invoice->delete(), 'can-not-delete-invoice');
            $this->assertTrue($subject->getDefaultAddress()->delete(), 'can-not-delete-address');
            $this->assertTrue($subject->delete(), 'can-not-delete-subject');
            $this->assertTrue($user->delete(), 'can-not-delete-user');
        }
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }

    private function sumReceipts($invoice): float
    {
        $sum = 0.0;
        foreach ($invoice->getReceipts() as $receipt) {
            $sum += $receipt->importe;
        }

        return round($sum, 2);
    }
}
