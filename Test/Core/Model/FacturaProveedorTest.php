<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\DataSrc\Retenciones;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Lib\FacturaProveedorRenumber;
use FacturaScripts\Core\Lib\InvoiceOperation;
use FacturaScripts\Core\Lib\ProductType;
use FacturaScripts\Core\Lib\RegimenIVA;
use FacturaScripts\Core\Lib\Vies;
use FacturaScripts\Core\Model\FacturaProveedor;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
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
        self::removeTaxRegularization();
    }

    public function testCreateNewInvoice(): void
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
        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true), 'cant-update-invoice');
        $neto = round(self::PRODUCT1_QUANTITY * self::PRODUCT1_COST, 2);
        $this->assertEquals($neto, $invoice->neto, 'bad-invoice-neto');

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
        $this->assertTrue($supplier->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($supplier->delete(), 'cant-delete-invoice');
    }

    public function testCanNotCreateInvoiceWithoutSupplier(): void
    {
        $invoice = new FacturaProveedor();
        $this->assertFalse($invoice->save(), 'can-create-invoice-without-supplier');
    }

    public function testInvoiceLineUpdateStock(): void
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
        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true), 'cant-update-invoice');

        // comprobamos el stock del producto
        $product->loadFromCode($product->idproducto);
        $this->assertEquals(self::PRODUCT1_QUANTITY, $product->stockfis, 'bad-product1-stock');

        // eliminamos
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertFalse($firstLine->exists(), 'deleted-line-invoice-still-found');
        $this->assertTrue($supplier->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($supplier->delete(), 'cant-delete-supplier');

        // comprobamos que el stock del producto ha desaparecido
        $product->loadFromCode($product->idproducto);
        $this->assertEquals(0, $product->stockfis, 'bad-product1-stock-end');

        // eliminamos el producto
        $this->assertTrue($product->delete(), 'cant-delete-producto');
    }

    public function testCreateInvoiceCreatesAccountingEntry(): void
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
        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true), 'cant-update-invoice');
        $netosindto = $invoice->netosindto;
        $neto = $invoice->neto;
        $total = $invoice->total;

        // comprobamos el asiento
        $entry = $invoice->getAccountingEntry();
        $this->assertTrue($entry->exists(), 'accounting-entry-not-found');
        $this->assertEquals($total, $entry->importe, 'accounting-entry-bad-importe');
        $this->assertEquals($invoice->fecha, $entry->fecha, 'accounting-entry-bad-fecha');
        $this->assertEquals($invoice->idasiento, $entry->idasiento, 'accounting-entry-bad-idasiento');

        // cambiamos el descuento para que cambie el total (el asiento debe cambiar)
        $invoice->dtopor1 = 50;
        $this->assertTrue(Calculator::calculate($invoice, $lines, true), 'cant-update-invoice-discount');
        $this->assertEquals($netosindto, $invoice->netosindto, 'bad-netosindto');
        $this->assertLessThan($neto, $invoice->neto, 'bad-neto');
        $this->assertLessThan($total, $invoice->total, 'bad-total');

        // comprobamos que el asiento ha cambiado
        $updEntry = $invoice->getAccountingEntry();
        $this->assertTrue($updEntry->exists(), 'updated-accounting-entry-not-found');
        $this->assertEquals($invoice->idasiento, $updEntry->idasiento, 'accounting-entry-not-updated');
        $this->assertEquals($invoice->total, $updEntry->importe, 'updated-accounting-entry-bad-importe');

        // eliminamos
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertFalse($updEntry->exists(), 'deleted-accounting-entry-still-found');
        $this->assertTrue($supplier->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($supplier->delete(), 'cant-delete-supplier');
    }

    public function testCantUpdateOrDeleteNonEditableInvoice(): void
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
        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true), 'cant-update-invoice');

        // asignamos un estado no editable
        $changed = false;
        $previous = $invoice->idestado;
        foreach ($invoice->getAvailableStatus() as $status) {
            if (false === $status->editable) {
                $invoice->idestado = $status->idestado;
                $changed = true;
                break;
            }
        }
        $this->assertTrue($changed, 'non-editable-status-not-found');
        $this->assertTrue($invoice->save(), 'cant-update-invoice');

        // cambiamos el descuento, recalculamos y guardamos
        $invoice->dtopor1 = 50;
        $this->assertFalse(Calculator::calculate($invoice, $lines, true), 'can-update-non-editable-invoice');
        $this->assertFalse($invoice->delete(), 'can-delete-non-editable-invoice');

        // volvemos al estado anterior
        $invoice->idestado = $previous;
        $this->assertTrue($invoice->save(), 'cant-update-invoice');

        // eliminamos
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertTrue($supplier->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($supplier->delete(), 'cant-delete-supplier');
    }

    public function testCreateInvoiceWithRetention(): void
    {
        // creamos un proveedor con retención
        $supplier = $this->getRandomSupplier();
        foreach (Retenciones::all() as $retention) {
            $supplier->codretencion = $retention->codretencion;
            break;
        }
        $this->assertTrue($supplier->save(), 'cant-create-supplier');

        // creamos la factura
        $invoice = new FacturaProveedor();
        $invoice->setSubject($supplier);
        $this->assertTrue($invoice->save(), 'cant-create-invoice');

        // añadimos una línea
        $firstLine = $invoice->getNewLine();
        $firstLine->cantidad = 2;
        $firstLine->pvpunitario = self::PRODUCT1_COST;
        $this->assertTrue($firstLine->save(), 'cant-save-first-line');

        // recalculamos
        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true), 'cant-update-invoice');
        $this->assertGreaterThan(0, $invoice->totalirpf, 'bad-totalirpf');

        // comprobamos el asiento
        $entry = $invoice->getAccountingEntry();
        $this->assertTrue($entry->exists(), 'accounting-entry-not-found');
        $this->assertEquals($invoice->total, $entry->importe, 'accounting-entry-bad-importe');

        // comprobamos que el asiento tiene una línea cuyo haber es el totalirpf de la factura
        $found = false;
        foreach ($entry->getLines() as $line) {
            if ($line->haber == $invoice->totalirpf) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'accounting-entry-without-retention-line');

        // eliminamos
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertTrue($supplier->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($supplier->delete(), 'cant-delete-supplier');
    }

    public function testCreateInvoiceWithSurcharge(): void
    {
        // creamos un proveedor con el régimen de recargo de equivalencia
        $supplier = $this->getRandomSupplier();
        $supplier->regimeniva = RegimenIVA::TAX_SYSTEM_SURCHARGE;
        $this->assertTrue($supplier->save(), 'cant-create-supplier');

        // creamos la factura
        $invoice = new FacturaProveedor();
        $invoice->setSubject($supplier);
        $this->assertTrue($invoice->save(), 'cant-create-invoice');

        // añadimos una línea
        $firstLine = $invoice->getNewLine();
        $firstLine->cantidad = 2;
        $firstLine->pvpunitario = 100;
        $this->assertTrue($firstLine->save(), 'cant-save-first-line');

        // recalculamos
        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true), 'cant-update-invoice');

        // comprobamos los totales
        $this->assertEquals(200, $invoice->neto, 'bad-neto');
        $this->assertEquals(200, $invoice->netosindto, 'bad-netosindto');
        $this->assertEquals(42, $invoice->totaliva, 'bad-totaliva');
        $this->assertEquals(10.4, $invoice->totalrecargo, 'bad-totalrecargo');
        $this->assertEquals(0, $invoice->totalirpf, 'bad-totalirpf');
        $this->assertEquals(0, $invoice->totalsuplidos, 'bad-totalsuplidos');
        $this->assertEquals(252.4, $invoice->total, 'bad-total');

        // comprobamos el asiento
        $entry = $invoice->getAccountingEntry();
        $this->assertTrue($entry->exists(), 'accounting-entry-not-found');
        $this->assertEquals($invoice->total, $entry->importe, 'accounting-entry-bad-importe');

        // eliminamos
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertTrue($supplier->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($supplier->delete(), 'cant-delete-supplier');
    }

    public function testCompanyWithSurcharge(): void
    {
        // creamos una empresa con el régimen de recargo de equivalencia
        $company = $this->getRandomCompany();
        $company->regimeniva = RegimenIVA::TAX_SYSTEM_SURCHARGE;
        $this->assertTrue($company->save(), 'cant-create-company');

        // creamos un proveedor
        $supplier = $this->getRandomSupplier();
        $this->assertTrue($supplier->save(), 'cant-create-supplier');

        // creamos la factura
        $invoice = new FacturaProveedor();
        foreach ($company->getWarehouses() as $warehouse) {
            $invoice->setWarehouse($warehouse->codalmacen);
            break;
        }
        $invoice->setSubject($supplier);
        $this->assertTrue($invoice->save(), 'cant-create-invoice');

        // añadimos una línea
        $firstLine = $invoice->getNewLine();
        $firstLine->cantidad = 2;
        $firstLine->pvpunitario = 50;
        $this->assertTrue($firstLine->save(), 'cant-save-first-line');

        // recalculamos
        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true), 'cant-update-invoice');

        // comprobamos los totales
        $this->assertEquals(100, $invoice->neto, 'bad-neto');
        $this->assertEquals(100, $invoice->netosindto, 'bad-netosindto');
        $this->assertEquals(21, $invoice->totaliva, 'bad-totaliva');
        $this->assertEquals(0, $invoice->totalrecargo, 'bad-totalrecargo');
        $this->assertEquals(0, $invoice->totalirpf, 'bad-totalirpf');
        $this->assertEquals(0, $invoice->totalsuplidos, 'bad-totalsuplidos');
        $this->assertEquals(121, $invoice->total, 'bad-total');

        // eliminamos
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertTrue($supplier->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($supplier->delete(), 'cant-delete-supplier');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    public function testCreateInvoiceWithSupplied(): void
    {
        // creamos un proveedor
        $supplier = $this->getRandomSupplier();
        $this->assertTrue($supplier->save(), 'cant-create-supplier');

        // creamos una factura
        $invoice = new FacturaProveedor();
        $invoice->setSubject($supplier);
        $this->assertTrue($invoice->save(), 'cant-create-invoice');

        // añadimos una línea
        $firstLine = $invoice->getNewLine();
        $firstLine->cantidad = 2;
        $firstLine->pvpunitario = 100;
        $firstLine->suplido = true;
        $this->assertTrue($firstLine->save(), 'cant-save-first-line');

        // recalculamos
        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true), 'cant-update-invoice');
        $this->assertEquals(200, $invoice->totalsuplidos, 'bad-totalsuplidos');

        // comprobamos el asiento
        $entry = $invoice->getAccountingEntry();
        $this->assertTrue($entry->exists(), 'accounting-entry-not-found');
        $this->assertEquals($invoice->total, $entry->importe, 'accounting-entry-bad-importe');

        // comprobamos que el asiento tiene una línea cuyo haber es el totalsuplidos de la factura
        $found = false;
        foreach ($entry->getLines() as $line) {
            if ($line->haber == $invoice->totalsuplidos) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'accounting-entry-without-supplied-line');

        // eliminamos
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertTrue($supplier->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($supplier->delete(), 'cant-delete-supplier');
    }

    public function testRenumber(): void
    {
        // creamos un proveedor
        $supplier = $this->getRandomSupplier();
        $this->assertTrue($supplier->save(), 'cant-create-supplier');

        // fecha inicial del 2 de enero
        $date = date('02-01-Y');

        // creamos una serie
        $serie = $this->getRandomSerie();
        $this->assertTrue($serie->save(), 'cant-create-serie');

        // creamos 10 facturas
        for ($i = 11; $i > 1; $i--) {
            $invoice = new FacturaProveedor();
            $invoice->setSubject($supplier);
            $invoice->codserie = $serie->codserie;
            $invoice->setDate($date, $invoice->hora);
            $invoice->numero = $i;
            $invoice->codigo = $date . '-' . $i;
            $this->assertTrue($invoice->save(), 'cant-create-invoice-' . $i);

            // recargamos la factura
            $invoice->loadFromCode($invoice->primaryColumnValue());

            // comprobamos que el código y número son correctos
            $this->assertEquals($date . '-' . $i, $invoice->codigo, 'bad-invoice-code-' . $i);
            $this->assertEquals($i, $invoice->numero, 'bad-invoice-number-' . $i);

            $date = date('d-m-Y', strtotime($date . ' + 1 day'));
        }

        // comprobamos que hay 10 facturas
        $invoiceModel = new FacturaProveedor();
        $this->assertEquals(10, $invoiceModel->count(), 'bad-invoice-count');

        // obtenemos el ejercicio de la primera factura
        $where = [new DataBaseWhere('codserie', $serie->codserie)];
        $codejercicio = $invoiceModel->all($where, [], 0, 1)[0]->codejercicio;

        // re-numeramos
        $this->assertTrue(FacturaProveedorRenumber::run($codejercicio), 'cant-renumber-invoices');

        // recorremos las facturas para comprobar que están numeradas correctamente
        $orderBy = ['fecha' => 'ASC', 'hora' => 'ASC'];
        $num = 1;
        foreach ($invoiceModel->all($where, $orderBy, 0, 0) as $invoice) {
            $this->assertEquals($num, $invoice->numero, 'bad-invoice-number-' . $num);
            $num++;
        }

        // eliminamos las facturas
        foreach ($invoiceModel->all($where, $orderBy, 0, 0) as $invoice) {
            $this->assertTrue($invoice->delete(), 'cant-delete-invoice-' . $invoice->codigo);
        }

        // eliminamos el proveedor
        $this->assertTrue($supplier->delete(), 'cant-delete-supplier');
        $this->assertTrue($supplier->getDefaultAddress()->delete(), 'contacto-cant-delete');

        // eliminamos la serie
        $this->assertTrue($serie->delete(), 'cant-delete-serie');
    }

    public function testInvoiceWithDifferentAccountingDate(): void
    {
        // creamos un proveedor
        $supplier = $this->getRandomSupplier();
        $this->assertTrue($supplier->save(), 'cant-create-supplier');

        // creamos una factura con fecha del 3 de marzo y fecha devengo del 28 de febrero
        $date = date('03-03-Y');
        $entryDate = date('28-02-Y');
        $invoice = new FacturaProveedor();
        $invoice->setSubject($supplier);
        $invoice->setDate($date, $invoice->hora);
        $invoice->fechadevengo = $entryDate;
        $this->assertTrue($invoice->save(), 'cant-create-invoice');

        // añadimos una línea
        $firstLine = $invoice->getNewLine();
        $firstLine->cantidad = 2;
        $firstLine->pvpunitario = 100;
        $this->assertTrue($firstLine->save(), 'cant-save-first-line');

        // recalculamos
        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true), 'cant-update-invoice');

        // comprobamos la fecha de la factura
        $this->assertEquals($date, $invoice->fecha, 'bad-invoice-date');

        // comprobamos la fecha de devengo de la factura
        $this->assertEquals($entryDate, $invoice->fechadevengo, 'bad-invoice-entry-date');

        // comprobamos la fecha del asiento
        $this->assertEquals($entryDate, $invoice->getAccountingEntry()->fecha, 'bad-entry-date');

        // eliminamos
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertTrue($supplier->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($supplier->delete(), 'cant-delete-supplier');
    }

    public function testIntraCommunity(): void
    {
        // creamos un proveedor
        $supplier = $this->getRandomSupplier();
        $this->assertTrue($supplier->save());

        // creamos una factura
        $invoice = new FacturaProveedor();
        $invoice->setSubject($supplier);
        $invoice->operacion = InvoiceOperation::INTRA_COMMUNITY;
        $this->assertTrue($invoice->save());

        // añadimos una línea
        $line = $invoice->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        $this->assertTrue($line->save());

        // recalculamos
        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true));

        // comprobamos los totales
        $this->assertEquals(100, $invoice->neto);
        $this->assertEquals(0, $invoice->totaliva);
        $this->assertEquals(0, $invoice->totalirpf);
        $this->assertEquals(100, $invoice->total);

        // comprobamos que el asiento tiene 4 líneas
        $entry = $invoice->getAccountingEntry();
        $this->assertCount(4, $entry->getLines());

        // eliminamos
        $this->assertTrue($invoice->delete());
        $this->assertTrue($supplier->getDefaultAddress()->delete());
        $this->assertTrue($supplier->delete());
    }

    public function testSetIntraCommunity(): void
    {
        // comprobamos si el VIES funciona
        if (Vies::getLastError() != '') {
            $this->markTestSkipped('Vies service is not available');
        }

        // establecemos la empresa en España con un cif español
        $company = Empresas::default();
        $company->codpais = 'ESP';
        $company->cifnif = 'B13658620';
        $company->tipoidfiscal = 'CIF';
        $this->assertTrue($company->save());

        // creamos un proveedor de Portugal con nif de Portugal
        $supplier = $this->getRandomSupplier();
        $supplier->cifnif = 'PT513969144';
        $this->assertTrue($supplier->save());
        $address = $supplier->getDefaultAddress();
        $address->codpais = 'PRT';
        $this->assertTrue($address->save());

        // creamos una factura
        $invoice = new FacturaProveedor();
        $invoice->setSubject($supplier);
        $this->assertTrue($invoice->setIntracomunitaria());

        // comprobamos que la operación es intracomunitaria
        $this->assertEquals(InvoiceOperation::INTRA_COMMUNITY, $invoice->operacion);

        // quitamos la operación
        $invoice->operacion = null;

        // cambiamos la empresa a Perú
        $company->codpais = 'PER';
        $this->assertTrue($company->save());

        // comprobamos que no se puede establecer la operación
        $this->assertFalse($invoice->setIntracomunitaria());

        // volvemos a España
        $company->codpais = 'ESP';
        $this->assertTrue($company->save());

        // cambiamos el proveedor a España
        $address->codpais = 'ESP';
        $this->assertTrue($address->save());

        // comprobamos que no se puede establecer la operación
        $this->assertFalse($invoice->setIntracomunitaria());

        // eliminamos
        $this->assertTrue($invoice->delete());
        $this->assertTrue($address->delete());
        $this->assertTrue($supplier->delete());
    }

    public function testBuyUsedGoods(): void
    {
        // creamos una empresa con el régimen de bienes usados
        $company = $this->getRandomCompany();
        $company->regimeniva = RegimenIVA::TAX_SYSTEM_USED_GOODS;
        $this->assertTrue($company->save());

        // creamos un producto de segunda mano
        $product = $this->getRandomProduct();
        $product->tipo = ProductType::SECOND_HAND;
        $this->assertTrue($product->save());

        // creamos un proveedor
        $supplier = $this->getRandomSupplier();
        $this->assertTrue($supplier->save());

        // creamos una factura
        $invoice = new FacturaProveedor();
        foreach ($company->getWarehouses() as $warehouse) {
            $invoice->setWarehouse($warehouse->codalmacen);
            break;
        }
        $invoice->setSubject($supplier);
        $this->assertTrue($invoice->save());

        // añadimos el producto
        $line = $invoice->getNewProductLine($product->referencia);
        $line->cantidad = 1;
        $line->pvpunitario = 900;
        $this->assertTrue($line->save());

        // recalculamos
        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true));

        // comprobamos los totales
        $this->assertEquals(900, $invoice->neto);
        $this->assertEquals(0, $invoice->totaliva);
        $this->assertEquals(0, $invoice->totalirpf);
        $this->assertEquals(900, $invoice->total);

        // eliminamos
        $this->assertTrue($invoice->delete());
        $this->assertTrue($supplier->getDefaultAddress()->delete());
        $this->assertTrue($supplier->delete());
        $this->assertTrue($product->delete());
        $this->assertTrue($company->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
