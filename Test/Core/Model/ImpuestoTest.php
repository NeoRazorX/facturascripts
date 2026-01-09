<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Impuesto;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\Calculator;
use FacturaScripts\Dinamic\Model\Almacen;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class ImpuestoTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
        self::removeTaxRegularization();
    }

    public function testCreate(): void
    {
        // creamos un impuesto
        $impuesto = new Impuesto();
        $impuesto->codimpuesto = 'TEST21';
        $impuesto->descripcion = 'Test IVA 21%';
        $impuesto->iva = 21.0;
        $impuesto->recargo = 5.2;
        $this->assertTrue($impuesto->save());

        // comprobamos que existe en la base de datos
        $this->assertTrue($impuesto->exists());

        // comprobamos valores por defecto
        $this->assertTrue($impuesto->activo);
        $this->assertEquals(Impuesto::TYPE_PERCENTAGE, $impuesto->tipo);

        // eliminamos
        $this->assertTrue($impuesto->delete());
    }

    public function testCreateHtml(): void
    {
        // creamos un impuesto con html en los campos
        $impuesto = new Impuesto();
        $impuesto->codimpuesto = 'test';
        $impuesto->descripcion = '<b/>Test';
        $impuesto->iva = 10.0;
        $this->assertTrue($impuesto->save());

        // comprobamos que el html ha sido escapado
        $this->assertEquals(Tools::noHtml('<b/>Test'), $impuesto->descripcion);

        // eliminamos
        $this->assertTrue($impuesto->delete());
    }

    public function testCreateWithInvalidCode(): void
    {
        // creamos un impuesto con código inválido (demasiado largo)
        $impuesto = new Impuesto();
        $impuesto->codimpuesto = 'CODIGO_DEMASIADO_LARGO';
        $impuesto->descripcion = 'Test IVA';
        $impuesto->iva = 10.0;
        $this->assertFalse($impuesto->save(), 'code-too-long-should-fail');

        // código con caracteres inválidos
        $impuesto->codimpuesto = 'TEST@';
        $this->assertFalse($impuesto->save(), 'invalid-characters-should-fail');
    }

    public function testCreateWithoutCode(): void
    {
        // creamos un impuesto sin código
        $impuesto = new Impuesto();
        $impuesto->descripcion = 'Test IVA Sin Código';
        $impuesto->iva = 15.0;
        $this->assertTrue($impuesto->save());

        // comprobamos que se ha asignado un código automáticamente
        $this->assertNotEmpty($impuesto->codimpuesto);

        // eliminamos
        $this->assertTrue($impuesto->delete());
    }

    public function testClear(): void
    {
        // creamos un impuesto y llamamos a clear
        $impuesto = new Impuesto();
        $impuesto->clear();

        // comprobamos valores por defecto después del clear
        $this->assertTrue($impuesto->activo);
        $this->assertEquals(0.0, $impuesto->iva);
        $this->assertEquals(0.0, $impuesto->recargo);
        $this->assertEquals(Impuesto::TYPE_PERCENTAGE, $impuesto->tipo);
    }

    public function testIsDefault(): void
    {
        // obtenemos el código del impuesto por defecto
        $defaultTaxCode = Tools::settings('default', 'codimpuesto');

        // creamos un impuesto que no es el por defecto
        $impuesto = new Impuesto();
        $impuesto->codimpuesto = 'NODEFAULT';
        $impuesto->descripcion = 'No Default Tax';
        $impuesto->iva = 10.0;
        $this->assertTrue($impuesto->save());
        $this->assertFalse($impuesto->isDefault());

        // eliminamos
        $this->assertTrue($impuesto->delete());

        // si existe el impuesto por defecto, lo comprobamos
        if ($defaultTaxCode) {
            $defaultTax = new Impuesto();
            if ($defaultTax->load($defaultTaxCode)) {
                $this->assertTrue($defaultTax->isDefault());
            }
        }
    }

    public function testDeleteDefault(): void
    {
        // obtenemos el código del impuesto por defecto
        $defaultTaxCode = Tools::settings('default', 'codimpuesto');

        if ($defaultTaxCode) {
            $defaultTax = new Impuesto();
            if ($defaultTax->load($defaultTaxCode)) {
                // intentamos eliminar el impuesto por defecto
                $this->assertFalse($defaultTax->delete(), 'default-tax-should-not-be-deletable');
            }
        }
    }

    /**
     * Probar que se puede crear dos impuestos con el mismo iva
     */
    public function testTwoTaxesSameIva(): void
    {
        // creamos un impuesto
        $impuesto = new Impuesto();
        $impuesto->codimpuesto = 'TEST22';
        $impuesto->descripcion = 'Test IVA 22%';
        $impuesto->iva = 22.0;
        $impuesto->recargo = 0;
        $this->assertTrue($impuesto->save(), 'cant-save-impuesto');

        // creamos un impuesto con el mismo iva
        $impuesto2 = new Impuesto();
        $impuesto2->codimpuesto = 'TEST222';
        $impuesto2->descripcion = 'Test IVA 22%';
        $impuesto2->iva = 22.0;
        $impuesto2->recargo = 0;
        $this->assertTrue($impuesto2->save(), 'cant-save-impuesto-2');

        // revisamos que existen los dos con el mismo iva
        $this->assertTrue($impuesto->exists());
        $this->assertTrue($impuesto2->exists());
        $this->assertEquals($impuesto->iva, $impuesto2->iva);

        // limpiamos
        $this->assertTrue($impuesto->delete());
        $this->assertTrue($impuesto2->delete());
    }

    /**
     * Si se crea un impuesto de tipo porcentaje y 10 de iva, al hacer una compra con una 
     * línea con ese impuesto, cantidad 2 y precio 50, el totaliva es 10.
     */
    public function testTotalIva()
    {
        // creamos un impuesto tipo porcentaje y 10 de iva
        $impuesto = new Impuesto();
        $impuesto->codimpuesto = 'TEST10';
        $impuesto->descripcion = 'Test IVA 10%';
        $impuesto->tipo = Impuesto::TYPE_PERCENTAGE;
        $impuesto->iva = 10.0;
        $impuesto->recargo = 0.0;
        $this->assertTrue($impuesto->save(), 'cant-save-impuesto');

        // creamos el cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // creamos la factura
        $invoice = new FacturaCliente();
        $invoice->setSubject($customer);
        $invoice->numero2 = 'ABC-DEF';
        $invoice->observaciones = 'TEST';
        $this->assertTrue($invoice->save(), 'cant-create-invoice');

        // añadimos una línea
        $line = $invoice->getNewLine();
        $line->descripcion = 'Test';
        $line->codimpuesto = $impuesto->codimpuesto;
        $line->cantidad = 50.0;
        $line->pvpunitario = 2;
        $this->assertTrue($line->save(), 'cant-save-first-line');

        // recalculamos
        $lines = $invoice->getLines();
        var_dump($lines);
        $this->assertTrue(Calculator::calculate($invoice, $lines, true), 'cant-update-invoice');


        $imp = new Impuesto();
        $imp->load($line->codimpuesto);
        echo "EL IVA: -------------------------------: ";
        var_dump($imp->iva);

        // comprobar que total iva es correcto
        $this->assertEquals(10.0, $invoice->totaliva, 'bad-total-iva');

        // limpiamos
        $this->assertTrue($line->delete(), 'cant-delete-line');
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertTrue($impuesto->delete(), 'cant-delete-impuesto');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
