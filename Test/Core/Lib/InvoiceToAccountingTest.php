<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Lib;

use FacturaScripts\Core\DataSrc\Paises;
use FacturaScripts\Core\Lib\Accounting\InvoiceToAccounting;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Lib\Calculator;
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\Cuenta;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\Partida;
use FacturaScripts\Dinamic\Model\Subcuenta;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class InvoiceToAccountingTest extends TestCase
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

    /**
     * Test para el bug de tarea3282
     * 
     * Prueba de regresión: al contabilizar una factura de venta con una línea de texto
     * (importe cero) y todos los productos vinculados a una subcuenta personalizada (700.1),
     * la subcuenta por defecto (700.0) no debe aparecer en el asiento con importe cero.
     */
    public function testSalesInvoiceWithTextLineHasNoZeroAccountingEntries(): void
    {
        // tiene que ser un test con contabilidad española para probar estos pasos
        if (Paises::default()->codpais !== 'ESP') {
            $this->markTestSkipped('Test only applicable to the Spanish accounting plan.');
        }

        // Creamos el cliente y la factura para obtener el codejercicio
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'cant-save-customer');

        $invoice = new FacturaCliente();
        $invoice->setSubject($customer);
        $this->assertTrue($invoice->save(), 'cant-save-invoice');

        // Garantizamos que el ejercicio de la factura tiene el plan contable instalado
        // (si no está esta linea, falla el test porque no encuentra la 700)
        self::installAccountingPlan();

        // Paso 1: Crear la subcuenta 7000000001 (700.1)
        $cuenta700 = new Cuenta();
        $this->assertTrue(
            $cuenta700->loadWhere([
                Where::eq('codejercicio', $invoice->codejercicio),
                Where::eq('codcuenta', '700'),
            ]),
            'cuenta-700-not-found'
        );

        $subcuenta701 = new Subcuenta();
        $subcuenta701->codcuenta = '700';
        $subcuenta701->codejercicio = $invoice->codejercicio;
        $subcuenta701->codsubcuenta = '7000000001';
        $subcuenta701->descripcion = 'Ventas de mercaderías (personalizada)';
        $subcuenta701->idcuenta = $cuenta700->idcuenta;
        $this->assertTrue($subcuenta701->save(), 'cant-save-subcuenta-701');

        // Paso 2: Crear producto y vincularlo a la subcuenta 7000000001
        $product = $this->getRandomProduct();
        $product->codsubcuentaven = '7000000001';
        $product->nostock = true;
        $this->assertTrue($product->save(), 'cant-save-product');

        // Paso 3: Rellenar la factura
        // Línea de producto: contabiliza en 7000000001
        $productLine = $invoice->getNewProductLine($product->referencia);
        $productLine->pvpunitario = 100;
        $this->assertTrue($productLine->save(), 'cant-save-product-line');

        // Línea de texto: sin referencia, solo descripción, cantidad 0 y precio 0
        $textLine = $invoice->getNewLine();
        $textLine->descripcion = 'Línea de texto descriptiva';
        $textLine->cantidad = 0;
        $textLine->pvpunitario = 0;
        $this->assertTrue($textLine->save(), 'cant-save-text-line');

        $lines = $invoice->getLines();
        Calculator::calculate($invoice, $lines, true);

        // Paso 4: Contabilizar y revisar el asiento
        $tool = new InvoiceToAccounting();
        $tool->generate($invoice);
        $this->assertNotEmpty($invoice->idasiento, 'accounting-entry-not-created');

        // Ninguna partida debe tener debe=0 y haber=0 a la vez
        // (7000000000 no debe aparecer porque la línea de texto tiene importe cero)
        $where = [Where::eq('idasiento', $invoice->idasiento)];
        foreach (Partida::all($where) as $partida) {
            $this->assertFalse(
                $partida->debe == 0.0 && $partida->haber == 0.0,
                'Partida con importe cero encontrada en el asiento, subcuenta: ' . $partida->codsubcuenta
            );
        }

        // Limpieza
        $asiento = new Asiento();
        if ($asiento->load($invoice->idasiento)) {
            $asiento->delete();
        }
        $invoice->delete();
        $customer->getDefaultAddress()->delete();
        $customer->delete();
        $product->delete();
        $subcuenta701->delete();
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
