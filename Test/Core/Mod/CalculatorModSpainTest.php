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

namespace FacturaScripts\Test\Core\Mod;

use FacturaScripts\Core\DataSrc\Impuestos;
use FacturaScripts\Core\DataSrc\Series;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Lib\InvoiceOperation;
use FacturaScripts\Core\Lib\ProductType;
use FacturaScripts\Core\Lib\RegimenIVA;
use FacturaScripts\Core\Lib\TaxExceptions;
use FacturaScripts\Core\Model\PresupuestoCliente;
use FacturaScripts\Core\Model\PresupuestoProveedor;
use FacturaScripts\Core\Model\Serie;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class CalculatorModSpainTest extends TestCase
{
    use RandomDataTrait;

    protected function setUp(): void
    {
        parent::setUp();
        if (Tools::config('codpais') !== 'ESP') {
            $this->markTestSkipped('country-is-not-spain');
        }
    }

    public function testIntraCommunitySale(): void
    {
        $doc = new PresupuestoCliente();
        $doc->operacion = InvoiceOperation::INTRA_COMMUNITY;

        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        $line->iva = 21;

        $lines = [$line];
        $this->assertTrue(Calculator::calculate($doc, $lines, false));

        // la línea debe tener IVA 0% con exención E5
        $this->assertEquals(0.0, $lines[0]->iva, 'bad-line-iva');
        $this->assertEquals(0.0, $lines[0]->recargo, 'bad-line-recargo');
        $this->assertEquals(Impuestos::get('IVA0')->codimpuesto, $lines[0]->codimpuesto, 'bad-line-codimpuesto');
        $this->assertEquals(TaxExceptions::ES_TAX_EXCEPTION_25, $lines[0]->excepcioniva, 'bad-line-excepcioniva');

        // comprobamos el documento
        $this->assertEquals(100.0, $doc->neto, 'bad-neto');
        $this->assertEquals(100.0, $doc->total, 'bad-total');
        $this->assertEquals(0.0, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(0.0, $doc->totalrecargo, 'bad-totalrecargo');
    }

    public function testIntraCommunityPurchase(): void
    {
        $doc = new PresupuestoProveedor();
        $doc->operacion = InvoiceOperation::INTRA_COMMUNITY;

        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        $line->iva = 21;

        $lines = [$line];
        $this->assertTrue(Calculator::calculate($doc, $lines, false));

        // la línea mantiene el IVA para la contabilidad (autorepercusión)
        $this->assertEquals(21.0, $lines[0]->iva, 'bad-line-iva');
        $this->assertEquals(0.0, $lines[0]->recargo, 'bad-line-recargo');
        $this->assertEquals(TaxExceptions::ES_TAX_EXCEPTION_84, $lines[0]->excepcioniva, 'bad-line-excepcioniva');

        // el IVA se neutraliza en el total del documento
        $this->assertEquals(100.0, $doc->neto, 'bad-neto');
        $this->assertEquals(100.0, $doc->total, 'bad-total');
        $this->assertEquals(0.0, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(0.0, $doc->totalrecargo, 'bad-totalrecargo');
    }

    public function testIntraCommunityServicesSale(): void
    {
        $doc = new PresupuestoCliente();
        $doc->operacion = InvoiceOperation::INTRA_COMMUNITY_SERVICES;

        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        $line->iva = 21;

        $lines = [$line];
        $this->assertTrue(Calculator::calculate($doc, $lines, false));

        // no sujeta por reglas de localización, exención N2
        $this->assertEquals(0.0, $lines[0]->iva, 'bad-line-iva');
        $this->assertEquals(0.0, $lines[0]->recargo, 'bad-line-recargo');
        $this->assertEquals(TaxExceptions::ES_TAX_EXCEPTION_68_70, $lines[0]->excepcioniva, 'bad-line-excepcioniva');

        // comprobamos el documento
        $this->assertEquals(100.0, $doc->neto, 'bad-neto');
        $this->assertEquals(100.0, $doc->total, 'bad-total');
        $this->assertEquals(0.0, $doc->totaliva, 'bad-totaliva');
    }

    public function testIntraCommunityServicesPurchase(): void
    {
        $doc = new PresupuestoProveedor();
        $doc->operacion = InvoiceOperation::INTRA_COMMUNITY_SERVICES;

        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        $line->iva = 21;

        $lines = [$line];
        $this->assertTrue(Calculator::calculate($doc, $lines, false));

        // ISP: mantiene IVA para autorepercusión
        $this->assertEquals(21.0, $lines[0]->iva, 'bad-line-iva');
        $this->assertEquals(0.0, $lines[0]->recargo, 'bad-line-recargo');
        $this->assertEquals(TaxExceptions::ES_TAX_EXCEPTION_84, $lines[0]->excepcioniva, 'bad-line-excepcioniva');

        // el IVA se neutraliza en el total del documento
        $this->assertEquals(100.0, $doc->neto, 'bad-neto');
        $this->assertEquals(100.0, $doc->total, 'bad-total');
        $this->assertEquals(0.0, $doc->totaliva, 'bad-totaliva');
    }

    public function testReverseChargeSale(): void
    {
        $doc = new PresupuestoCliente();
        $doc->operacion = InvoiceOperation::REVERSE_CHARGE;

        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        $line->iva = 21;

        $lines = [$line];
        $this->assertTrue(Calculator::calculate($doc, $lines, false));

        // ISP doméstico: el vendedor no cobra IVA
        $this->assertEquals(0.0, $lines[0]->iva, 'bad-line-iva');
        $this->assertEquals(0.0, $lines[0]->recargo, 'bad-line-recargo');
        $this->assertEquals(TaxExceptions::ES_TAX_EXCEPTION_84, $lines[0]->excepcioniva, 'bad-line-excepcioniva');

        // comprobamos el documento
        $this->assertEquals(100.0, $doc->neto, 'bad-neto');
        $this->assertEquals(100.0, $doc->total, 'bad-total');
        $this->assertEquals(0.0, $doc->totaliva, 'bad-totaliva');
    }

    public function testReverseChargePurchase(): void
    {
        $doc = new PresupuestoProveedor();
        $doc->operacion = InvoiceOperation::REVERSE_CHARGE;

        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        $line->iva = 21;

        $lines = [$line];
        $this->assertTrue(Calculator::calculate($doc, $lines, false));

        // ISP doméstico: mantiene IVA para autorepercusión
        $this->assertEquals(21.0, $lines[0]->iva, 'bad-line-iva');
        $this->assertEquals(0.0, $lines[0]->recargo, 'bad-line-recargo');
        $this->assertEquals(TaxExceptions::ES_TAX_EXCEPTION_84, $lines[0]->excepcioniva, 'bad-line-excepcioniva');

        // el IVA se neutraliza en el total del documento
        $this->assertEquals(100.0, $doc->neto, 'bad-neto');
        $this->assertEquals(100.0, $doc->total, 'bad-total');
        $this->assertEquals(0.0, $doc->totaliva, 'bad-totaliva');
    }

    public function testExportSale(): void
    {
        $doc = new PresupuestoCliente();
        $doc->operacion = InvoiceOperation::EXPORT;

        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        $line->iva = 21;

        $lines = [$line];
        $this->assertTrue(Calculator::calculate($doc, $lines, false));

        // exportación: IVA 0% con exención E2
        $this->assertEquals(0.0, $lines[0]->iva, 'bad-line-iva');
        $this->assertEquals(0.0, $lines[0]->recargo, 'bad-line-recargo');
        $this->assertEquals(TaxExceptions::ES_TAX_EXCEPTION_21, $lines[0]->excepcioniva, 'bad-line-excepcioniva');

        // comprobamos el documento
        $this->assertEquals(100.0, $doc->neto, 'bad-neto');
        $this->assertEquals(100.0, $doc->total, 'bad-total');
        $this->assertEquals(0.0, $doc->totaliva, 'bad-totaliva');
    }

    public function testImportPurchase(): void
    {
        $doc = new PresupuestoProveedor();
        $doc->operacion = InvoiceOperation::IMPORT;

        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        $line->iva = 21;

        $lines = [$line];
        $this->assertTrue(Calculator::calculate($doc, $lines, false));

        // importación: IVA 0% (se liquida en aduanas)
        $this->assertEquals(0.0, $lines[0]->iva, 'bad-line-iva');
        $this->assertEquals(0.0, $lines[0]->recargo, 'bad-line-recargo');

        // comprobamos el documento
        $this->assertEquals(100.0, $doc->neto, 'bad-neto');
        $this->assertEquals(100.0, $doc->total, 'bad-total');
        $this->assertEquals(0.0, $doc->totaliva, 'bad-totaliva');
    }

    public function testGoldRegimeSale(): void
    {
        // creamos un cliente con régimen de oro de inversión
        $subject = $this->getRandomCustomer();
        $subject->regimeniva = RegimenIVA::TAX_SYSTEM_GOLD;
        $this->assertTrue($subject->save(), 'can-not-save-customer');

        $doc = new PresupuestoCliente();
        $this->assertTrue($doc->setSubject($subject), 'can-not-assign-customer');

        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        $line->iva = 21;

        $lines = [$line];
        $this->assertTrue(Calculator::calculate($doc, $lines, false));

        // oro de inversión: exento de IVA con excepción E6
        $this->assertNull($lines[0]->codimpuesto, 'bad-line-codimpuesto');
        $this->assertEquals(0.0, $lines[0]->iva, 'bad-line-iva');
        $this->assertEquals(0.0, $lines[0]->recargo, 'bad-line-recargo');
        $this->assertEquals(TaxExceptions::ES_TAX_EXCEPTION_OTHER, $lines[0]->excepcioniva, 'bad-line-excepcioniva');

        // comprobamos el documento
        $this->assertEquals(100.0, $doc->neto, 'bad-neto');
        $this->assertEquals(100.0, $doc->total, 'bad-total');
        $this->assertEquals(0.0, $doc->totaliva, 'bad-totaliva');

        // eliminamos
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'cliente-cant-delete');
    }

    public function testAgrarianPurchase(): void
    {
        // creamos un proveedor con régimen agrario (REAGYP)
        $subject = $this->getRandomSupplier();
        $subject->regimeniva = RegimenIVA::TAX_SYSTEM_AGRARIAN;
        $this->assertTrue($subject->save(), 'can-not-save-supplier');

        $doc = new PresupuestoProveedor();
        $this->assertTrue($doc->setSubject($subject), 'can-not-assign-supplier');

        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        $line->iva = 21;
        $line->recargo = 5.2;

        $lines = [$line];
        $this->assertTrue(Calculator::calculate($doc, $lines, false));

        // REAGYP: mantiene IVA pero elimina recargo, excepción E6
        $this->assertEquals(21.0, $lines[0]->iva, 'bad-line-iva');
        $this->assertEquals(0.0, $lines[0]->recargo, 'bad-line-recargo');
        $this->assertEquals(TaxExceptions::ES_TAX_EXCEPTION_OTHER, $lines[0]->excepcioniva, 'bad-line-excepcioniva');

        // comprobamos el documento
        $this->assertEquals(100.0, $doc->neto, 'bad-neto');
        $this->assertEquals(121.0, $doc->total, 'bad-total');
        $this->assertEquals(21.0, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(0.0, $doc->totalrecargo, 'bad-totalrecargo');

        // eliminamos
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'proveedor-cant-delete');
    }

    public function testUsedGoodsPurchase(): void
    {
        // obtenemos IVA21
        $tax = Impuestos::get('IVA21');
        if (false === $tax->exists()) {
            $this->markTestSkipped('IVA21-not-found');
        }

        // creamos un producto de segunda mano
        $product = $this->getRandomProduct();
        $product->codimpuesto = $tax->codimpuesto;
        $product->tipo = ProductType::SECOND_HAND;
        $this->assertTrue($product->save(), 'can-not-save-product');

        // cambiamos el régimen de la empresa a bienes usados
        $doc = new PresupuestoProveedor();
        $company = $doc->getCompany();
        $originalRegimen = $company->regimeniva;
        $company->regimeniva = RegimenIVA::TAX_SYSTEM_USED_GOODS;
        $this->assertTrue($company->save(), 'can-not-save-company');

        // creamos un proveedor normal
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save(), 'can-not-save-supplier');
        $this->assertTrue($doc->setSubject($subject), 'can-not-assign-supplier');
        $this->assertTrue($doc->save(), 'can-not-save-doc');

        // añadimos el producto
        $line = $doc->getNewProductLine($product->referencia);
        $lines = [$line];
        $this->assertTrue(Calculator::calculate($doc, $lines, false));

        // compra de bienes usados: sin IVA (se aplica REBU en la venta)
        $this->assertNull($lines[0]->codimpuesto, 'bad-line-codimpuesto');
        $this->assertEquals(0.0, $lines[0]->iva, 'bad-line-iva');
        $this->assertEquals(0.0, $lines[0]->recargo, 'bad-line-recargo');

        // comprobamos el documento
        $this->assertEquals(0.0, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(0.0, $doc->totalrecargo, 'bad-totalrecargo');

        // restauramos el régimen original de la empresa
        $company->regimeniva = $originalRegimen;
        $this->assertTrue($company->save(), 'can-not-restore-company');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-doc');
        $this->assertTrue($product->delete(), 'can-not-delete-product');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'proveedor-cant-delete');
    }

    public function testUsedGoodsSale(): void
    {
        // obtenemos IVA21
        $tax = Impuestos::get('IVA21');
        if (false === $tax->exists()) {
            $this->markTestSkipped('IVA21-not-found');
        }

        // creamos un producto de segunda mano
        $product = $this->getRandomProduct();
        $product->codimpuesto = $tax->codimpuesto;
        $product->tipo = ProductType::SECOND_HAND;
        $this->assertTrue($product->save(), 'can-not-save-product');

        // cambiamos el régimen de la empresa a bienes usados
        $doc = new PresupuestoCliente();
        $company = $doc->getCompany();
        $originalRegimen = $company->regimeniva;
        $company->regimeniva = RegimenIVA::TAX_SYSTEM_USED_GOODS;
        $this->assertTrue($company->save(), 'can-not-save-company');

        // creamos un cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer');
        $this->assertTrue($doc->setSubject($subject), 'can-not-assign-customer');
        $this->assertTrue($doc->save(), 'can-not-save-doc');

        // añadimos el producto: pvp 200, coste 150, margen 50
        $line = $doc->getNewProductLine($product->referencia);
        $line->cantidad = 1;
        $line->pvpunitario = 200;
        $line->coste = 150;
        $lines = [$line];
        $this->assertTrue(Calculator::calculate($doc, $lines, false));

        // REBU: IVA solo sobre el margen (50 * 21% = 10.5)
        $this->assertEquals(200.0, $doc->neto, 'bad-neto');
        $this->assertEquals(10.5, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(210.5, $doc->total, 'bad-total');
        $this->assertEquals(0.0, $doc->totalrecargo, 'bad-totalrecargo');

        // restauramos el régimen original de la empresa
        $company->regimeniva = $originalRegimen;
        $this->assertTrue($company->save(), 'can-not-restore-company');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-doc');
        $this->assertTrue($product->delete(), 'can-not-delete-product');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'cliente-cant-delete');
    }

    public function testTravelAgencySale(): void
    {
        // cambiamos el régimen de la empresa a agencia de viajes
        $doc = new PresupuestoCliente();
        $company = $doc->getCompany();
        $originalRegimen = $company->regimeniva;
        $company->regimeniva = RegimenIVA::TAX_SYSTEM_TRAVEL;
        $this->assertTrue($company->save(), 'can-not-save-company');

        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 200;
        $line->coste = 120;
        $line->iva = 21;

        $lines = [$line];
        $this->assertTrue(Calculator::calculate($doc, $lines, false));

        // agencia de viajes: IVA sobre el margen (80 * 21% = 16.8)
        $this->assertEquals(200.0, $doc->neto, 'bad-neto');
        $this->assertEquals(16.8, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(216.8, $doc->total, 'bad-total');
        $this->assertEquals(0.0, $doc->totalrecargo, 'bad-totalrecargo');

        // restauramos el régimen original de la empresa
        $company->regimeniva = $originalRegimen;
        $this->assertTrue($company->save(), 'can-not-restore-company');
    }

    public function testCompanyReSupplierPurchase(): void
    {
        // cambiamos el régimen de la empresa a recargo de equivalencia
        $doc = new PresupuestoProveedor();
        $company = $doc->getCompany();
        $originalRegimen = $company->regimeniva;
        $company->regimeniva = RegimenIVA::TAX_SYSTEM_SURCHARGE;
        $this->assertTrue($company->save(), 'can-not-save-company');

        // creamos un proveedor normal
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save(), 'can-not-create-supplier');
        $this->assertTrue($doc->setSubject($subject), 'can-not-assign-supplier');

        // primera línea
        $line1 = $doc->getNewLine();
        $line1->cantidad = 2;
        $line1->pvpunitario = 100;
        $line1->iva = 21;
        $line1->recargo = 5.2;

        $lines = [$line1];
        $this->assertTrue(Calculator::calculate($doc, $lines, false));

        // comprobamos el documento: el recargo se aplica porque la empresa tiene RE
        $this->assertEquals(200.0, $doc->neto, 'bad-neto');
        $this->assertEquals(200.0, $doc->netosindto, 'bad-netosindto');
        $this->assertEquals(252.4, $doc->total, 'bad-total');
        $this->assertEquals(42.0, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(0.0, $doc->totalirpf, 'bad-totalirpf');
        $this->assertEquals(10.4, $doc->totalrecargo, 'bad-totalrecargo');
        $this->assertEquals(0.0, $doc->totalsuplidos, 'bad-totalsuplidos');

        // restauramos el régimen original de la empresa
        $company->regimeniva = $originalRegimen;
        $this->assertTrue($company->save(), 'can-not-restore-company');

        // eliminamos
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'proveedor-cant-delete');
    }

    public function testGetSubtotalsPurchaseIntraCommunityWithoutPreviousCalculate(): void
    {
        $doc = new PresupuestoProveedor();
        $doc->operacion = InvoiceOperation::INTRA_COMMUNITY;

        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        $line->iva = 21;
        $line->recargo = 0;

        $this->assertTrue(Calculator::calculateLine($doc, $line), 'can-not-calculate-line');

        $subtotals = Calculator::getSubtotals($doc, [$line]);

        $this->assertEquals(100.0, $subtotals['neto'], 'bad-neto');
        $this->assertEquals(0.0, $subtotals['totaliva'], 'bad-totaliva');
        $this->assertEquals(0.0, $subtotals['iva']['21|0']['totaliva'], 'bad-subtotal-iva');
    }

    public function testProductLineWithCustomerRe(): void
    {
        // obtenemos el impuesto IVA21%
        $tax = Impuestos::get('IVA21');
        if (false === $tax->exists()) {
            $this->markTestSkipped('IVA21-not-found');
        }

        // creamos un producto con IVA 21%
        $product = $this->getRandomProduct();
        $product->codimpuesto = $tax->codimpuesto;
        $this->assertTrue($product->save(), 'can-not-save-product');

        // creamos un cliente con recargo de equivalencia
        $subject = $this->getRandomCustomer();
        $subject->regimeniva = RegimenIVA::TAX_SYSTEM_SURCHARGE;
        $this->assertTrue($subject->save(), 'can-not-save-customer');

        // creamos un presupuesto
        $doc = new PresupuestoCliente();
        $this->assertTrue($doc->setSubject($subject), 'can-not-assign-customer');
        $this->assertTrue($doc->save(), 'can-not-save-doc');

        // añadimos el producto: la línea debe tener recargo porque el cliente tiene RE
        $line = $doc->getNewProductLine($product->referencia);
        $this->assertEquals($tax->codimpuesto, $line->codimpuesto);
        $this->assertEquals($tax->iva, $line->iva);
        $this->assertEquals($tax->recargo, $line->recargo, 'bad-recargo');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-doc');
        $this->assertTrue($product->delete(), 'can-not-delete-product');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'can-not-delete-contact');
        $this->assertTrue($subject->delete(), 'can-not-delete-customer');
    }

    public function testProductLineWithCompanyRe(): void
    {
        // obtenemos el impuesto IVA21%
        $tax = Impuestos::get('IVA21');
        if (false === $tax->exists()) {
            $this->markTestSkipped('IVA21-not-found');
        }

        // creamos un producto con IVA 21%
        $product = $this->getRandomProduct();
        $product->codimpuesto = $tax->codimpuesto;
        $this->assertTrue($product->save(), 'can-not-save-product');

        // cambiamos el régimen de la empresa a RE
        $doc = new PresupuestoProveedor();
        $company = $doc->getCompany();
        $originalRegimen = $company->regimeniva;
        $company->regimeniva = RegimenIVA::TAX_SYSTEM_SURCHARGE;
        $this->assertTrue($company->save(), 'can-not-save-company');

        // creamos un proveedor normal
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save(), 'can-not-save-supplier');
        $this->assertTrue($doc->setSubject($subject), 'can-not-assign-supplier');
        $this->assertTrue($doc->save(), 'can-not-save-doc');

        // añadimos el producto: la línea debe tener recargo porque la empresa tiene RE
        $line = $doc->getNewProductLine($product->referencia);
        $this->assertEquals($tax->codimpuesto, $line->codimpuesto);
        $this->assertEquals($tax->iva, $line->iva);
        $this->assertEquals($tax->recargo, $line->recargo, 'bad-recargo');

        // restauramos el régimen original de la empresa
        $company->regimeniva = $originalRegimen;
        $this->assertTrue($company->save(), 'can-not-restore-company');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-doc');
        $this->assertTrue($product->delete(), 'can-not-delete-product');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'can-not-delete-contact');
        $this->assertTrue($subject->delete(), 'can-not-delete-supplier');
    }

    public function testUsedGoodsSaleNegativeMargin(): void
    {
        // obtenemos IVA21
        $tax = Impuestos::get('IVA21');
        if (false === $tax->exists()) {
            $this->markTestSkipped('IVA21-not-found');
        }

        // creamos un producto de segunda mano
        $product = $this->getRandomProduct();
        $product->codimpuesto = $tax->codimpuesto;
        $product->tipo = ProductType::SECOND_HAND;
        $this->assertTrue($product->save(), 'can-not-save-product');

        // cambiamos el régimen de la empresa a bienes usados
        $doc = new PresupuestoCliente();
        $company = $doc->getCompany();
        $originalRegimen = $company->regimeniva;
        $company->regimeniva = RegimenIVA::TAX_SYSTEM_USED_GOODS;
        $this->assertTrue($company->save(), 'can-not-save-company');

        // creamos un cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer');
        $this->assertTrue($doc->setSubject($subject), 'can-not-assign-customer');
        $this->assertTrue($doc->save(), 'can-not-save-doc');

        // añadimos el producto: pvp 80, coste 100, margen -20
        $line = $doc->getNewProductLine($product->referencia);
        $line->cantidad = 1;
        $line->pvpunitario = 80;
        $line->coste = 100;
        $lines = [$line];
        $this->assertTrue(Calculator::calculate($doc, $lines, false));

        // REBU con margen negativo: no hay IVA, el total es el pvp
        $this->assertEquals(80.0, $doc->neto, 'bad-neto');
        $this->assertEquals(0.0, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(80.0, $doc->total, 'bad-total');
        $this->assertEquals(0.0, $doc->totalrecargo, 'bad-totalrecargo');

        // restauramos el régimen original de la empresa
        $company->regimeniva = $originalRegimen;
        $this->assertTrue($company->save(), 'can-not-restore-company');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-doc');
        $this->assertTrue($product->delete(), 'can-not-delete-product');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'cliente-cant-delete');
    }

    public function testUsedGoodsSaleRectificativeNegativeMargin(): void
    {
        // obtenemos IVA21
        $tax = Impuestos::get('IVA21');
        if (false === $tax->exists()) {
            $this->markTestSkipped('IVA21-not-found');
        }

        // creamos una serie rectificativa
        $serie = new Serie();
        $serie->codserie = 'RT';
        if (false === $serie->exists()) {
            $serie->descripcion = 'Rectificativas test';
            $serie->tipo = 'R';
            $this->assertTrue($serie->save(), 'can-not-save-serie');
            Series::clear();
        }

        // creamos un producto de segunda mano
        $product = $this->getRandomProduct();
        $product->codimpuesto = $tax->codimpuesto;
        $product->tipo = ProductType::SECOND_HAND;
        $this->assertTrue($product->save(), 'can-not-save-product');

        // cambiamos el régimen de la empresa a bienes usados
        $doc = new PresupuestoCliente();
        $doc->codserie = $serie->codserie;
        $company = $doc->getCompany();
        $originalRegimen = $company->regimeniva;
        $company->regimeniva = RegimenIVA::TAX_SYSTEM_USED_GOODS;
        $this->assertTrue($company->save(), 'can-not-save-company');

        // creamos un cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer');
        $this->assertTrue($doc->setSubject($subject), 'can-not-assign-customer');
        $this->assertTrue($doc->save(), 'can-not-save-doc');

        // nota de crédito: cantidad -1, pvp 200, coste 150
        // margen = (-200) - (-150) = -50
        $line = $doc->getNewProductLine($product->referencia);
        $line->cantidad = -1;
        $line->pvpunitario = 200;
        $line->coste = 150;
        $lines = [$line];
        $this->assertTrue(Calculator::calculate($doc, $lines, false));

        // REBU rectificativa: IVA sobre el margen negativo (-50 * 21% = -10.5)
        $this->assertEquals(-200.0, $doc->neto, 'bad-neto');
        $this->assertEquals(-10.5, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(-210.5, $doc->total, 'bad-total');

        // restauramos el régimen original de la empresa
        $company->regimeniva = $originalRegimen;
        $this->assertTrue($company->save(), 'can-not-restore-company');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-doc');
        $this->assertTrue($product->delete(), 'can-not-delete-product');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'cliente-cant-delete');
        $serie->delete();
    }

    public function testTravelAgencySaleNegativeMargin(): void
    {
        // cambiamos el régimen de la empresa a agencia de viajes
        $doc = new PresupuestoCliente();
        $company = $doc->getCompany();
        $originalRegimen = $company->regimeniva;
        $company->regimeniva = RegimenIVA::TAX_SYSTEM_TRAVEL;
        $this->assertTrue($company->save(), 'can-not-save-company');

        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 80;
        $line->coste = 100;
        $line->iva = 21;

        $lines = [$line];
        $this->assertTrue(Calculator::calculate($doc, $lines, false));

        // agencia de viajes con margen negativo: no hay IVA, el total es el pvp
        $this->assertEquals(80.0, $doc->neto, 'bad-neto');
        $this->assertEquals(0.0, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(80.0, $doc->total, 'bad-total');
        $this->assertEquals(0.0, $doc->totalrecargo, 'bad-totalrecargo');

        // restauramos el régimen original de la empresa
        $company->regimeniva = $originalRegimen;
        $this->assertTrue($company->save(), 'can-not-restore-company');
    }

    public function testTravelAgencySaleRectificativeNegativeMargin(): void
    {
        // creamos una serie rectificativa
        $serie = new Serie();
        $serie->codserie = 'RT';
        if (false === $serie->exists()) {
            $serie->descripcion = 'Rectificativas test';
            $serie->tipo = 'R';
            $this->assertTrue($serie->save(), 'can-not-save-serie');
            Series::clear();
        }

        // cambiamos el régimen de la empresa a agencia de viajes
        $doc = new PresupuestoCliente();
        $doc->codserie = $serie->codserie;
        $company = $doc->getCompany();
        $originalRegimen = $company->regimeniva;
        $company->regimeniva = RegimenIVA::TAX_SYSTEM_TRAVEL;
        $this->assertTrue($company->save(), 'can-not-save-company');

        // nota de crédito: cantidad -1, pvp 200, coste 120
        // margen = (-200) - (-120) = -80
        $line = $doc->getNewLine();
        $line->cantidad = -1;
        $line->pvpunitario = 200;
        $line->coste = 120;
        $line->iva = 21;

        $lines = [$line];
        $this->assertTrue(Calculator::calculate($doc, $lines, false));

        // rectificativa: IVA sobre el margen negativo (-80 * 21% = -16.8)
        $this->assertEquals(-200.0, $doc->neto, 'bad-neto');
        $this->assertEquals(-16.8, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(-216.8, $doc->total, 'bad-total');

        // restauramos el régimen original de la empresa
        $company->regimeniva = $originalRegimen;
        $this->assertTrue($company->save(), 'can-not-restore-company');

        // eliminamos
        $serie->delete();
    }

    public function testExportPurchaseIsNoOp(): void
    {
        // operacion EXPORT en compra no debe modificar el IVA
        $doc = new PresupuestoProveedor();
        $doc->operacion = InvoiceOperation::EXPORT;

        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        $line->iva = 21;

        $lines = [$line];
        $this->assertTrue(Calculator::calculate($doc, $lines, false));

        // el IVA se mantiene porque EXPORT solo aplica a ventas
        $this->assertEquals(21.0, $lines[0]->iva, 'bad-line-iva');
        $this->assertEquals(100.0, $doc->neto, 'bad-neto');
        $this->assertEquals(21.0, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(121.0, $doc->total, 'bad-total');
    }

    public function testImportSaleIsNoOp(): void
    {
        // operacion IMPORT en venta no debe modificar el IVA
        $doc = new PresupuestoCliente();
        $doc->operacion = InvoiceOperation::IMPORT;

        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        $line->iva = 21;

        $lines = [$line];
        $this->assertTrue(Calculator::calculate($doc, $lines, false));

        // el IVA se mantiene porque IMPORT solo aplica a compras
        $this->assertEquals(21.0, $lines[0]->iva, 'bad-line-iva');
        $this->assertEquals(100.0, $doc->neto, 'bad-neto');
        $this->assertEquals(21.0, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(121.0, $doc->total, 'bad-total');
    }

    public function testUsedGoodsSaleNormalProduct(): void
    {
        // obtenemos IVA21
        $tax = Impuestos::get('IVA21');
        if (false === $tax->exists()) {
            $this->markTestSkipped('IVA21-not-found');
        }

        // creamos un producto normal (NO segunda mano)
        $product = $this->getRandomProduct();
        $product->codimpuesto = $tax->codimpuesto;
        $this->assertTrue($product->save(), 'can-not-save-product');

        // cambiamos el régimen de la empresa a bienes usados
        $doc = new PresupuestoCliente();
        $company = $doc->getCompany();
        $originalRegimen = $company->regimeniva;
        $company->regimeniva = RegimenIVA::TAX_SYSTEM_USED_GOODS;
        $this->assertTrue($company->save(), 'can-not-save-company');

        // creamos un cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer');
        $this->assertTrue($doc->setSubject($subject), 'can-not-assign-customer');
        $this->assertTrue($doc->save(), 'can-not-save-doc');

        // añadimos el producto: pvp 200, coste 150
        $line = $doc->getNewProductLine($product->referencia);
        $line->cantidad = 1;
        $line->pvpunitario = 200;
        $line->coste = 150;
        $lines = [$line];
        $this->assertTrue(Calculator::calculate($doc, $lines, false));

        // producto normal: IVA sobre el total, no sobre el margen
        $this->assertEquals(200.0, $doc->neto, 'bad-neto');
        $this->assertEquals(42.0, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(242.0, $doc->total, 'bad-total');
        $this->assertEquals(0.0, $doc->totalrecargo, 'bad-totalrecargo');

        // restauramos el régimen original de la empresa
        $company->regimeniva = $originalRegimen;
        $this->assertTrue($company->save(), 'can-not-restore-company');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-doc');
        $this->assertTrue($product->delete(), 'can-not-delete-product');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'cliente-cant-delete');
    }

    public function testTravelAgencyPurchase(): void
    {
        // cambiamos el régimen de la empresa a agencia de viajes
        $doc = new PresupuestoProveedor();
        $company = $doc->getCompany();
        $originalRegimen = $company->regimeniva;
        $company->regimeniva = RegimenIVA::TAX_SYSTEM_TRAVEL;
        $this->assertTrue($company->save(), 'can-not-save-company');

        // creamos un proveedor normal
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save(), 'can-not-save-supplier');
        $this->assertTrue($doc->setSubject($subject), 'can-not-assign-supplier');

        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        $line->coste = 80;
        $line->iva = 21;

        $lines = [$line];
        $this->assertTrue(Calculator::calculate($doc, $lines, false));

        // compra de agencia de viajes: IVA normal (margen solo aplica a ventas)
        $this->assertEquals(100.0, $doc->neto, 'bad-neto');
        $this->assertEquals(21.0, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(121.0, $doc->total, 'bad-total');
        $this->assertEquals(0.0, $doc->totalrecargo, 'bad-totalrecargo');

        // restauramos el régimen original de la empresa
        $company->regimeniva = $originalRegimen;
        $this->assertTrue($company->save(), 'can-not-restore-company');

        // eliminamos
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'proveedor-cant-delete');
    }

    public function testIntraCommunityPurchaseMultipleRates(): void
    {
        $doc = new PresupuestoProveedor();
        $doc->operacion = InvoiceOperation::INTRA_COMMUNITY;

        // línea al 21%
        $line1 = $doc->getNewLine();
        $line1->cantidad = 1;
        $line1->pvpunitario = 100;
        $line1->iva = 21;

        // línea al 10%
        $line2 = $doc->getNewLine();
        $line2->cantidad = 1;
        $line2->pvpunitario = 50;
        $line2->iva = 10;

        $lines = [$line1, $line2];
        $this->assertTrue(Calculator::calculate($doc, $lines, false));

        // ambas líneas mantienen IVA para autorepercusión
        $this->assertEquals(21.0, $lines[0]->iva, 'bad-line1-iva');
        $this->assertEquals(10.0, $lines[1]->iva, 'bad-line2-iva');

        // pero el IVA se neutraliza en el total del documento
        $this->assertEquals(150.0, $doc->neto, 'bad-neto');
        $this->assertEquals(0.0, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(150.0, $doc->total, 'bad-total');
        $this->assertEquals(0.0, $doc->totalrecargo, 'bad-totalrecargo');
    }
}
