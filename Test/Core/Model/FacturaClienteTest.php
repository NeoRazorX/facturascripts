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

use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\DataSrc\Retenciones;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Lib\InvoiceOperation;
use FacturaScripts\Core\Lib\ProductType;
use FacturaScripts\Core\Lib\RegimenIVA;
use FacturaScripts\Core\Lib\Vies;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Core\Model\FacturaCliente;
use FacturaScripts\Core\Model\Stock;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
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
        self::removeTaxRegularization();
    }

    public function testCanCreateInvoice(): void
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
        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true), 'cant-update-invoice');
        $neto = round(self::PRODUCT1_PRICE * self::PRODUCT1_QUANTITY, FS_NF0);
        $this->assertEquals($neto, $invoice->neto, 'bad-invoice-neto');

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
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
    }

    public function testCanNotCreateInvoiceWithoutCustomer(): void
    {
        $invoice = new FacturaCliente();
        $this->assertFalse($invoice->save(), 'can-create-invoice-without-customer');
    }

    public function testCanNotCreateInvoiceWithBadExercise(): void
    {
        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // creamos la factura
        $invoice = new FacturaCliente();
        $invoice->setSubject($customer);

        // asignamos una fecha de 2019
        $this->assertTrue($invoice->setDate('11-01-2019', '00:00:00'));
        $oldExercise = $invoice->getExercise();

        // creamos un ejercicio para 2020
        $exercise = new Ejercicio();
        $exercise->codejercicio = '2020';
        $exercise->nombre = '2020';
        $exercise->fechainicio = '01-01-2020';
        $exercise->fechafin = '31-12-2020';
        $this->assertTrue($exercise->save());

        // asignamos el ejercicio a la factura
        $invoice->codejercicio = $exercise->codejercicio;

        // intentamos guardar la factura, no debe permitirlo
        $this->assertFalse($invoice->save(), 'can-create-invoice-with-bad-exercise');

        // eliminamos
        $this->assertTrue($exercise->delete());
        $this->assertTrue($oldExercise->delete());
        $this->assertTrue($customer->getDefaultAddress()->delete());
        $this->assertTrue($customer->delete());
    }

    public function testCanNotCreateInvoiceWithDuplicatedCode(): void
    {
        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // creamos la factura
        $invoice = new FacturaCliente();
        $invoice->setSubject($customer);
        $this->assertTrue($invoice->save(), 'cant-create-invoice');

        // creamos otra factura con el mismo código
        $invoice2 = new FacturaCliente();
        $invoice2->setSubject($customer);
        $invoice2->codigo = $invoice->codigo;
        $this->assertFalse($invoice2->save(), 'can-create-invoice-with-duplicated-code: ' . $invoice->codigo . ' = ' . $invoice2->codigo);

        // eliminamos
        $this->assertTrue($invoice->delete());
        $this->assertTrue($customer->getDefaultAddress()->delete());
        $this->assertTrue($customer->delete());
    }

    public function testCanNotCreateInvoiceWithDuplicatedNumber(): void
    {
        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // creamos la factura
        $invoice = new FacturaCliente();
        $invoice->setSubject($customer);
        $this->assertTrue($invoice->save(), 'cant-create-invoice');

        // creamos otra factura con el mismo número
        $invoice2 = new FacturaCliente();
        $invoice2->setSubject($customer);
        $invoice2->codigo = $invoice->codigo . '-2';
        $invoice2->numero = $invoice->numero;
        $this->assertFalse($invoice2->save(), 'can-create-invoice-with-duplicated-number');

        // eliminamos
        $this->assertTrue($invoice->delete());
        $this->assertTrue($customer->getDefaultAddress()->delete());
        $this->assertTrue($customer->delete());
    }

    public function testInvoiceLineUpdateStock(): void
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
        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true), 'cant-update-invoice');

        // comprobamos el stock del producto
        $product->loadFromCode($product->idproducto);
        $this->assertEquals(0, $product->stockfis, 'bad-product1-stock');

        // eliminamos
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertFalse($firstLine->exists(), 'deleted-line-invoice-still-found');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($customer->delete(), 'cant-delete-customer');

        // comprobamos que se restaura el stock del producto
        $product->loadFromCode($product->idproducto);
        $this->assertEquals(self::PRODUCT1_QUANTITY, $product->stockfis, 'bad-product1-stock-end');

        // eliminamos el producto
        $this->assertTrue($product->delete(), 'cant-delete-product');
    }

    public function testCreateInvoiceCreatesAccountingEntry(): void
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
        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true), 'cant-update-invoice');
        $netosindto = $invoice->netosindto;
        $neto = $invoice->neto;
        $total = $invoice->total;

        // comprobamos el asiento
        $entry = $invoice->getAccountingEntry();
        $this->assertTrue($entry->exists(), 'accounting-entry-not-found');
        $this->assertEquals($total, $entry->importe, 'accounting-entry-bad-importe');
        $this->assertEquals($invoice->fecha, $entry->fecha, 'accounting-entry-bad-date');
        $this->assertEquals($invoice->idasiento, $entry->idasiento, 'accounting-entry-bad-idasiento');

        // aplicamos un descuento para modificar el total de la factura
        $invoice->dtopor1 = 50;
        Calculator::calculate($invoice, $lines, false);
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
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
    }

    public function testCantUpdateOrDeleteNonEditableInvoice(): void
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
        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true), 'cant-update-invoice');

        // cambiamos el estado a uno no editable
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

        // volvemos a cambiar el estado
        $invoice->idestado = $previous;
        $this->assertTrue($invoice->save(), 'cant-update-invoice');

        // eliminamos
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
    }

    public function testCreateInvoiceWithRetention(): void
    {
        // creamos un cliente y le asignamos una retención
        $customer = $this->getRandomCustomer();
        foreach (Retenciones::all() as $retention) {
            $customer->codretencion = $retention->codretencion;
            break;
        }
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
        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true), 'cant-update-invoice');
        $this->assertGreaterThan(0, $invoice->totalirpf, 'bad-totalirpf');

        // comprobamos el asiento
        $entry = $invoice->getAccountingEntry();
        $this->assertTrue($entry->exists(), 'accounting-entry-not-found');
        $this->assertEquals($invoice->total, $entry->importe, 'accounting-entry-bad-importe');

        // comprobamos que el asiento tiene una línea cuyo debe es el totalirpf de la factura
        $found = false;
        foreach ($entry->getLines() as $line) {
            if ($line->debe == $invoice->totalirpf) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'accounting-entry-without-retention-line');

        // eliminamos
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
    }

    public function testCreateInvoiceWithSurcharge(): void
    {
        // creamos un cliente con régimen de recargo de equivalencia
        $customer = $this->getRandomCustomer();
        $customer->regimeniva = RegimenIVA::TAX_SYSTEM_SURCHARGE;
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // creamos la factura
        $invoice = new FacturaCliente();
        $invoice->setSubject($customer);
        $this->assertTrue($invoice->save(), 'cant-create-invoice');

        // añadimos una línea
        $firstLine = $invoice->getNewLine();
        $firstLine->cantidad = 1;
        $firstLine->pvpunitario = 200;
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
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
    }

    public function testCompanyWithSurcharge(): void
    {
        // creamos una empresa con régimen de recargo de equivalencia
        $company = $this->getRandomCompany();
        $company->regimeniva = RegimenIVA::TAX_SYSTEM_SURCHARGE;
        $this->assertTrue($company->save(), 'cant-create-company');

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // creamos la factura
        $invoice = new FacturaCliente();
        foreach ($company->getWarehouses() as $warehouse) {
            $invoice->setWarehouse($warehouse->codalmacen);
            break;
        }
        $invoice->setSubject($customer);
        $this->assertTrue($invoice->save(), 'cant-create-invoice');

        // añadimos una línea
        $firstLine = $invoice->getNewLine();
        $firstLine->cantidad = 1;
        $firstLine->pvpunitario = 30.07;
        $this->assertTrue($firstLine->save(), 'cant-save-first-line');

        // recalculamos
        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true), 'cant-update-invoice');

        // comprobamos los totales
        $this->assertEquals(30.07, $invoice->neto, 'bad-neto');
        $this->assertEquals(30.07, $invoice->netosindto, 'bad-neto-sin-dto');
        $this->assertEquals(6.31, $invoice->totaliva, 'bad-total-iva');
        $this->assertEquals(0, $invoice->totalrecargo, 'bad-total-recargo');
        $this->assertEquals(0, $invoice->totalirpf, 'bad-total-irpf');
        $this->assertEquals(0, $invoice->totalsuplidos, 'bad-total-suplidos');
        $this->assertEquals(36.38, $invoice->total, 'bad-total');

        // comprobamos también los subtotales, para ver que no hay más decimales de los necesarios
        $subtotals = Calculator::getSubtotals($invoice, $lines);
        $this->assertEquals(6.31, $subtotals['iva']['21|0']['totaliva'], 'bad-subtotal-iva');
        $this->assertEquals(0, $subtotals['iva']['21|0']['totalrecargo'], 'bad-subtotal-recargo');

        // eliminamos
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    public function testCreateInvoiceWithSupplied(): void
    {
        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // creamos una factura
        $invoice = new FacturaCliente();
        $invoice->setSubject($customer);
        $this->assertTrue($invoice->save(), 'cant-create-invoice');

        // añadimos una línea
        $firstLine = $invoice->getNewLine();
        $firstLine->cantidad = 1;
        $firstLine->pvpunitario = 200;
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

        // comprobamos que el asiento tiene una línea cuyo debe es el totalsuplidos de la factura
        $found = false;
        foreach ($entry->getLines() as $line) {
            if ($line->debe == $invoice->totalsuplidos) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'accounting-entry-without-supplied-line');

        // eliminamos
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
    }

    public function testCreateInvoiceWithOldDate(): void
    {
        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // creamos una factura el 10 de enero
        $invoice = new FacturaCliente();
        $invoice->setSubject($customer);
        $invoice->setDate(date('10-01-Y'), $invoice->hora);
        $this->assertTrue($invoice->save(), 'cant-create-invoice');

        // añadimos una línea
        $firstLine = $invoice->getNewLine();
        $firstLine->cantidad = 1;
        $firstLine->pvpunitario = 200;
        $this->assertTrue($firstLine->save(), 'cant-save-first-line');

        // recalculamos
        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true), 'cant-update-invoice');

        // creamos una factura el 9 de enero
        $invoice2 = new FacturaCliente();
        $invoice2->setSubject($customer);
        $invoice2->setDate(date('09-01-Y'), $invoice2->hora);

        // comprobamos que no se puede crear
        $this->assertFalse($invoice2->save(), 'cant-create-invoice');

        // asignamos la fecha 11 de enero
        $invoice2->setDate(date('11-01-Y'), $invoice2->hora);
        $this->assertTrue($invoice2->save(), 'cant-create-invoice');

        // añadimos una línea
        $firstLine = $invoice2->getNewLine();
        $firstLine->cantidad = 1;
        $firstLine->pvpunitario = 200;
        $this->assertTrue($firstLine->save(), 'cant-save-first-line');

        // recalculamos
        $lines = $invoice2->getLines();
        $this->assertTrue(Calculator::calculate($invoice2, $lines, true), 'cant-update-invoice');

        // comprobamos la fecha del asiento
        $this->assertEquals($invoice2->fecha, $invoice2->getAccountingEntry()->fecha, 'bad-entry-date');

        // cambiamos la fecha al 9 de enero
        $invoice2->setDate(date('09-01-Y'), $invoice2->hora);
        $this->assertFalse($invoice2->save(), 'cant-create-invoice');

        // eliminamos
        $this->assertTrue($invoice2->delete(), 'cant-delete-invoice');
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
    }

    public function testCreateInvoiceWithOldDateAndSerie(): void
    {
        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // creamos una nueva serie
        $serie = $this->getRandomSerie();
        $this->assertTrue($serie->save(), 'cant-create-serie');

        // creamos una factura el 11 de enero
        $invoice = new FacturaCliente();
        $invoice->setSubject($customer);
        $invoice->codserie = $serie->codserie;
        $invoice->setDate(date('11-01-Y'), $invoice->hora);
        $this->assertTrue($invoice->save(), 'cant-create-invoice');

        // añadimos una línea
        $firstLine = $invoice->getNewLine();
        $firstLine->cantidad = 1;
        $firstLine->pvpunitario = 200;
        $this->assertTrue($firstLine->save(), 'cant-save-first-line');

        // recalculamos
        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true), 'cant-update-invoice');

        // creamos una factura el 8 de enero
        $invoice2 = new FacturaCliente();
        $invoice2->setSubject($customer);
        $invoice2->codserie = $serie->codserie;
        $invoice2->setDate(date('08-01-Y'), $invoice2->hora);

        // comprobamos que no se puede crear
        $this->assertFalse($invoice2->save(), 'cant-create-invoice');

        // asignamos la fecha 12 de enero
        $invoice2->setDate(date('12-01-Y'), $invoice2->hora);
        $this->assertTrue($invoice2->save(), 'cant-create-invoice');

        // eliminamos
        $this->assertTrue($invoice2->delete(), 'cant-delete-invoice');
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertTrue($serie->delete(), 'cant-delete-serie');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
    }

    public function testUpdateInvoiceWithOldDate(): void
    {
        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // creamos una factura el 10 de enero
        $invoice = new FacturaCliente();
        $invoice->setSubject($customer);
        $invoice->setDate(date('10-01-Y'), $invoice->hora);
        $this->assertTrue($invoice->save(), 'cant-create-invoice');

        // añadimos una línea
        $firstLine = $invoice->getNewLine();
        $firstLine->cantidad = 1;
        $firstLine->pvpunitario = 200;
        $this->assertTrue($firstLine->save(), 'cant-save-first-line');

        // recalculamos
        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true), 'cant-update-invoice');

        // comprobamos la fecha del asiento
        $this->assertEquals($invoice->fecha, $invoice->getAccountingEntry()->fecha, 'bad-entry-date');

        // creamos una factura el 11 de enero
        $invoice2 = new FacturaCliente();
        $invoice2->setSubject($customer);
        $invoice2->setDate(date('11-01-Y'), $invoice2->hora);
        $this->assertTrue($invoice2->save(), 'cant-create-invoice');

        // añadimos una línea
        $firstLine = $invoice2->getNewLine();
        $firstLine->cantidad = 1;
        $firstLine->pvpunitario = 200;
        $this->assertTrue($firstLine->save(), 'cant-save-first-line');

        // recalculamos
        $lines = $invoice2->getLines();
        $this->assertTrue(Calculator::calculate($invoice2, $lines, true), 'cant-update-invoice');

        // cambiamos la fecha de la primera factura al 12 de enero
        $invoice->setDate(date('12-01-Y'), $invoice->hora);
        $this->assertFalse($invoice->save(), 'cant-create-invoice');

        // eliminamos
        $this->assertTrue($invoice2->delete(), 'cant-delete-invoice');
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
    }

    public function testChangeInvoiceSerieWithOldDate(): void
    {
        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // creamos una factura el 2 de febrero
        $invoice = new FacturaCliente();
        $invoice->setSubject($customer);
        $invoice->setDate(date('02-02-Y'), $invoice->hora);
        $this->assertTrue($invoice->save(), 'cant-create-invoice');

        // creamos una nueva serie
        $serie = $this->getRandomSerie();
        $this->assertTrue($serie->save(), 'cant-create-serie');

        // creamos una factura el 3 de febrero
        $invoice2 = new FacturaCliente();
        $invoice2->setSubject($customer);
        $invoice2->codserie = $serie->codserie;
        $invoice2->setDate(date('03-02-Y'), $invoice2->hora);
        $this->assertTrue($invoice2->save(), 'cant-create-invoice');

        // ahora cambiamos la serie de la primera factura
        $invoice->codserie = $serie->codserie;
        $this->assertFalse($invoice->save(), 'can-change-invoice-serie-with-old-date: ' . $invoice->fecha);

        // eliminamos
        $this->assertTrue($invoice2->delete(), 'cant-delete-invoice');
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertTrue($serie->delete(), 'cant-delete-serie');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
    }

    public function testInvoiceWithDifferentAccountingDate(): void
    {
        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // creamos una factura el 2 de febrero, pero con fecha devengo del 31 de enero
        $date = date('02-02-Y');
        $entryDate = date('31-01-Y');
        $invoice = new FacturaCliente();
        $invoice->setSubject($customer);
        $invoice->setDate($date, $invoice->hora);
        $invoice->fechadevengo = $entryDate;
        $this->assertTrue($invoice->save(), 'cant-create-invoice');

        // añadimos una línea
        $firstLine = $invoice->getNewLine();
        $firstLine->cantidad = 1;
        $firstLine->pvpunitario = 200;
        $this->assertTrue($firstLine->save(), 'cant-save-first-line');

        // recalculamos
        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true), 'cant-update-invoice');

        // comprobamos la fecha de la factura
        $this->assertEquals($date, $invoice->fecha, 'invoice-date-is-not-correct');

        // comprobamos la fecha de devengo
        $this->assertEquals($entryDate, $invoice->fechadevengo, 'invoice-entry-date-is-not-correct');

        // comprobamos la fecha del asiento
        $this->assertEquals($entryDate, $invoice->getAccountingEntry()->fecha, 'invoice-entry-date-is-not-correct');

        // eliminamos
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
    }

    public function testIntraCommunity(): void
    {
        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save());

        // creamos una factura
        $invoice = new FacturaCliente();
        $invoice->setSubject($customer);
        $invoice->operacion = InvoiceOperation::INTRA_COMMUNITY;
        $this->assertTrue($invoice->save());

        // añadimos una línea
        $firstLine = $invoice->getNewLine();
        $firstLine->cantidad = 1;
        $firstLine->pvpunitario = 200;
        $this->assertTrue($firstLine->save());

        // recalculamos
        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true));

        // comprobamos los totales
        $this->assertEquals(200, $invoice->neto);
        $this->assertEquals(0, $invoice->totaliva);
        $this->assertEquals(200, $invoice->total);

        // comprobamos que el asiento tiene 4 líneas
        $entry = $invoice->getAccountingEntry();
        $this->assertCount(4, $entry->getLines());

        // eliminamos
        $this->assertTrue($invoice->delete());
        $this->assertTrue($customer->getDefaultAddress()->delete());
        $this->assertTrue($customer->delete());
    }

    public function testSetIntraCommunity(): void
    {
        // comprobamos primero si el VIES funciona
        if (Vies::getLastError() != '') {
            $this->markTestSkipped('Vies service is not available');
        }

        // establecemos la empresa en España con un cif español
        $company = Empresas::default();
        $company->codpais = 'ESP';
        $company->cifnif = 'B13658620';
        $company->tipoidfiscal = 'CIF';
        $this->assertTrue($company->save());

        // creamos un cliente de Portugal con nif de Portugal
        $customer = $this->getRandomCustomer();
        $customer->cifnif = 'PT513969144';
        $this->assertTrue($customer->save());
        $address = $customer->getDefaultAddress();
        $address->codpais = 'PRT';
        $this->assertTrue($address->save());

        // creamos una factura
        $invoice = new FacturaCliente();
        $invoice->setSubject($customer);

        $check = $invoice->setIntracomunitaria();
        if (Vies::getLastError() != '') {
            $this->markTestSkipped('Vies service error: ' . Vies::getLastError());
        }
        $this->assertTrue($check);

        // comprobamos que se ha establecido el tipo de operación
        $this->assertEquals(InvoiceOperation::INTRA_COMMUNITY, $invoice->operacion);

        // quitamos la operación
        $invoice->operacion = null;

        // cambiamos el país de la empresa a Colombia
        $company->codpais = 'COL';
        $this->assertTrue($company->save());

        // comprobamos que ya no se puede asignar intracomunitaria
        $this->assertFalse($invoice->setIntracomunitaria());

        // volvemos a poner España
        $company->codpais = 'ESP';
        $this->assertTrue($company->save());

        // cambiamos el país de la factura a Perú
        $invoice->codpais = 'PER';

        // comprobamos que ya no se puede asignar intracomunitaria
        $this->assertFalse($invoice->setIntracomunitaria());

        // eliminamos
        $this->assertTrue($invoice->delete());
        $this->assertTrue($address->delete());
        $this->assertTrue($customer->delete());
    }

    public function testShellUsedGoods(): void
    {
        // creamos una empresa con el régimen de bienes usados
        $company = $this->getRandomCompany();
        $company->regimeniva = RegimenIVA::TAX_SYSTEM_USED_GOODS;
        $this->assertTrue($company->save());

        // creamos un producto de segunda mano
        $product = $this->getRandomProduct();
        $product->tipo = ProductType::SECOND_HAND;
        $product->ventasinstock = true;
        $this->assertTrue($product->save());

        // le asignamos un coste de 900 y un precio de 1200 a su variante
        foreach ($product->getVariants() as $variant) {
            $variant->coste = 900;
            $variant->precio = 1200;
            $this->assertTrue($variant->save());
            break;
        }

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save());

        // creamos una factura
        $invoice = new FacturaCliente();
        foreach ($company->getWarehouses() as $warehouse) {
            $invoice->setWarehouse($warehouse->codalmacen);
            break;
        }
        $invoice->setSubject($customer);
        $this->assertTrue($invoice->save());

        // añadimos el producto
        $firstLine = $invoice->getNewProductLine($product->referencia);
        $firstLine->cantidad = 1;
        $this->assertTrue($firstLine->save());

        // recalculamos
        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true));

        // comprobamos los totales
        $this->assertEquals(1200, $invoice->neto);
        $this->assertEquals(63, $invoice->totaliva);
        $this->assertEquals(0, $invoice->totalirpf);
        $this->assertEquals(1263, $invoice->total);

        // eliminamos
        $this->assertTrue($invoice->delete());
        $this->assertTrue($customer->getDefaultAddress()->delete());
        $this->assertTrue($customer->delete());
        $this->assertTrue($product->delete());
        $this->assertTrue($company->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
