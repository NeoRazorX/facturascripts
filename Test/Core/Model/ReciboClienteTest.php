<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Lib\ReceiptGenerator;
use FacturaScripts\Core\Model\Base\ModelCore;
use FacturaScripts\Core\Model\FacturaCliente;
use FacturaScripts\Core\Model\FormaPago;
use FacturaScripts\Core\Model\ReciboCliente;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class ReciboClienteTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
    }

    public function testCreateInvoiceCreateReceipt()
    {
        // creamos una factura
        $invoice = $this->getRandomCustomerInvoice();
        $this->assertTrue($invoice->exists(), 'can-not-create-random-invoice');

        // comprobamos que existe un recibo para esta factura
        $receipts = $invoice->getReceipts();
        $this->assertCount(1, $receipts, 'bad-invoice-receipts-count');

        // obtenemos el subject de la factura
        $subject = $invoice->getSubject();

        // eliminamos la factura
        $this->assertTrue($invoice->delete(), 'can-not-delete-invoice');

        // comprobamos que el recibo se ha eliminado
        foreach ($receipts as $receipt) {
            $this->assertFalse($receipt->exists());
        }

        // eliminamos el subject
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-subject');
    }

    public function testCreateInvoiceOnPastDate()
    {
        // creamos una factura de ayer
        $yesterday = date(ModelCore::DATE_STYLE, strtotime('-1 day'));
        $invoice = $this->getRandomCustomerInvoice($yesterday);
        $this->assertTrue($invoice->exists(), 'can-not-create-random-invoice');

        // comprobamos que existe un recibo de ayer para esta factura
        $receipts = $invoice->getReceipts();
        $this->assertCount(1, $receipts, 'bad-invoice-receipts-count');
        $this->assertEquals($yesterday, $receipts[0]->fecha);

        // obtenemos el subject de la factura
        $subject = $invoice->getSubject();

        // eliminamos la factura
        $this->assertTrue($invoice->delete(), 'can-not-delete-invoice');

        // eliminamos el subject
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-subject');
    }

    public function testCreatePaidInvoiceOnPastDate()
    {
        // creamos una forma de pago pagada
        $payMethod = new FormaPago();
        $payMethod->descripcion = 'test';
        $payMethod->plazovencimiento = 0;
        $payMethod->tipovencimiento = 'days';
        $payMethod->pagado = true;
        $this->assertTrue($payMethod->save(), 'cant-save-forma-pago');

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // creamos una factura de ayer
        $yesterday = date(ModelCore::DATE_STYLE, strtotime('-1 day'));
        $invoice = new FacturaCliente();
        $invoice->setSubject($customer);
        $invoice->setDate($yesterday, $invoice->hora);
        $invoice->codpago = $payMethod->codpago;
        $this->assertTrue($invoice->save(), 'can-not-create-invoice');

        // añadimos una línea a la factura
        $newLine = $invoice->getNewLine();
        $newLine->cantidad = 1;
        $newLine->descripcion = 'test';
        $newLine->pvpunitario = 100;
        $this->assertTrue($newLine->save(), 'cant-add-invoice-line');

        // recalculamos
        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true), 'cant-update-invoice');

        // comprobamos que la factura está pagada
        $this->assertTrue($invoice->pagada, 'invoice-unpaid');

        // comprobamos que existe un recibo pagado de ayer para esta factura
        $receipts = $invoice->getReceipts();
        $this->assertCount(1, $receipts, 'bad-invoice-receipts-count');
        $this->assertTrue($receipts[0]->pagado, 'unpaid-receipt');
        $this->assertEquals($yesterday, $receipts[0]->fecha, 'bad-receipt-date');
        $this->assertEquals($yesterday, $receipts[0]->vencimiento, 'bad-receipt-expiration');
        $this->assertEquals($yesterday, $receipts[0]->fechapago, 'bad-receipt-payment-date');

        // eliminamos
        $this->assertTrue($invoice->delete(), 'can-not-delete-invoice');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($customer->delete(), 'can-not-delete-customer');
        $this->assertTrue($payMethod->delete(), 'can-not-delete-forma-pago');
    }

    public function testCustomerPaymentDays()
    {
        // creamos una forma de pago con vencimiento de 0 días
        $payMethod = new FormaPago();
        $payMethod->descripcion = 'test';
        $payMethod->plazovencimiento = 0;
        $payMethod->tipovencimiento = 'days';
        $payMethod->pagado = false;
        $this->assertTrue($payMethod->save(), 'cant-save-forma-pago');

        // creamos un cliente con días de pago 1 y 15
        $customer = $this->getRandomCustomer();
        $customer->diaspago = '1,15';
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // creamos una factura con fecha del día 10 de marzo
        $invoice = new FacturaCliente();
        $invoice->setSubject($customer);
        $date = date('10-03-Y');
        $invoice->setDate($date, $invoice->hora);
        $invoice->codpago = $payMethod->codpago;
        $this->assertTrue($invoice->save(), 'can-not-create-invoice');

        // añadimos una línea a la factura
        $newLine = $invoice->getNewLine();
        $newLine->cantidad = 1;
        $newLine->descripcion = 'test';
        $newLine->pvpunitario = 100;
        $this->assertTrue($newLine->save(), 'cant-add-invoice-line');

        // recalculamos
        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true), 'cant-update-invoice');

        // comprobamos que el recibo tiene vencimiento el día 15 de marzo
        $receipts = $invoice->getReceipts();
        $this->assertCount(1, $receipts, 'bad-invoice-receipts-count');
        $vencimiento10 = date('15-03-Y');
        $this->assertEquals($vencimiento10, $receipts[0]->vencimiento, 'bad-receipt-expiration');

        // eliminamos el recibo
        $this->assertTrue($receipts[0]->delete(), 'can-not-delete-receipt');

        // ahora cambiamos los días de pago a 1 y 7
        $customer->diaspago = '1,7';
        $this->assertTrue($customer->save(), 'cant-update-customer');

        // generados el recibo de nuevo
        $generator = new ReceiptGenerator();
        $generator->generate($invoice);

        // comprobamos que el recibo tiene vencimiento el día 1 de abril
        $receipts = $invoice->getReceipts();
        $this->assertCount(1, $receipts, 'bad-invoice-receipts-count');
        $vencimiento20 = date('01-04-Y');
        $this->assertEquals($vencimiento20, $receipts[0]->vencimiento, 'bad-receipt-expiration');

        // eliminamos
        $this->assertTrue($invoice->delete(), 'can-not-delete-invoice');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($customer->delete(), 'can-not-delete-customer');
        $this->assertTrue($payMethod->delete(), 'can-not-delete-forma-pago');
    }

    public function testReceiptsTotalGreaterThanInvoice()
    {
        // creamos una factura
        $invoice = $this->getRandomCustomerInvoice();
        $this->assertTrue($invoice->exists(), 'can-not-create-random-invoice');

        // aumentamos el total y marcamos como pagados
        foreach ($invoice->getReceipts() as $receipt) {
            $receipt->importe += 10;
            $receipt->pagado = true;
            $this->assertTrue($receipt->save(), 'can-not-set-paid-receipt');
        }

        // comprobamos que la factura está pagada
        $invoice->loadFromCode($invoice->primaryColumnValue());
        $this->assertTrue($invoice->pagada, 'invoice-unpaid');

        // obtenemos el subject
        $subject = $invoice->getSubject();

        // eliminamos la factura
        $this->assertTrue($invoice->delete(), 'can-not-delete-invoice');

        // eliminamos el subject
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-subject');
    }

    public function testUpdateAndCreateReceipts()
    {
        // creamos una factura
        $invoice = $this->getRandomCustomerInvoice();
        $this->assertTrue($invoice->exists(), 'can-not-create-random-invoice');

        // modificamos el recibo existente para reducir el importe a 1, y nos guardamos el resto
        $resto = 0;
        foreach ($invoice->getReceipts() as $receipt) {
            $resto = $receipt->importe - 1;

            $receipt->importe = 1;
            $this->assertTrue($receipt->save(), 'can-not-update-receipt-1');
            break;
        }

        // creamos un nuevo recibo
        $newReceipt = new ReciboCliente();
        $newReceipt->codcliente = $invoice->codcliente;
        $newReceipt->coddivisa = $invoice->coddivisa;
        $newReceipt->idempresa = $invoice->idempresa;
        $newReceipt->idfactura = $invoice->idfactura;
        $newReceipt->importe = $resto;
        $newReceipt->setPaymentMethod($invoice->codpago);
        $this->assertTrue($newReceipt->save(), 'can-not-create-receipt-1');

        // obtenemos el subject
        $subject = $invoice->getSubject();

        // eliminamos la factura
        $this->assertTrue($invoice->delete(), 'can-not-delete-invoice');

        // eliminamos el subject
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-subject');
    }

    public function testUpdateReceiptsUpdateInvoice()
    {
        // creamos una factura
        $invoice = $this->getRandomCustomerInvoice();
        $this->assertTrue($invoice->exists(), 'can-not-create-random-invoice');

        // eliminamos los recibos
        foreach ($invoice->getReceipts() as $receipt) {
            $this->assertTrue($receipt->delete(), 'can-not-delete-receipt');
        }

        // creamos 3 recibos
        $generator = new ReceiptGenerator();
        $this->assertTrue($generator->generate($invoice, 3), 'can-not-create-new-receipts');
        $this->assertCount(3, $invoice->getReceipts(), 'bad-invoice-receipts-count');

        // marcamos todos como pagados
        foreach ($invoice->getReceipts() as $receipt) {
            $receipt->pagado = true;
            $this->assertTrue($receipt->save(), 'can-not-set-paid-receipt');
        }

        // comprobamos que la factura está pagada
        $invoice->loadFromCode($invoice->primaryColumnValue());
        $this->assertTrue($invoice->pagada, 'invoice-unpaid');

        // marcamos un recibo como impagado
        foreach ($invoice->getReceipts() as $receipt) {
            $receipt->pagado = false;
            $this->assertTrue($receipt->save(), 'can-not-set-unpaid-receipt');
            break;
        }

        // comprobamos que la factura está impagada
        $invoice->loadFromCode($invoice->primaryColumnValue());
        $this->assertFalse($invoice->pagada, 'invoice-paid');

        // marcamos todos como pagados
        foreach ($invoice->getReceipts() as $receipt) {
            $receipt->pagado = true;
            $this->assertTrue($receipt->save(), 'can-not-set-paid-receipt');
        }

        // comprobamos que la factura está pagada
        $invoice->loadFromCode($invoice->primaryColumnValue());
        $this->assertTrue($invoice->pagada, 'invoice-unpaid');

        // eliminamos un recibo
        foreach ($invoice->getReceipts() as $receipt) {
            $this->assertTrue($receipt->delete(), 'can-not-delete-receipt');
            break;
        }

        // comprobamos que la factura está impagada
        $invoice->loadFromCode($invoice->primaryColumnValue());
        $this->assertFalse($invoice->pagada, 'invoice-paid');

        // obtenemos el subject
        $subject = $invoice->getSubject();

        // eliminamos la factura
        $this->assertTrue($invoice->delete(), 'can-not-delete-invoice');

        // eliminamos el subject
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-subject');
    }

    public function testUpdateInvoiceWithPaidReceipt()
    {
        // creamos una factura
        $invoice = $this->getRandomCustomerInvoice();
        $this->assertTrue($invoice->exists(), 'can-not-create-random-invoice');

        // marcamos el recibo como pagado
        $receipt = $invoice->getReceipts()[0];
        $receipt->pagado = true;
        $this->assertTrue($receipt->save(), 'can-not-set-paid-receipt');

        // comprobamos que la factura está pagada
        $invoice->loadFromCode($invoice->primaryColumnValue());
        $this->assertTrue($invoice->pagada, 'invoice-unpaid');

        // añadimos una línea con precio 0
        $newLine = $invoice->getNewLine();
        $newLine->cantidad = 1;
        $newLine->descripcion = 'test';
        $newLine->pvpunitario = 0;
        $this->assertTrue($newLine->save(), 'can-not-create-new-line');

        // forzamos a que se recalcule la factura
        $invoice->fecha = date('d-m-Y', strtotime('+1 day'));
        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true), 'can-not-calculate-invoice');

        // comprobamos que la factura sigue pagada
        $invoice->loadFromCode($invoice->primaryColumnValue());
        $this->assertTrue($invoice->pagada, 'invoice-unpaid');

        // comprobamos que solamente hay un recibo
        $this->assertCount(1, $invoice->getReceipts(), 'bad-invoice-receipts-count');

        // obtenemos el subject
        $subject = $invoice->getSubject();

        // eliminamos la factura
        $this->assertTrue($invoice->delete(), 'can-not-delete-invoice');

        // eliminamos el subject
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-subject');
    }

    public function testPayReceiptUpdatesCustomerRisk()
    {
        // creamos una factura
        $invoice = $this->getRandomCustomerInvoice();
        $this->assertTrue($invoice->exists(), 'can-not-create-random-invoice');

        // eliminamos sus recibos
        foreach ($invoice->getReceipts() as $receipt) {
            $this->assertTrue($receipt->delete(), 'can-not-delete-receipt');
        }

        // creamos dos recibos
        $generator = new ReceiptGenerator();
        $this->assertTrue($generator->generate($invoice, 2), 'can-not-create-new-receipts');

        // marcamos los recibos como impagados
        foreach ($invoice->getReceipts() as $receipt) {
            $receipt->pagado = false;
            $this->assertTrue($receipt->save(), 'can-not-set-unpaid-receipt');
        }

        // comprobamos que el cliente tiene un riesgo igual al total de la factura
        $customer = $invoice->getSubject();
        $this->assertEquals($invoice->total, $customer->riesgoalcanzado, 'bad-customer-risk');

        // nos guardamos el riesgo antes de pagar el recibo
        $risk = $customer->riesgoalcanzado;

        // marcamos un recibo como pagado
        foreach ($invoice->getReceipts() as $receipt) {
            $receipt->pagado = true;
            $this->assertTrue($receipt->save(), 'can-not-set-paid-receipt');
            break;
        }

        // comprobamos que el riesgo es menor que el anterior
        $customer->loadFromCode($customer->primaryColumnValue());
        $this->assertLessThan($risk, $customer->riesgoalcanzado, 'bad-customer-risk');

        // obtenemos el subject
        $subject = $invoice->getSubject();

        // eliminamos la factura
        $this->assertTrue($invoice->delete(), 'can-not-delete-invoice');

        // eliminamos el subject
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-subject');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
