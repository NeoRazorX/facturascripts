<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2022-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Model\FacturaProveedor;
use FacturaScripts\Core\Model\FormaPago;
use FacturaScripts\Core\Model\ReciboProveedor;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

class ReciboProveedorTest extends TestCase
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
        $invoice = $this->getRandomSupplierInvoice();
        $this->assertTrue($invoice->exists(), 'can-not-create-random-invoice');

        // comprobamos que existe un recibo para esta factura
        $receipts = $invoice->getReceipts();
        $this->assertCount(1, $receipts, 'bad-invoice-receipts-count');

        // obtenemos el subject
        $subject = $invoice->getSubject();

        // eliminamos la factura
        $this->assertTrue($invoice->delete(), 'can-not-delete-invoice');

        // eliminamos el subject
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-subject');

        // comprobamos que el recibo se ha eliminado
        foreach ($receipts as $receipt) {
            $this->assertFalse($receipt->exists());
        }
    }

    public function testCreateInvoiceOnPastDate()
    {
        // creamos una factura de ayer
        $yesterday = date(ModelCore::DATE_STYLE, strtotime('-1 day'));
        $invoice = $this->getRandomSupplierInvoice($yesterday);
        $this->assertTrue($invoice->exists(), 'can-not-create-random-invoice');

        // comprobamos que existe un recibo para esta factura
        $receipts = $invoice->getReceipts();
        $this->assertCount(1, $receipts, 'bad-invoice-receipts-count');
        $this->assertEquals($yesterday, $receipts[0]->fecha);

        // obtenemos el subject
        $subject = $invoice->getSubject();

        // eliminamos la factura
        $this->assertTrue($invoice->delete(), 'can-not-delete-invoice');

        // eliminamos el subject
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-subject');

        // comprobamos que el recibo se ha eliminado
        foreach ($receipts as $receipt) {
            $this->assertFalse($receipt->exists());
        }
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

        // creamos un proveedor
        $supplier = $this->getRandomSupplier();
        $this->assertTrue($supplier->save(), 'cant-create-supplier');

        // creamos una factura de ayer
        $yesterday = date(ModelCore::DATE_STYLE, strtotime('-1 day'));
        $invoice = new FacturaProveedor();
        $invoice->setSubject($supplier);
        $invoice->setDate($yesterday, $invoice->hora);
        $invoice->codpago = $payMethod->codpago;
        $this->assertTrue($invoice->save(), 'can-not-create-invoice');

        // añadimos una línea a la factura
        $newLine = $invoice->getNewLine();
        $newLine->cantidad = 1;
        $newLine->descripcion = 'test';
        $newLine->pvpunitario = 150;
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
        $this->assertTrue($supplier->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($supplier->delete(), 'can-not-delete-supplier');
        $this->assertTrue($payMethod->delete(), 'can-not-delete-forma-pago');
    }

    public function testCreatePaidInvoiceOnPastDateWithTimeLimit()
    {
        // creamos una forma de pago pagada con vencimiento a 10 días
        $payMethod = new FormaPago();
        $payMethod->descripcion = 'test';
        $payMethod->plazovencimiento = 10;
        $payMethod->tipovencimiento = 'days';
        $payMethod->pagado = true;
        $this->assertTrue($payMethod->save(), 'cant-save-forma-pago');

        // creamos un proveedor
        $supplier = $this->getRandomSupplier();
        $this->assertTrue($supplier->save(), 'cant-create-supplier');

        // creamos una factura el día 1 de este mes
        $date = date('01-m-Y');
        $invoice = new FacturaProveedor();
        $invoice->setSubject($supplier);
        $invoice->setDate($date, $invoice->hora);
        $invoice->codpago = $payMethod->codpago;
        $this->assertTrue($invoice->save(), 'can-not-create-invoice');

        // añadimos una línea a la factura
        $newLine = $invoice->getNewLine();
        $newLine->cantidad = 1;
        $newLine->descripcion = 'test';
        $newLine->pvpunitario = 150;
        $this->assertTrue($newLine->save(), 'cant-add-invoice-line');

        // recalculamos
        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true), 'cant-update-invoice');

        // comprobamos que la factura está pagada
        $this->assertTrue($invoice->pagada, 'invoice-unpaid');

        // comprobamos que existe un recibo pagado para esta factura
        $receipts = $invoice->getReceipts();
        $this->assertCount(1, $receipts, 'bad-invoice-receipts-count');
        $this->assertTrue($receipts[0]->pagado, 'unpaid-receipt');
        $this->assertEquals($date, $receipts[0]->fecha, 'bad-receipt-date');
        $vencimiento = date('11-m-Y'); // 10 días después del día 1
        $this->assertEquals($vencimiento, $receipts[0]->vencimiento, 'bad-receipt-expiration');
        $this->assertEquals($date, $receipts[0]->fechapago, 'bad-receipt-payment-date');

        // eliminamos
        $this->assertTrue($invoice->delete(), 'can-not-delete-invoice');
        $this->assertTrue($supplier->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($supplier->delete(), 'can-not-delete-supplier');
        $this->assertTrue($payMethod->delete(), 'can-not-delete-forma-pago');
    }

    public function testReceiptsTotalGreaterThanInvoice()
    {
        // creamos una factura
        $invoice = $this->getRandomSupplierInvoice();
        $this->assertTrue($invoice->exists(), 'can-not-create-random-invoice');

        // aumentamos el total y marcamos como cobrados
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
        $invoice = $this->getRandomSupplierInvoice();
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
        $newReceipt = new ReciboProveedor();
        $newReceipt->coddivisa = $invoice->coddivisa;
        $newReceipt->codproveedor = $invoice->codproveedor;
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
        $invoice = $this->getRandomSupplierInvoice();
        $this->assertTrue($invoice->exists(), 'can-not-create-random-invoice');

        // eliminamos los recibos
        foreach ($invoice->getReceipts() as $receipt) {
            $this->assertTrue($receipt->delete(), 'can-not-delete-receipt');
        }

        // creamos 2 recibos
        $generator = new ReceiptGenerator();
        $this->assertTrue($generator->generate($invoice, 2), 'can-not-create-new-receipts');
        $this->assertCount(2, $invoice->getReceipts(), 'bad-invoice-receipts-count');

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
        $invoice = $this->getRandomSupplierInvoice();
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
        $this->assertTrue(Calculator::calculate($invoice, $lines, true), 'can-not-recalculate-invoice');

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

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
