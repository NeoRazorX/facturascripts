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

use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Core\Model\FacturaCliente;
use FacturaScripts\Core\Model\Stock;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class FacturaClienteTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    const PRODUCT1_PRICE = 66.1;
    const PRODUCT1_QUANTITY = 3;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
        self::removeTaxRegularization();
    }

    /**
     * Prueba que no se puede crear una factura sin cliente.
     */
    public function testCanNotCreateInvoiceWithoutCustomer(): void
    {
        $invoice = new FacturaCliente();
        $this->assertFalse($invoice->save(), 'can-create-invoice-without-customer');
    }

    /**
     * Prueba que no se puede crear una factura con un ejercicio incorrecto.
     */
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

    /**
     * Prueba que no se puede crear una factura con un código duplicado.
     */
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

    /**
     * Prueba que no se puede crear una factura con un número duplicado.
     */
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

    /**
     * Prueba que al añadir una línea de producto se actualiza el stock.
     */
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
        $product->reload();
        $this->assertEquals(0, $product->stockfis, 'bad-product1-stock');

        // eliminamos
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertFalse($firstLine->exists(), 'deleted-line-invoice-still-found');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($customer->delete(), 'cant-delete-customer');

        // comprobamos que se restaura el stock del producto
        $product->reload();
        $this->assertEquals(self::PRODUCT1_QUANTITY, $product->stockfis, 'bad-product1-stock-end');

        // eliminamos el producto
        $this->assertTrue($product->delete(), 'cant-delete-product');
    }

    /**
     * Prueba que no se puede modificar o eliminar una factura en estado no editable.
     */
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

    /**
     * Prueba la creación de una factura con fecha anterior a otra ya existente.
     */
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

    /**
     * Prueba la creación de una factura con fecha anterior a otra ya existente en la misma serie.
     */
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

    /**
     * Prueba la actualización de una factura para ponerle una fecha anterior a otra ya existente.
     */
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

    /**
     * Prueba la actualización de una factura para ponerle una fecha anterior a otra ya existente en la misma serie.
     */
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

    /**
     * Prueba que la fecha del asiento sea igual a la fecha de devengo de la factura.
     */
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

    /**
     * Prueba las longitudes máximas de las propiedades de la factura.
     */
    public function testPropertiesLength(): void
    {
        // Definir los campos a validar: campo => [longitud_máxima, longitud_invalida]
        $campos = [
            'apartado' => [10, 11],
            'cifnif' => [30, 31],
            'ciudad' => [100, 101],
            'codigo' => [20, 21],
            'codigorect' => [20, 21],
            'codigoenv' => [200, 201],
            'codpais' => [20, 21],
            'codpostal' => [10, 11],
            'direccion' => [200, 201],
            'nombrecliente' => [100, 101],
            'operacion' => [50, 51],
            'provincia' => [100, 101],
        ];

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'cant-create-customer');

        foreach ($campos as $campo => [$valido, $invalido]) {
            // Creamos el modelo
            $model = new FacturaCliente();

            // campo obligatorio (not null)
            $model->setSubject($customer);

            // Asignamos el valor inválido en el campo a probar
            $model->{$campo} = Tools::randomString($invalido);
            $this->assertFalse($model->save(), "can-save-facturaCliente-bad-{$campo}");

            // Corregimos el campo y comprobamos que ahora sí se puede guardar
            $model->{$campo} = Tools::randomString($valido);
            $this->assertTrue($model->save(), "cannot-save-facturaCliente-fixed-{$campo}");

            // Limpiar
            $this->assertTrue($model->delete(), "cannot-delete-facturaCliente-{$campo}");
        }

        // eliminamos
        $this->assertTrue($customer->getDefaultAddress()->delete());
        $this->assertTrue($customer->delete());;
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
