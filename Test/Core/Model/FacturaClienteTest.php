<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\BusinessDocumentTools;
use FacturaScripts\Core\Model\FacturaCliente;
use FacturaScripts\Core\Model\Stock;
use FacturaScripts\Test\Core\DefaultSettingsTrait;
use FacturaScripts\Test\Core\LogErrorsTrait;
use FacturaScripts\Test\Core\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class FacturaClienteTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    const INVOICE_NOTES = 'Test, 2, 3.';
    const INVOICE_REF = 'J567-987';
    const PRODUCT1_PRICE = 66.1;
    const PRODUCT1_QUANTITY = 3;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
    }

    public function testCanCreateInvoice()
    {
        // creamos el cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // creamos la factura
        $invoice = new FacturaCliente();
        $invoice->setSubject($customer);
        $invoice->numero2 = self::INVOICE_REF;
        $invoice->observaciones = self::INVOICE_NOTES;
        $this->assertTrue($invoice->save(), 'cant-create-invoice');
        $this->assertTrue($invoice->exists(), 'invoice-not-exists');

        // añadimos una línea
        $firstLine = $invoice->getNewLine();
        $firstLine->cantidad = self::PRODUCT1_QUANTITY;
        $firstLine->descripcion = 'Test';
        $firstLine->pvpunitario = self::PRODUCT1_PRICE;
        $this->assertTrue($firstLine->save(), 'cant-save-first-line');
        $this->assertTrue($firstLine->exists(), 'first-invoice-line-does-not-exists');

        // recalculamos
        $tool = new BusinessDocumentTools();
        $tool->recalculate($invoice);
        $neto = round(self::PRODUCT1_PRICE * self::PRODUCT1_QUANTITY, 2);
        $this->assertEquals($neto, $invoice->neto, 'bad-invoice-neto');
        $this->assertTrue($invoice->save(), 'cant-update-invoice');

        // buscamos la factura
        $dbInvoice = $invoice->get($invoice->idfactura);
        $this->assertIsObject($dbInvoice, 'invoice-cant-be-read');
        $this->assertEquals($customer->cifnif, $dbInvoice->cifnif, 'bad-invoice-cifnif');
        $this->assertEquals($invoice->codigo, $dbInvoice->codigo, 'bad-invoice-codigo');
        $this->assertEquals($neto, $dbInvoice->neto, 'bad-invoice-neto');
        $this->assertEquals($customer->razonsocial, $dbInvoice->nombrecliente, 'bad-invoice-nombre');
        $this->assertEquals($invoice->numero, $dbInvoice->numero, 'bad-invoice-numero');
        $this->assertEquals(self::INVOICE_REF, $dbInvoice->numero2, 'bad-invoice-numero2');
        $this->assertEquals(self::INVOICE_NOTES, $dbInvoice->observaciones, 'bad-invoice-notes');
        $this->assertEquals($invoice->total, $dbInvoice->total, 'bad-invoice-total');

        // comprobamos que se añade la línea al log de auditoría
        $found = $this->searchAuditLog($invoice->modelClassName(), $invoice->idfactura);
        $this->assertTrue($found, 'invoice-log-audit-cant-persist');

        // eliminamos
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertFalse($dbInvoice->exists(), 'invoice-still-found');
        $this->assertFalse($firstLine->exists(), 'invoice-line-not-deleted');
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
    }

    public function testCanNotCreateInvoiceWithoutCustomer()
    {
        $invoice = new FacturaCliente();
        $this->assertFalse($invoice->save(), 'can-create-invoice-without-customer');
    }

    public function testInvoiceLineUpdateStock()
    {
        // creamos el cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // creamos el producto
        $product = $this->getRandomProduct();
        $product->precio = self::PRODUCT1_PRICE;
        $this->assertTrue($product->save(), 'cant-create-product');

        // creamos el stock
        $stock = new Stock();
        $stock->cantidad = self::PRODUCT1_QUANTITY;
        $stock->idproducto = $product->idproducto;
        $stock->referencia = $product->referencia;
        $this->assertTrue($stock->save(), 'cant-create-stock');

        // creamos la factura
        $invoice = new FacturaCliente();
        $invoice->setSubject($customer);
        $this->assertTrue($invoice->save(), 'cant-create-invoice');

        // añadimos la línea con el producto
        $firstLine = $invoice->getNewProductLine($product->referencia);
        $firstLine->cantidad = self::PRODUCT1_QUANTITY;
        $this->assertEquals($product->referencia, $firstLine->referencia, 'bad-first-line-reference');
        $this->assertEquals($product->descripcion, $firstLine->descripcion, 'bad-first-line-description');
        $this->assertEquals($product->precio, $firstLine->pvpunitario, 'bad-first-line-pvpunitario');
        $this->assertTrue($firstLine->save(), 'cant-save-first-line');

        // recalculamos
        $tool = new BusinessDocumentTools();
        $tool->recalculate($invoice);
        $this->assertTrue($invoice->save(), 'cant-update-invoice');

        // comprobamos el stock del producto
        $product->loadFromCode($product->idproducto);
        $this->assertEquals(0, $product->stockfis, 'bad-product1-stock');

        // eliminamos
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertFalse($firstLine->exists(), 'deleted-line-invoice-still-found');
        $this->assertTrue($customer->delete(), 'cant-delete-customer');

        // comprobamos que se restaura el stock del producto
        $product->loadFromCode($product->idproducto);
        $this->assertEquals(self::PRODUCT1_QUANTITY, $product->stockfis, 'bad-product1-stock-end');
        $this->assertTrue($product->delete(), 'cant-delete-product');
    }

    public function testCreateInvoiceCreatesAccountingEntry()
    {
        // creamos el cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // creamos la factura
        $invoice = new FacturaCliente();
        $invoice->setSubject($customer);
        $this->assertTrue($invoice->save(), 'cant-create-invoice');

        // añadimos una línea
        $firstLine = $invoice->getNewLine();
        $firstLine->cantidad = 1;
        $firstLine->pvpunitario = self::PRODUCT1_PRICE;
        $this->assertTrue($firstLine->save(), 'cant-save-first-line');

        // recalculamos
        $tool = new BusinessDocumentTools();
        $tool->recalculate($invoice);
        $netosindto = $invoice->netosindto;
        $neto = $invoice->neto;
        $total = $invoice->total;
        $this->assertTrue($invoice->save(), 'cant-update-invoice');

        // comprobamos el asiento
        $entry = $invoice->getAccountingEntry();
        $this->assertTrue($entry->exists(), 'accounting-entry-not-found');
        $this->assertEquals($total, $entry->importe, 'accounting-entry-bad-importe');

        // aplicamos un descuento para modificar el total de la factura
        $invoice->dtopor1 = 50;
        $tool->recalculate($invoice);
        $this->assertEquals($netosindto, $invoice->netosindto, 'bad-netosindto');
        $this->assertLessThan($neto, $invoice->neto, 'bad-neto');
        $this->assertLessThan($total, $invoice->total, 'bad-total');
        $this->assertTrue($invoice->save(), 'cant-update-invoice-discount');

        // comprobamos que se ha actualizado el asiento
        $updEntry = $invoice->getAccountingEntry();
        $this->assertTrue($updEntry->exists(), 'updated-accounting-entry-not-found');
        $this->assertEquals($invoice->idasiento, $updEntry->idasiento, 'accounting-entry-not-updated');
        $this->assertEquals($invoice->total, $updEntry->importe, 'updated-accounting-entry-bad-importe');

        // eliminamos
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertFalse($updEntry->exists(), 'deleted-accounting-entry-still-found');
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
    }

    public function cantUpdateOrDeleteNonEditableInvoice()
    {
        // creamos el cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // creamos la factura
        $invoice = new FacturaCliente();
        $invoice->setSubject($customer);
        $this->assertTrue($invoice->save(), 'cant-create-invoice');

        // añadimos una línea
        $firstLine = $invoice->getNewLine();
        $firstLine->cantidad = 1;
        $firstLine->pvpunitario = self::PRODUCT1_PRICE;
        $this->assertTrue($firstLine->save(), 'cant-save-first-line');

        // recalculamos
        $tool = new BusinessDocumentTools();
        $tool->recalculate($invoice);
        $this->assertTrue($invoice->save(), 'cant-update-invoice');

        // cambiamos el estado a uno no editable
        $changed = false;
        foreach ($invoice->getAvaliableStatus() as $status) {
            if (false === $status->editable) {
                $invoice->idestado = $status->idestado;
                $changed = true;
            }
        }
        $this->assertTrue($changed, 'non-editable-status-not-found');
        $this->assertTrue($invoice->save(), 'cant-update-invoice');

        // cambiamos el descuento, recalculamos y guardamos
        $invoice->dtopor1 = 50;
        $tool->recalculate($invoice);
        $this->assertFalse($invoice->save(), 'can-update-non-editable-invoice');
        $this->assertFalse($invoice->delete(), 'can-delete-non-editable-invoice');

        // eliminamos
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
