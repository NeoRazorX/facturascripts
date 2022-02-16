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

use FacturaScripts\Core\Lib\ReceiptGenerator;
use FacturaScripts\Core\Model\ReciboCliente;
use FacturaScripts\Test\Core\DefaultSettingsTrait;
use FacturaScripts\Test\Core\LogErrorsTrait;
use FacturaScripts\Test\Core\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class ReciboClienteTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass()
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

        // eliminamos la factura
        $this->assertTrue($invoice->delete(), 'can-not-delete-invoice');

        // comprobamos que el recibo se ha eliminado
        foreach ($receipts as $receipt) {
            $this->assertFalse($receipt->exists());
        }
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

        // eliminamos la factura
        $this->assertTrue($invoice->delete(), 'can-not-delete-invoice');
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

        // eliminamos la factura
        $this->assertTrue($invoice->delete(), 'can-not-delete-invoice');
    }

    protected function tearDown()
    {
        $this->logErrors();
    }
}
