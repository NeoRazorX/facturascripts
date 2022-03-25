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
use FacturaScripts\Core\Model\FacturaProveedor;
use FacturaScripts\Test\Core\DefaultSettingsTrait;
use FacturaScripts\Test\Core\LogErrorsTrait;
use FacturaScripts\Test\Core\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class FacturaProveedorTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    const INVOICE_NOTES = 'Test test test.';
    const INVOICE_REF = '7777777';
    const PRODUCT1_COST = 99.9;
    const PRODUCT1_QUANTITY = 10;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
    }

    public function testCreateNewInvoice()
    {
        // creamos el proveedor
        $supplier = $this->getRandomSupplier();
        $this->assertTrue($supplier->save(), 'cant-create-supplier');

        // creamos la factura
        $invoice = new FacturaProveedor();
        $invoice->setSubject($supplier);
        $invoice->numproveedor = self::INVOICE_REF;
        $invoice->observaciones = self::INVOICE_NOTES;
        $this->assertTrue($invoice->save(), 'cant-create-invoice');
        $this->assertTrue($invoice->exists(), 'invoice-does-not-exist');

        // añadimos una línea
        $firstLine = $invoice->getNewLine();
        $firstLine->cantidad = self::PRODUCT1_QUANTITY;
        $firstLine->descripcion = 'Test';
        $firstLine->pvpunitario = self::PRODUCT1_COST;
        $this->assertTrue($firstLine->save(), 'cant-save-first-line');
        $this->assertTrue($firstLine->exists(), 'first-invoice-line-does-not-exists');

        // recalculamos
        $tool = new BusinessDocumentTools();
        $tool->recalculate($invoice);
        $neto = round(self::PRODUCT1_QUANTITY * self::PRODUCT1_COST, 2);
        $this->assertEquals($neto, $invoice->neto, 'bad-invoice-neto');
        $this->assertTrue($invoice->save(), 'cant-update-invoice');

        // buscamos la factura
        $dbInvoice = $invoice->get($invoice->idfactura);
        $this->assertIsObject($dbInvoice, 'invoice-cant-be-read');
        $this->assertEquals($supplier->cifnif, $dbInvoice->cifnif, 'bad-invoice-cifnif');
        $this->assertEquals($invoice->codigo, $dbInvoice->codigo, 'bad-invoice-codigo');
        $this->assertEquals($neto, $dbInvoice->neto, 'bad-invoice-neto');
        $this->assertEquals($supplier->razonsocial, $dbInvoice->nombre, 'bad-invoice-nombre');
        $this->assertEquals($invoice->numero, $dbInvoice->numero, 'bad-invoice-numero');
        $this->assertEquals(self::INVOICE_REF, $dbInvoice->numproveedor, 'bad-invoice-numproveedor');
        $this->assertEquals(self::INVOICE_NOTES, $dbInvoice->observaciones, 'bad-invoice-notes');
        $this->assertEquals($invoice->total, $dbInvoice->total, 'bad-invoice-total');

        // eliminamos
        $this->assertTrue($dbInvoice->delete(), 'cant-delete-invoice');
        $this->assertFalse($dbInvoice->exists(), 'deleted-invoice-still-found');
        $this->assertFalse($invoice->exists(), 'deleted-invoice-still-found-2');
        $this->assertFalse($firstLine->exists(), 'deleted-line-invoice-still-found');
        $this->assertTrue($supplier->delete(), 'cant-delete-invoice');
    }

    public function testCanNotCreateInvoiceWithoutSupplier()
    {
        $invoice = new FacturaProveedor();
        $this->assertFalse($invoice->save(), 'can-create-invoice-without-supplier');
    }

    public function testInvoiceLineUpdateStock()
    {
        // creamos el proveedor
        $supplier = $this->getRandomSupplier();
        $this->assertTrue($supplier->save(), 'cant-create-supplier');

        // creamos el producto
        $product = $this->getRandomProduct();
        $this->assertTrue($product->save(), 'cant-create-product');

        // creamos la factura
        $invoice = new FacturaProveedor();
        $invoice->setSubject($supplier);
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
        $this->assertEquals(self::PRODUCT1_QUANTITY, $product->stockfis, 'bad-product1-stock');

        // eliminamos
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertFalse($firstLine->exists(), 'deleted-line-invoice-still-found');
        $this->assertTrue($supplier->delete(), 'cant-delete-supplier');

        // comprobamos que el stock del producto ha desaparecido
        $product->loadFromCode($product->idproducto);
        $this->assertEquals(0, $product->stockfis, 'bad-product1-stock-end');
        $this->assertTrue($product->delete(), 'cant-delete-producto');
    }

    public function testCreateInvoiceCreatesAccountingEntry()
    {
        // creamos el proveedor
        $supplier = $this->getRandomSupplier();
        $this->assertTrue($supplier->save(), 'cant-create-supplier');

        // creamos la factura
        $invoice = new FacturaProveedor();
        $invoice->setSubject($supplier);
        $this->assertTrue($invoice->save(), 'cant-create-invoice');

        // añadimos una línea
        $firstLine = $invoice->getNewLine();
        $firstLine->cantidad = 1;
        $firstLine->pvpunitario = self::PRODUCT1_COST;
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

        // cambiamos el descuento para que cambie el total (el asiento debe cambiar)
        $invoice->dtopor1 = 50;
        $tool->recalculate($invoice);
        $this->assertEquals($netosindto, $invoice->netosindto, 'bad-netosindto');
        $this->assertLessThan($neto, $invoice->neto, 'bad-neto');
        $this->assertLessThan($total, $invoice->total, 'bad-total');
        $this->assertTrue($invoice->save(), 'cant-update-invoice-discount');

        // comprobamos que el asiento ha cambiado
        $updEntry = $invoice->getAccountingEntry();
        $this->assertTrue($updEntry->exists(), 'updated-accounting-entry-not-found');
        $this->assertEquals($invoice->idasiento, $updEntry->idasiento, 'accounting-entry-not-updated');
        $this->assertEquals($invoice->total, $updEntry->importe, 'updated-accounting-entry-bad-importe');

        // eliminamos
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertFalse($updEntry->exists(), 'deleted-accounting-entry-still-found');
        $this->assertTrue($supplier->delete(), 'cant-delete-supplier');
    }

    public function cantUpdateOrDeleteNonEditableInvoice()
    {
        // creamos el proveedor
        $supplier = $this->getRandomSupplier();
        $this->assertTrue($supplier->save(), 'cant-create-supplier');

        // creamos la factura
        $invoice = new FacturaProveedor();
        $invoice->setSubject($supplier);
        $this->assertTrue($invoice->save(), 'cant-create-invoice');

        // añadimos una línea
        $firstLine = $invoice->getNewLine();
        $firstLine->cantidad = 1;
        $firstLine->pvpunitario = self::PRODUCT1_COST;
        $this->assertTrue($firstLine->save(), 'cant-save-first-line');

        // recalculamos
        $tool = new BusinessDocumentTools();
        $tool->recalculate($invoice);
        $this->assertTrue($invoice->save(), 'cant-update-invoice');

        // asignamos un estado no editable
        $changed = false;
        foreach ($invoice->getAvailableStatus() as $status) {
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
        $this->assertTrue($supplier->delete(), 'cant-delete-supplier');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
