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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\BusinessDocumentTools;
use FacturaScripts\Core\Model\FacturaCliente;
use FacturaScripts\Core\Model\Stock;
use FacturaScripts\Test\Core\BusinessDocsTrait;
use FacturaScripts\Test\Core\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

class FacturaClienteTest extends TestCase
{
    use LogErrorsTrait;
    use BusinessDocsTrait;

    const CUSTOMER_CIF = '556677';
    const CUSTOMER_NAME = 'Alberto';
    const INVOICE_NOTES = 'Test, 2, 3.';
    const INVOICE_REF = 'J567-987';
    const PRODUCT1_PRICE = 66.1;
    const PRODUCT1_QUANTITY = 3;
    const PRODUCT1_REF = 'X7878';

    public function testCanCreateInvoice()
    {
        $customer = self::getCustomer(self::CUSTOMER_NAME, self::CUSTOMER_CIF);
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // create invoice
        $invoice = new FacturaCliente();
        $invoice->setSubject($customer);
        $invoice->numero2 = self::INVOICE_REF;
        $invoice->observaciones = self::INVOICE_NOTES;
        $this->assertTrue($invoice->save(), 'cant-create-invoice');
        $this->assertTrue($invoice->exists(), 'invoice-not-exists');

        // add line
        $firstLine = $invoice->getNewLine();
        $firstLine->cantidad = self::PRODUCT1_QUANTITY;
        $firstLine->descripcion = 'Test';
        $firstLine->pvpunitario = self::PRODUCT1_PRICE;
        $this->assertTrue($firstLine->save(), 'cant-save-first-line');
        $this->assertTrue($firstLine->exists(), 'first-invoice-line-does-not-exists');

        // recalculate
        $tool = new BusinessDocumentTools();
        $tool->recalculate($invoice);
        $neto = round(self::PRODUCT1_PRICE * self::PRODUCT1_QUANTITY, 2);
        $this->assertEquals($neto, $invoice->neto, 'bad-invoice-neto');
        $this->assertTrue($invoice->save(), 'cant-update-invoice');

        // find invoice
        $dbInvoice = $invoice->get($invoice->idfactura);
        $this->assertIsObject($dbInvoice, 'invoice-cant-be-read');
        $this->assertEquals(self::CUSTOMER_CIF, $dbInvoice->cifnif, 'bad-invoice-cifnif');
        $this->assertEquals($invoice->codigo, $dbInvoice->codigo, 'bad-invoice-codigo');
        $this->assertEquals($invoice->neto, $dbInvoice->neto, 'bad-invoice-neto');
        $this->assertEquals(self::CUSTOMER_NAME, $dbInvoice->nombrecliente, 'bad-invoice-nombre');
        $this->assertEquals($invoice->numero, $dbInvoice->numero, 'bad-invoice-numero');
        $this->assertEquals(self::INVOICE_REF, $dbInvoice->numero2, 'bad-invoice-numero2');
        $this->assertEquals(self::INVOICE_NOTES, $dbInvoice->observaciones, 'bad-invoice-notes');
        $this->assertEquals($invoice->total, $dbInvoice->total, 'bad-invoice-total');

        // delete
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertFalse($dbInvoice->exists(), 'invoice-still-found');
        $this->assertFalse($firstLine->exists(), 'invoice-line-not-deleted');
    }

    public function testCanNotCreateInvoiceWithoutCustomer()
    {
        $invoice = new FacturaCliente();
        $this->assertFalse($invoice->save(), 'can-create-invoice-without-customer');
    }

    public function testInvoiceLineUpdateStock()
    {
        $product = $this->getProduct(self::PRODUCT1_REF);
        $product->precio = self::PRODUCT1_PRICE;
        $this->assertTrue($product->save(), 'cant-create-product');
        $this->assertEquals(0, $product->stockfis, 'product-bad-stock');

        // update stock
        $stock = new Stock();
        $stock->cantidad = self::PRODUCT1_QUANTITY;
        $stock->codalmacen = AppSettings::get('default', 'codalmacen');
        $stock->idproducto = $product->idproducto;
        $stock->referencia = $product->referencia;
        $this->assertTrue($stock->save(), 'cant-create-stock');

        // reload product
        $product->loadFromCode($product->idproducto);
        $this->assertEquals(self::PRODUCT1_QUANTITY, $product->stockfis, 'product-stock-not-updated');

        // create invoice
        $invoice = new FacturaCliente();
        $customer = self::getCustomer(self::CUSTOMER_NAME, self::CUSTOMER_CIF);
        $invoice->setSubject($customer);
        $this->assertTrue($invoice->save(), 'cant-create-invoice');

        // add product line
        $firstLine = $invoice->getNewProductLine(self::PRODUCT1_REF);
        $firstLine->cantidad = self::PRODUCT1_QUANTITY;
        $firstLine->pvpunitario = self::PRODUCT1_PRICE;
        $this->assertEquals(self::PRODUCT1_REF, $firstLine->referencia, 'bad-first-line-reference');
        $this->assertEquals(self::PRODUCT1_REF, $firstLine->descripcion, 'bad-first-line-description');
        $this->assertTrue($firstLine->save(), 'cant-save-first-line');

        // recalculate
        $tool = new BusinessDocumentTools();
        $tool->recalculate($invoice);
        $this->assertTrue($invoice->save(), 'cant-update-invoice');

        // reload product
        $product->loadFromCode($product->idproducto);
        $this->assertEquals(0, $product->stockfis, 'bad-product1-stock');

        // delete
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertFalse($firstLine->exists(), 'deleted-line-invoice-still-found');

        // reload product again
        $product->loadFromCode($product->idproducto);
        $this->assertEquals(self::PRODUCT1_QUANTITY, $product->stockfis, 'bad-product1-stock-end');
    }

    public function testCreateInvoiceCreatesAccountingEntry()
    {
        // create invoice
        $invoice = new FacturaCliente();
        $customer = self::getCustomer(self::CUSTOMER_NAME, self::CUSTOMER_CIF);
        $invoice->setSubject($customer);
        $this->assertTrue($invoice->save(), 'cant-create-invoice');

        // add line
        $firstLine = $invoice->getNewLine();
        $firstLine->cantidad = 1;
        $firstLine->pvpunitario = self::PRODUCT1_PRICE;
        $this->assertTrue($firstLine->save(), 'cant-save-first-line');

        // recalculate
        $tool = new BusinessDocumentTools();
        $tool->recalculate($invoice);
        $netosindto = $invoice->netosindto;
        $neto = $invoice->neto;
        $total = $invoice->total;
        $this->assertTrue($invoice->save(), 'cant-update-invoice');

        // check accounting entry
        $entry = $invoice->getAccountingEntry();
        $this->assertTrue($entry->exists(), 'accounting-entry-not-found');
        $this->assertEquals($invoice->total, $entry->importe, 'accounting-entry-bad-importe');

        // update discount to update invoice total
        $invoice->dtopor1 = 50;
        $tool->recalculate($invoice);
        $this->assertEquals($netosindto, $invoice->netosindto, 'bad-netosindto');
        $this->assertLessThan($neto, $invoice->neto, 'bad-neto');
        $this->assertLessThan($total, $invoice->total, 'bad-total');
        $this->assertTrue($invoice->save(), 'cant-update-invoice-discount');

        // check updated accounting entry
        $updEntry = $invoice->getAccountingEntry();
        $this->assertTrue($updEntry->exists(), 'updated-accounting-entry-not-found');
        $this->assertEquals($invoice->idasiento, $updEntry->idasiento, 'accounting-entry-not-updated');
        $this->assertEquals($invoice->total, $updEntry->importe, 'updated-accounting-entry-bad-importe');

        // delete invoice
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertFalse($updEntry->exists(), 'deleted-accounting-entry-still-found');
    }

    public function cantUpdateOrDeleteNonEditableInvoice()
    {
        // create invoice
        $invoice = new FacturaCliente();
        $customer = self::getCustomer(self::CUSTOMER_NAME, self::CUSTOMER_CIF);
        $invoice->setSubject($customer);
        $this->assertTrue($invoice->save(), 'cant-create-invoice');

        // add line
        $firstLine = $invoice->getNewLine();
        $firstLine->cantidad = 1;
        $firstLine->pvpunitario = self::PRODUCT1_PRICE;
        $this->assertTrue($firstLine->save(), 'cant-save-first-line');

        // recalculate
        $tool = new BusinessDocumentTools();
        $tool->recalculate($invoice);
        $this->assertTrue($invoice->save(), 'cant-update-invoice');

        // change status
        $changed = false;
        foreach ($invoice->getAvaliableStatus() as $status) {
            if (false === $status->editable) {
                $invoice->idestado = $status->idestado;
                $changed = true;
            }
        }
        $this->assertTrue($changed, 'non-editable-status-not-found');
        $this->assertTrue($invoice->save(), 'cant-update-invoice');

        // update discount to update invoice total
        $invoice->dtopor1 = 50;
        $tool->recalculate($invoice);
        $this->assertFalse($invoice->save(), 'can-update-non-editable-invoice');
        $this->assertFalse($invoice->delete(), 'can-delete-non-editable-invoice');
    }

    public static function setUpBeforeClass()
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
    }

    protected function tearDown()
    {
        $this->logErrors();
    }

    public static function tearDownAfterClass()
    {
        // delete items
        $facturaModel = new FacturaCliente();
        $customer = self::getCustomer(self::CUSTOMER_NAME, self::CUSTOMER_NAME);
        $where = [new DataBaseWhere('codcliente', $customer->codcliente)];
        foreach ($facturaModel->all($where) as $invoice) {
            $invoice->delete();
        }

        $customer->delete();
        self::getProduct(self::PRODUCT1_REF)->delete();
    }
}