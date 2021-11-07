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
use FacturaScripts\Core\Model\Almacen;
use FacturaScripts\Core\Model\FacturaProveedor;
use FacturaScripts\Core\Model\Producto;
use FacturaScripts\Core\Model\Proveedor;
use FacturaScripts\Test\Core\ShowLogTrait;
use PHPUnit\Framework\TestCase;

final class FacturaProveedorTest extends TestCase
{
    use ShowLogTrait;

    const INVOICE_NOTES = 'Test test test.';
    const INVOICE_REF = '7777777';
    const PRODUCT1_COST = 99.9;
    const PRODUCT1_QUANTITY = 10;
    const PRODUCT1_REF = '1234';
    const SUPPLIER_CIF = '8888';
    const SUPPLIER_NAME = 'ACME';

    public function testCreateNewInvoice()
    {
        $supplier = $this->getSupplier();
        $this->assertTrue($supplier->save(), 'cant-create-supplier');

        $product = $this->getProduct1();
        $this->assertTrue($product->save(), 'cant-create-product');
        $this->assertEquals(0, $product->stockfis, 'product-bad-stock');

        $invoice = new FacturaProveedor();
        $invoice->setSubject($supplier);
        $invoice->numproveedor = self::INVOICE_REF;
        $invoice->observaciones = self::INVOICE_NOTES;
        $this->assertTrue($invoice->save(), 'cant-create-invoice');
        $this->assertTrue($invoice->exists(), 'invoice-does-not-exist');

        $firstLine = $invoice->getNewProductLine(self::PRODUCT1_REF);
        $firstLine->cantidad = self::PRODUCT1_QUANTITY;
        $firstLine->pvpunitario = self::PRODUCT1_COST;
        $this->assertEquals(self::PRODUCT1_REF, $firstLine->referencia, 'bad-first-line-reference');
        $this->assertEquals(self::PRODUCT1_REF, $firstLine->descripcion, 'bad-first-line-description');
        $this->assertTrue($firstLine->save(), 'cant-save-first-line');
        $this->assertTrue($firstLine->exists(), 'first-invoice-line-does-not-exists');

        $tool = new BusinessDocumentTools();
        $tool->recalculate($invoice);
        $this->assertTrue($invoice->save(), 'cant-update-invoice');

        // reload product
        $product->loadFromCode($product->idproducto);
        $this->assertEquals(self::PRODUCT1_QUANTITY, $product->stockfis, 'bad-product1-stock');

        // find invoice
        $readedInvoice = $invoice->get($invoice->idfactura);
        $this->assertIsObject($readedInvoice, 'invoice-cant-be-read');
        $this->assertEquals($invoice->codigo, $readedInvoice->codigo, 'bad-invoice-codigo');
        $this->assertEquals($invoice->neto, $readedInvoice->neto, 'bad-invoice-neto');
        $this->assertEquals(self::SUPPLIER_NAME, $readedInvoice->nombre, 'bad-invoice-nombre');
        $this->assertEquals($invoice->numero, $readedInvoice->numero, 'bad-invoice-numero');
        $this->assertEquals(self::INVOICE_REF, $readedInvoice->numproveedor, 'bad-invoice-numproveedor');
        $this->assertEquals(self::INVOICE_NOTES, $readedInvoice->observaciones, 'bad-invoice-notes');
        $this->assertEquals($invoice->total, $readedInvoice->total, 'bad-invoice-total');

        // delete
        $this->assertTrue($readedInvoice->delete(), 'cant-delete-invoice');
        $this->assertFalse($readedInvoice->exists(), 'deleted-invoice-still-found');
        $this->assertFalse($invoice->exists(), 'deleted-invoice-still-found-2');
        $this->assertFalse($firstLine->exists(), 'deleted-line-invoice-still-found');

        // reload product again
        $product->loadFromCode($product->idproducto);
        $this->assertEquals(0, $product->stockfis, 'bad-product1-stock-end');

        // delete product and supplier
        $this->assertTrue($product->delete(), 'cant-delete-product');
        $this->assertTrue($supplier->delete(), 'cant-delete-supplier');
    }

    private function getProduct1(): Producto
    {
        $product = new Producto();
        $where = [new DataBaseWhere('referencia', self::PRODUCT1_REF)];
        $product->loadFromCode('', $where);

        $product->descripcion = $product->referencia = self::PRODUCT1_REF;
        $product->nostock = false;
        $product->secompra = true;
        return $product;
    }

    private function getSupplier(): Proveedor
    {
        $supplier = new Proveedor();
        $where = [new DataBaseWhere('nombre', self::SUPPLIER_NAME)];
        $supplier->loadFromCode('', $where);

        $supplier->cifnif = self::SUPPLIER_CIF;
        $supplier->nombre = self::SUPPLIER_NAME;
        return $supplier;
    }

    protected function setUp()
    {
        $almacenModel = new Almacen();
        foreach ($almacenModel->all() as $almacen) {
            $appset = new AppSettings();
            $appset->set('default', 'codalmacen', $almacen->codalmacen);
            $appset->save();
        }
    }
}
