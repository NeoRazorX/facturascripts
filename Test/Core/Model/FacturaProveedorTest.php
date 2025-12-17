<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Lib\FacturaProveedorRenumber;
use FacturaScripts\Core\Model\FacturaProveedor;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class FacturaProveedorTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    const PRODUCT1_COST = 99.9;
    const PRODUCT1_QUANTITY = 10;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
        self::removeTaxRegularization();
    }



    /**
     * Prueba que no se puede crear una factura sin proveedor.
     */
    public function testCanNotCreateInvoiceWithoutSupplier(): void
    {
        $invoice = new FacturaProveedor();
        $this->assertFalse($invoice->save(), 'can-create-invoice-without-supplier');
    }


    /**
     * Prueba que al añadir una línea de producto a la factura, se actualiza el stock del producto.
     */
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
        $product->reload();
        $this->assertEquals(self::PRODUCT1_QUANTITY, $product->stockfis, 'bad-product1-stock');

        // eliminamos
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertFalse($firstLine->exists(), 'deleted-line-invoice-still-found');
        $this->assertTrue($supplier->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($supplier->delete(), 'cant-delete-supplier');

        // comprobamos que el stock del producto ha desaparecido
        $product->reload();
        $this->assertEquals(0, $product->stockfis, 'bad-product1-stock-end');

        // eliminamos el producto
        $this->assertTrue($product->delete(), 'cant-delete-producto');
    }

    /**
     * Prueba que no se puede modificar o eliminar una factura en estado no editable.
     */
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

    /**
     * Prueba la re-numeración de facturas de proveedor.
     */
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

    /**
     * Prueba que la fecha del asiento sea igual a la fecha de devengo de la factura.
     */
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

    /**
     * Prueba las longitudes máximas de las propiedades de la factura de proveedor.
     */
    public function testPropertiesLength(): void
    {
        // Definir los campos a validar: campo => [longitud_máxima, longitud_invalida]
        $campos = [
            'cifnif' => [30, 31],
            'codigo' => [20, 21],
            'codigorect' => [20, 21],
            'nombre' => [100, 101],
            'numproveedor' => [50, 51],
            'operacion' => [50, 51],
        ];

        // creamos un proveedor
        $supplier = $this->getRandomSupplier();
        $this->assertTrue($supplier->save(), 'cant-create-supplier');

        foreach ($campos as $campo => [$valido, $invalido]) {
            // Creamos el modelo
            $model = new FacturaProveedor();

            // campo obligatorio (not null)
            $model->setSubject($supplier);

            // Asignamos el valor inválido en el campo a probar
            $model->{$campo} = Tools::randomString($invalido);
            $this->assertFalse($model->save(), "can-save-facturaProveedor-bad-{$campo}");

            // Corregimos el campo y comprobamos que ahora sí se puede guardar
            $model->{$campo} = Tools::randomString($valido);
            $this->assertTrue($model->save(), "cannot-save-facturaProveedor-fixed-{$campo}");

            // Limpiar
            $this->assertTrue($model->delete(), "cannot-delete-facturaProveedor-{$campo}");
        }

        // eliminamos el proveedor
        $this->assertTrue($supplier->getDefaultAddress()->delete());
        $this->assertTrue($supplier->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
