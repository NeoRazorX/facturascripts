<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2025-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\RegularizacionImpuesto;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

/**
 * Tests para verificar que PaymentToAccounting genera asientos contables
 * aunque la fecha del pago coincida con el período de una regularización de IVA.
 */
final class PaymentToAccountingTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
        self::removeTaxRegularization();
    }

    public function testCustomerPaymentInsideLockedRegularizationCreatesAccountingEntry(): void
    {
        // creamos una regularización de T1 con bloqueo activo
        $reg = new RegularizacionImpuesto();
        $reg->periodo = 'T1';
        $reg->bloquear = true;
        $this->assertTrue($reg->save(), 'can-not-save-regularization');

        // usamos una fecha dentro del período de regularización (15 de marzo)
        $year = date('Y', strtotime($reg->fechainicio));
        $paymentDate = '15-03-' . $year;

        $invoice = $this->getRandomCustomerInvoice($paymentDate);
        $this->assertTrue($invoice->exists(), 'can-not-create-invoice');

        $receipts = $invoice->getReceipts();
        $this->assertCount(1, $receipts, 'bad-invoice-receipts-count');

        // marcamos el recibo como pagado con fecha dentro de la regularización
        $receipts[0]->pagado = true;
        $receipts[0]->fechapago = $paymentDate;
        $this->assertTrue($receipts[0]->save(), 'can-not-set-paid-receipt');

        $payments = $receipts[0]->getPayments();
        $this->assertCount(1, $payments, 'should-have-one-payment');
        $this->assertEquals($paymentDate, $payments[0]->fecha, 'bad-payment-date');

        // BUG: aunque la fecha cae dentro de una regularización de IVA con bloqueo,
        // el asiento contable del pago debe generarse igualmente
        $this->assertNotEmpty(
            $payments[0]->idasiento,
            'payment-should-have-accounting-entry-even-inside-locked-regularization'
        );

        $this->assertTrue($reg->delete(), 'can-not-delete-regularization');
        $subject = $invoice->getSubject();
        $this->assertTrue($invoice->delete(), 'can-not-delete-invoice');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-subject');
    }

    public function testCustomerPaymentInsideUnlockedRegularizationCreatesAccountingEntry(): void
    {
        // con bloquear=false la regularización no debe impedir la creación del asiento
        $reg = new RegularizacionImpuesto();
        $reg->periodo = 'T1';
        $reg->bloquear = false;
        $this->assertTrue($reg->save(), 'can-not-save-regularization');

        $year = date('Y', strtotime($reg->fechainicio));
        $paymentDate = '15-03-' . $year;

        $invoice = $this->getRandomCustomerInvoice($paymentDate);
        $this->assertTrue($invoice->exists(), 'can-not-create-invoice');

        $receipts = $invoice->getReceipts();
        $this->assertCount(1, $receipts, 'bad-invoice-receipts-count');

        $receipts[0]->pagado = true;
        $receipts[0]->fechapago = $paymentDate;
        $this->assertTrue($receipts[0]->save(), 'can-not-set-paid-receipt');

        $payments = $receipts[0]->getPayments();
        $this->assertCount(1, $payments, 'should-have-one-payment');

        // sin bloqueo, el asiento debe generarse sin problema
        $this->assertNotEmpty(
            $payments[0]->idasiento,
            'payment-should-have-accounting-entry-inside-unlocked-regularization'
        );

        $this->assertTrue($reg->delete(), 'can-not-delete-regularization');
        $subject = $invoice->getSubject();
        $this->assertTrue($invoice->delete(), 'can-not-delete-invoice');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-subject');
    }

    public function testCustomerPaymentOnRegularizationEndDateCreatesAccountingEntry(): void
    {
        // creamos una regularización de T1 con bloqueo activo
        $reg = new RegularizacionImpuesto();
        $reg->periodo = 'T1';
        $reg->bloquear = true;
        $this->assertTrue($reg->save(), 'can-not-save-regularization');

        // usamos exactamente la fecha de fin de la regularización
        $paymentDate = $reg->fechafin;

        $invoice = $this->getRandomCustomerInvoice($paymentDate);
        $this->assertTrue($invoice->exists(), 'can-not-create-invoice');

        $receipts = $invoice->getReceipts();
        $this->assertCount(1, $receipts, 'bad-invoice-receipts-count');

        $receipts[0]->pagado = true;
        $receipts[0]->fechapago = $paymentDate;
        $this->assertTrue($receipts[0]->save(), 'can-not-set-paid-receipt');

        $payments = $receipts[0]->getPayments();
        $this->assertCount(1, $payments, 'should-have-one-payment');

        // el pago en la fecha exacta de fin de la regularización debe generar asiento contable
        $this->assertNotEmpty(
            $payments[0]->idasiento,
            'payment-on-regularization-end-date-should-have-accounting-entry'
        );

        $this->assertTrue($reg->delete(), 'can-not-delete-regularization');
        $subject = $invoice->getSubject();
        $this->assertTrue($invoice->delete(), 'can-not-delete-invoice');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-subject');
    }

    public function testCustomerPaymentOnRegularizationStartDateCreatesAccountingEntry(): void
    {
        // creamos una regularización de T1 con bloqueo activo
        $reg = new RegularizacionImpuesto();
        $reg->periodo = 'T1';
        $reg->bloquear = true;
        $this->assertTrue($reg->save(), 'can-not-save-regularization');

        // usamos exactamente la fecha de inicio de la regularización
        $paymentDate = $reg->fechainicio;

        $invoice = $this->getRandomCustomerInvoice($paymentDate);
        $this->assertTrue($invoice->exists(), 'can-not-create-invoice');

        $receipts = $invoice->getReceipts();
        $this->assertCount(1, $receipts, 'bad-invoice-receipts-count');

        $receipts[0]->pagado = true;
        $receipts[0]->fechapago = $paymentDate;
        $this->assertTrue($receipts[0]->save(), 'can-not-set-paid-receipt');

        $payments = $receipts[0]->getPayments();
        $this->assertCount(1, $payments, 'should-have-one-payment');

        // el pago en la fecha exacta de inicio de la regularización debe generar asiento contable
        $this->assertNotEmpty(
            $payments[0]->idasiento,
            'payment-on-regularization-start-date-should-have-accounting-entry'
        );

        $this->assertTrue($reg->delete(), 'can-not-delete-regularization');
        $subject = $invoice->getSubject();
        $this->assertTrue($invoice->delete(), 'can-not-delete-invoice');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-subject');
    }

    public function testCustomerPaymentWithoutRegularizationCreatesAccountingEntry(): void
    {
        // sin regularizaciones activas, el pago de un cliente debe generar asiento contable
        $invoice = $this->getRandomCustomerInvoice();
        $this->assertTrue($invoice->exists(), 'can-not-create-invoice');

        $receipts = $invoice->getReceipts();
        $this->assertCount(1, $receipts, 'bad-invoice-receipts-count');
        $this->assertFalse($receipts[0]->pagado, 'receipt-should-be-unpaid');

        $receipts[0]->pagado = true;
        $this->assertTrue($receipts[0]->save(), 'can-not-set-paid-receipt');

        $payments = $receipts[0]->getPayments();
        $this->assertCount(1, $payments, 'should-have-one-payment');
        $this->assertNotEmpty($payments[0]->idasiento, 'payment-should-have-accounting-entry');

        $subject = $invoice->getSubject();
        $this->assertTrue($invoice->delete(), 'can-not-delete-invoice');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-subject');
    }

    public function testSupplierPaymentInsideLockedRegularizationCreatesAccountingEntry(): void
    {
        // el bug también afecta a los pagos de proveedores
        $reg = new RegularizacionImpuesto();
        $reg->periodo = 'T1';
        $reg->bloquear = true;
        $this->assertTrue($reg->save(), 'can-not-save-regularization');

        $year = date('Y', strtotime($reg->fechainicio));
        $paymentDate = '15-03-' . $year;

        $invoice = $this->getRandomSupplierInvoice($paymentDate);
        $this->assertTrue($invoice->exists(), 'can-not-create-supplier-invoice');

        $receipts = $invoice->getReceipts();
        $this->assertCount(1, $receipts, 'bad-invoice-receipts-count');

        $receipts[0]->pagado = true;
        $receipts[0]->fechapago = $paymentDate;
        $this->assertTrue($receipts[0]->save(), 'can-not-set-paid-receipt');

        $payments = $receipts[0]->getPayments();
        $this->assertCount(1, $payments, 'should-have-one-payment');
        $this->assertEquals($paymentDate, $payments[0]->fecha, 'bad-payment-date');

        // BUG: el asiento contable del pago de proveedor también debe generarse
        // aunque la fecha caiga dentro de una regularización de IVA bloqueada
        $this->assertNotEmpty(
            $payments[0]->idasiento,
            'supplier-payment-should-have-accounting-entry-inside-locked-regularization'
        );

        $this->assertTrue($reg->delete(), 'can-not-delete-regularization');
        $subject = $invoice->getSubject();
        $this->assertTrue($invoice->delete(), 'can-not-delete-invoice');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-subject');
    }

    protected function setUp(): void
    {
        self::removeTaxRegularization();
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
