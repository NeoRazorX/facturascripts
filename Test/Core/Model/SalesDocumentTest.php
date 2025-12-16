<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2025 Carlos García Gómez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
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

use FacturaScripts\Core\DataSrc\Retenciones;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Lib\Vies;
use FacturaScripts\Core\Model\Cuenta;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Lib\InvoiceOperation;
use FacturaScripts\Dinamic\Lib\ProductType;
use FacturaScripts\Dinamic\Lib\TaxException;
use FacturaScripts\Dinamic\Lib\TaxRegime;
use FacturaScripts\Dinamic\Model\AlbaranCliente;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\PedidoCliente;
use FacturaScripts\Dinamic\Model\PresupuestoCliente;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class SalesDocumentTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    const PRODUCT_PRICE = 100;
    const PRODUCT_QUANTITY = 2;
    const PRODUCT_COST = 60;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
        self::removeTaxRegularization();
    }

    /**
     * Prueba el régimen general con cliente en régimen general.
     * IVA normal sobre el neto. Sin recargo de equivalencia.
     */
    public function testGeneralRegimeWithGeneralCustomer(): void
    {
        // creamos una empresa con régimen general
        $company = $this->getRandomCompany();
        $company->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($company->save(), 'cant-create-company');

        // creamos un cliente con régimen general
        $customer = $this->getRandomCustomer();
        $customer->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // creamos los 4 tipos de documentos
        $this->createAndTestDocuments($company, $customer, 'testGeneralRegimeWithGeneralCustomer', [
            'expected-doc-neto' => 200,
            'expected-doc-iva' => 42,
            'expected-doc-recargo' => 0,
            'expected-doc-irpf' => 0,
            'expected-doc-total' => 242,
            'expected-line-pvptotal' => 200,
            'expected-line-iva' => 21,
            'expected-line-recargo' => 0,
            'expected-line-irpf' => 0
        ]);

        // eliminamos
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Prueba el régimen general con cliente en recargo de equivalencia.
     * Se debe aplicar IVA + recargo de equivalencia.
     */
    public function testGeneralRegimeWithSurchargeCustomer(): void
    {
        // creamos una empresa con régimen general
        $company = $this->getRandomCompany();
        $company->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($company->save(), 'cant-create-company');

        // creamos un cliente con recargo de equivalencia
        $customer = $this->getRandomCustomer();
        $customer->regimeniva = TaxRegime::ES_TAX_REGIME_SURCHARGE;
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // IVA 21% + Recargo 5.2% sobre 200€ = 42 + 10.4 = 52.4
        $this->createAndTestDocuments($company, $customer, 'testGeneralRegimeWithSurchargeCustomer', [
            'expected-doc-neto' => 200,
            'expected-doc-iva' => 42,
            'expected-doc-recargo' => 10.4,
            'expected-doc-irpf' => 0,
            'expected-doc-total' => 252.4,
            'expected-line-pvptotal' => 200,
            'expected-line-iva' => 21,
            'expected-line-recargo' => 5.2,
            'expected-line-irpf' => 0
        ]);

        // eliminamos
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Prueba empresa en recargo de equivalencia con cliente en régimen general.
     * La empresa en recargo NO aplica recargo en sus ventas a clientes generales.
     */
    public function testSurchargeRegimeWithGeneralCustomer(): void
    {
        // creamos una empresa con recargo de equivalencia
        $company = $this->getRandomCompany();
        $company->regimeniva = TaxRegime::ES_TAX_REGIME_SURCHARGE;
        $this->assertTrue($company->save(), 'cant-create-company');

        // creamos un cliente con régimen general
        $customer = $this->getRandomCustomer();
        $customer->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // Solo IVA 21% sobre 200€ = 42, sin recargo
        $this->createAndTestDocuments($company, $customer, 'testSurchargeRegimeWithGeneralCustomer', [
            'expected-doc-neto' => 200,
            'expected-doc-iva' => 42,
            'expected-doc-recargo' => 0,
            'expected-doc-irpf' => 0,
            'expected-doc-total' => 242,
            'expected-line-pvptotal' => 200,
            'expected-line-iva' => 21,
            'expected-line-recargo' => 0,
            'expected-line-irpf' => 0
        ]);

        // eliminamos
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Prueba empresa y cliente ambos en recargo de equivalencia.
     * Las empresas en recargo NO aplican recargo en sus ventas (solo lo pagan en compras).
     */
    public function testSurchargeRegimeWithSurchargeCustomer(): void
    {
        // creamos una empresa con recargo de equivalencia
        $company = $this->getRandomCompany();
        $company->regimeniva = TaxRegime::ES_TAX_REGIME_SURCHARGE;
        $this->assertTrue($company->save(), 'cant-create-company');

        // creamos un cliente también con recargo de equivalencia
        $customer = $this->getRandomCustomer();
        $customer->regimeniva = TaxRegime::ES_TAX_REGIME_SURCHARGE;
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // Solo IVA 21% sobre 200€ = 42, recargo neutralizado
        $this->createAndTestDocuments($company, $customer, 'testSurchargeRegimeWithSurchargeCustomer', [
            'expected-doc-neto' => 200,
            'expected-doc-iva' => 42,
            'expected-doc-recargo' => 0,
            'expected-doc-irpf' => 0,
            'expected-doc-total' => 242,
            'expected-line-pvptotal' => 200,
            'expected-line-iva' => 21,
            'expected-line-recargo' => 0,
            'expected-line-irpf' => 0
        ]);

        // eliminamos
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Prueba el régimen de bienes usados.
     * IVA solo sobre el beneficio (venta - coste).
     */
    public function testUsedGoodsRegime(): void
    {
        // creamos una empresa con régimen de bienes usados
        $company = $this->getRandomCompany();
        $company->regimeniva = TaxRegime::ES_TAX_REGIME_USED_GOODS;
        $this->assertTrue($company->save(), 'cant-create-company');

        // creamos un producto de segunda mano con coste
        $product = $this->getRandomProduct();
        $product->tipo = ProductType::SECOND_HAND;
        $product->ventasinstock = true;
        $this->assertTrue($product->save(), 'cant-create-product');

        // le asignamos un coste y un precio a su variante
        foreach ($product->getVariants() as $variant) {
            $variant->coste = self::PRODUCT_COST;
            $variant->precio = self::PRODUCT_PRICE;
            $this->assertTrue($variant->save(), 'cant-update-variant');
            break;
        }

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $customer->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // calculamos el beneficio y el IVA esperado
        // Beneficio por unidad: 100 - 60 = 40
        // Beneficio total: 40 * 2 = 80
        // IVA sobre beneficio: 80 * 21% = 16.8
        $expectedNeto = 200; // pvptotal
        $expectedIva = 16.8;  // IVA solo sobre beneficio
        $expectedTotal = 216.8;

        // creamos los 4 tipos de documentos con el producto
        $this->createAndTestDocuments($company, $customer, 'testUsedGoodsRegime', [
            'expected-doc-neto' => $expectedNeto,
            'expected-doc-iva' => $expectedIva,
            'expected-doc-recargo' => 0,
            'expected-doc-irpf' => 0,
            'expected-doc-total' => $expectedTotal,
            'expected-line-pvptotal' => 200,
            'expected-line-iva' => 21,
            'expected-line-recargo' => 0,
            'expected-line-irpf' => 0
        ], $product);

        // eliminamos
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
        $this->assertTrue($product->delete(), 'cant-delete-product');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Prueba el régimen de agencias de viaje.
     * IVA sobre el margen (venta - coste de servicios).
     */
    public function testTravelAgencyRegime(): void
    {
        // creamos una empresa con régimen de agencias de viaje
        $company = $this->getRandomCompany();
        $company->regimeniva = TaxRegime::ES_TAX_REGIME_TRAVEL;
        $this->assertTrue($company->save(), 'cant-create-company');

        // creamos un producto con coste
        $product = $this->getRandomProduct();
        $product->ventasinstock = true;
        $this->assertTrue($product->save(), 'cant-create-product');

        // le asignamos un coste y un precio a su variante
        foreach ($product->getVariants() as $variant) {
            $variant->coste = self::PRODUCT_COST;
            $variant->precio = self::PRODUCT_PRICE;
            $this->assertTrue($variant->save(), 'cant-update-variant');
            break;
        }

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $customer->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // calculamos el margen y el IVA esperado
        // Margen por unidad: 100 - 60 = 40
        // Margen total: 40 * 2 = 80
        // IVA sobre margen: 80 * 21% = 16.8
        $expectedNeto = 200; // pvptotal
        $expectedIva = 16.8;  // IVA solo sobre margen
        $expectedTotal = 216.8;

        // creamos los 4 tipos de documentos con el producto
        $this->createAndTestDocuments($company, $customer, 'testTravelAgencyRegime', [
            'expected-doc-neto' => $expectedNeto,
            'expected-doc-iva' => $expectedIva,
            'expected-doc-recargo' => 0,
            'expected-doc-irpf' => 0,
            'expected-doc-total' => $expectedTotal,
            'expected-line-pvptotal' => 200,
            'expected-line-iva' => 21,
            'expected-line-recargo' => 0,
            'expected-line-irpf' => 0
        ], $product);

        // eliminamos
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
        $this->assertTrue($product->delete(), 'cant-delete-product');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Prueba el régimen de criterio de caja.
     * El cálculo es idéntico al general, solo difiere el momento del devengo.
     */
    public function testCashCriteriaRegime(): void
    {
        // creamos una empresa con régimen de criterio de caja
        $company = $this->getRandomCompany();
        $company->regimeniva = TaxRegime::ES_TAX_REGIME_CASH_CRITERIA;
        $this->assertTrue($company->save(), 'cant-create-company');

        // creamos un cliente con régimen general
        $customer = $this->getRandomCustomer();
        $customer->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // IVA 21% sobre 200€ = 42
        $this->createAndTestDocuments($company, $customer, 'testCashCriteriaRegime', [
            'expected-doc-neto' => 200,
            'expected-doc-iva' => 42,
            'expected-doc-recargo' => 0,
            'expected-doc-irpf' => 0,
            'expected-doc-total' => 242,
            'expected-line-pvptotal' => 200,
            'expected-line-iva' => 21,
            'expected-line-recargo' => 0,
            'expected-line-irpf' => 0
        ]);

        // eliminamos
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Prueba el régimen agrario.
     * Los tipos pueden ser reducidos, pero el cálculo es similar al general.
     */
    public function testAgrarianRegime(): void
    {
        // creamos una empresa con régimen agrario
        $company = $this->getRandomCompany();
        $company->regimeniva = TaxRegime::ES_TAX_REGIME_AGRARIAN;
        $this->assertTrue($company->save(), 'cant-create-company');

        // creamos un cliente con régimen general
        $customer = $this->getRandomCustomer();
        $customer->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // IVA 21% sobre 200€ = 42 (puede variar según el producto)
        $this->createAndTestDocuments($company, $customer, 'testAgrarianRegime', [
            'expected-doc-neto' => 200,
            'expected-doc-iva' => 42,
            'expected-doc-recargo' => 0,
            'expected-doc-irpf' => 0,
            'expected-doc-total' => 242,
            'expected-line-pvptotal' => 200,
            'expected-line-iva' => 21,
            'expected-line-recargo' => 0,
            'expected-line-irpf' => 0
        ]);

        // eliminamos
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Prueba el régimen simplificado.
     * El cálculo es similar al régimen general.
     */
    public function testSimplifiedRegime(): void
    {
        // creamos una empresa con régimen simplificado
        $company = $this->getRandomCompany();
        $company->regimeniva = TaxRegime::ES_TAX_REGIME_SIMPLIFIED;
        $this->assertTrue($company->save(), 'cant-create-company');

        // creamos un cliente con régimen general
        $customer = $this->getRandomCustomer();
        $customer->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // IVA 21% sobre 200€ = 42
        $this->createAndTestDocuments($company, $customer, 'testSimplifiedRegime', [
            'expected-doc-neto' => 200,
            'expected-doc-iva' => 42,
            'expected-doc-recargo' => 0,
            'expected-doc-irpf' => 0,
            'expected-doc-total' => 242,
            'expected-line-pvptotal' => 200,
            'expected-line-iva' => 21,
            'expected-line-recargo' => 0,
            'expected-line-irpf' => 0
        ]);

        // eliminamos
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Prueba operaciones intracomunitarias.
     * IVA = 0% con inversión del sujeto pasivo.
     */
    public function testIntraCommunityOperation(): void
    {
        // comprobamos primero si el VIES funciona
        if (Vies::getLastError() != '') {
            $this->markTestSkipped('Vies service is not available');
        }

        // creamos una empresa
        $company = $this->getRandomCompany();
        $company->codpais = 'ESP';
        $company->cifnif = 'B13658620';
        $company->tipoidfiscal = 'CIF';
        $company->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($company->save(), 'cant-create-company');

        // creamos un cliente de otro país de la UE
        $customer = $this->getRandomCustomer();
        $customer->cifnif = 'PT513969144';
        $customer->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($customer->save(), 'cant-create-customer');
        $address = $customer->getDefaultAddress();
        $address->codpais = 'PRT'; // Portugal
        $this->assertTrue($address->save(), 'cant-update-address');

        // IVA 0% en operación intracomunitaria
        $this->createAndTestDocuments($company, $customer, 'testIntraCommunityOperation', [
            'expected-doc-neto' => 200,
            'expected-doc-iva' => 0,
            'expected-doc-recargo' => 0,
            'expected-doc-irpf' => 0,
            'expected-doc-total' => 200,
            'doc-operacion' => InvoiceOperation::ES_INTRA_COMMUNITY,
            'line-excepcioniva' => TaxException::ES_TAX_EXCEPTION_25,
            'line-iva' => 0,
            'line-codimpuesto' => 'IVA0',
            'expected-line-pvptotal' => 200,
            'expected-line-iva' => 0,
            'expected-line-recargo' => 0,
            'expected-line-irpf' => 0
        ]);

        // eliminamos
        $this->assertTrue($address->delete(), 'cant-delete-address');
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Prueba operaciones de exportación.
     * IVA = 0% en exportaciones fuera de la UE.
     */
    public function testExportOperation(): void
    {
        // creamos una empresa
        $company = $this->getRandomCompany();
        $company->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($company->save(), 'cant-create-company');

        // creamos un cliente fuera de la UE
        $customer = $this->getRandomCustomer();
        $customer->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($customer->save(), 'cant-create-customer');
        $address = $customer->getDefaultAddress();
        $address->codpais = 'USA'; // Estados Unidos
        $this->assertTrue($address->save(), 'cant-update-address');
        $customer->idcontactoenv = $address->id();
        $customer->idcontactofact = $address->id();
        $this->assertTrue($customer->save(), 'cant-update-customer');

        // IVA 0% en exportación
        $this->createAndTestDocuments($company, $customer, 'testExportOperation', [
            'expected-doc-neto' => 200,
            'expected-doc-iva' => 0,
            'expected-doc-recargo' => 0,
            'expected-doc-irpf' => 0,
            'expected-doc-total' => 200,
            'doc-operacion' => InvoiceOperation::ES_EXPORT,
            'line-excepcioniva' => TaxException::ES_TAX_EXCEPTION_21,
            'line-iva' => 0,
            'line-codimpuesto' => 'IVA0',
            'expected-line-pvptotal' => 200,
            'expected-line-iva' => 0,
            'expected-line-recargo' => 0,
            'expected-line-irpf' => 0
        ]);

        // eliminamos
        $this->assertTrue($address->delete(), 'cant-delete-address');
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Prueba operaciones exentas de IVA.
     * IVA = 0% por exención fiscal.
     */
    public function testExemptOperation(): void
    {
        // creamos una empresa
        $company = $this->getRandomCompany();
        $company->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($company->save(), 'cant-create-company');

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $customer->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // IVA 0% por exención
        $this->createAndTestDocuments($company, $customer, 'testExemptOperation', [
            'expected-doc-neto' => 200,
            'expected-doc-iva' => 0,
            'expected-doc-recargo' => 0,
            'expected-doc-irpf' => 0,
            'expected-doc-total' => 200,
            'doc-operacion' => InvoiceOperation::EXEMPT,
            'line-iva' => 0,
            'line-codimpuesto' => 'IVA0',
            'expected-line-pvptotal' => 200,
            'expected-line-iva' => 0,
            'expected-line-recargo' => 0,
            'expected-line-irpf' => 0
        ]);

        // eliminamos
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Prueba régimen general con descuento del 10%.
     * Neto con descuento: 200€ - 10% = 180€
     * IVA 21% sobre 180€ = 37.8€
     */
    public function testGeneralRegimeWithDiscount(): void
    {
        // creamos una empresa con régimen general
        $company = $this->getRandomCompany();
        $company->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($company->save(), 'cant-create-company');

        // creamos un cliente con régimen general
        $customer = $this->getRandomCustomer();
        $customer->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // Con descuento del 10%: Neto = 180, IVA = 37.8, Total = 217.8
        $this->createAndTestDocuments($company, $customer, 'testGeneralRegimeWithDiscount', [
            'expected-doc-neto' => 180,
            'expected-doc-iva' => 37.8,
            'expected-doc-recargo' => 0,
            'expected-doc-irpf' => 0,
            'expected-doc-total' => 217.8,
            'line-dtopor' => 10,
            'expected-line-pvptotal' => 180,
            'expected-line-iva' => 21,
            'expected-line-recargo' => 0,
            'expected-line-irpf' => 0,
            'expected-line-dtopor' => 10
        ]);

        // eliminamos
        
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Prueba régimen general con recargo de equivalencia y descuento del 5%.
     * Neto con descuento: 200€ - 5% = 190€
     * IVA 21% sobre 190€ = 39.9€
     * Recargo 5.2% sobre 190€ = 9.88€
     */
    public function testSurchargeRegimeWithDiscount(): void
    {
        // creamos una empresa con régimen general
        $company = $this->getRandomCompany();
        $company->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($company->save(), 'cant-create-company');

        // creamos un cliente con recargo de equivalencia
        $customer = $this->getRandomCustomer();
        $customer->regimeniva = TaxRegime::ES_TAX_REGIME_SURCHARGE;
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // Con descuento del 5%: Neto = 190, IVA = 39.9, Recargo = 9.88, Total = 239.78
        $this->createAndTestDocuments($company, $customer, 'testSurchargeRegimeWithDiscount', [
            'expected-doc-neto' => 190,
            'expected-doc-iva' => 39.9,
            'expected-doc-recargo' => 9.88,
            'expected-doc-irpf' => 0,
            'expected-doc-total' => 239.78,
            'line-dtopor' => 5,
            'expected-line-pvptotal' => 190,
            'expected-line-iva' => 21,
            'expected-line-recargo' => 5.2,
            'expected-line-irpf' => 0,
            'expected-line-dtopor' => 5
        ]);

        // eliminamos
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Prueba régimen general con descuento del 20% y cliente con recargo.
     * Neto con descuento: 200€ - 20% = 160€
     * IVA 21% sobre 160€ = 33.6€
     * Recargo 5.2% sobre 160€ = 8.32€
     */
    public function testGeneralRegimeWithLargeDiscountAndSurcharge(): void
    {
        // creamos una empresa con régimen general
        $company = $this->getRandomCompany();
        $company->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($company->save(), 'cant-create-company');

        // creamos un cliente con recargo de equivalencia
        $customer = $this->getRandomCustomer();
        $customer->regimeniva = TaxRegime::ES_TAX_REGIME_SURCHARGE;
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // Con descuento del 20%: Neto = 160, IVA = 33.6, Recargo = 8.32, Total = 201.92
        $this->createAndTestDocuments($company, $customer, 'testGeneralRegimeWithLargeDiscountAndSurcharge', [
            'expected-doc-neto' => 160,
            'expected-doc-iva' => 33.6,
            'expected-doc-recargo' => 8.32,
            'expected-doc-irpf' => 0,
            'expected-doc-total' => 201.92,
            'line-dtopor' => 20,
            'expected-line-pvptotal' => 160,
            'expected-line-iva' => 21,
            'expected-line-recargo' => 5.2,
            'expected-line-irpf' => 0,
            'expected-line-dtopor' => 20
        ]);

        // eliminamos
        
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Prueba régimen general con IRPF.
     * Neto: 200€
     * IVA 21% sobre 200€ = 42€
     * IRPF 15% sobre 200€ = 30€ (se resta del total)
     * Total: 200 + 42 - 30 = 212€
     */
    public function testGeneralRegimeWithIRPF(): void
    {
        // creamos una empresa con régimen general
        $company = $this->getRandomCompany();
        $company->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($company->save(), 'cant-create-company');

        // creamos un cliente con régimen general y retención
        $customer = $this->getRandomCustomer();
        $customer->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;

        // asignar retención si existe
        foreach (Retenciones::all() as $retention) {
            $customer->codretencion = $retention->codretencion;
            break;
        }

        $this->assertTrue($customer->save(), 'cant-create-customer');

        // Sí hay retención, probar con IRPF
        // IRPF 15% sobre 200€ = 30€, Total = 200 + 42 - 30 = 212€
        if (!empty($customer->codretencion)) {
            $this->createAndTestDocuments($company, $customer, 'testGeneralRegimeWithIRPF', [
                'expected-doc-neto' => 200,
                'expected-doc-iva' => 42,
                'expected-doc-recargo' => 0,
                'expected-doc-irpf' => 15,
                'expected-doc-total' => 212,
                'expected-line-pvptotal' => 200,
                'expected-line-iva' => 21,
                'expected-line-recargo' => 0,
                'expected-line-irpf' => 15
            ]);
        }

        // eliminamos
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Prueba régimen general con IRPF y descuento.
     * Neto con descuento: 200€ - 10% = 180€
     * IVA 21% sobre 180€ = 37.8€
     * IRPF 15% sobre 180€ = 27€ (se resta del total)
     * Total: 180 + 37.8 - 27 = 190.8€
     */
    public function testGeneralRegimeWithIRPFAndDiscount(): void
    {
        // creamos una empresa con régimen general
        $company = $this->getRandomCompany();
        $company->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($company->save(), 'cant-create-company');

        // creamos un cliente con régimen general y retención
        $customer = $this->getRandomCustomer();
        $customer->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;

        // asignar retención si existe
        foreach (Retenciones::all() as $retention) {
            $customer->codretencion = $retention->codretencion;
            break;
        }

        $this->assertTrue($customer->save(), 'cant-create-customer');

        // Si hay retención, probar con IRPF y descuento
        // IRPF 15% sobre 180€ = 27€, Total = 180 + 37.8 - 27 = 190.8€
        if (!empty($customer->codretencion)) {
            $this->createAndTestDocuments($company, $customer, 'testGeneralRegimeWithIRPFAndDiscount', [
                'expected-doc-neto' => 180,
                'expected-doc-iva' => 37.8,
                'expected-doc-recargo' => 0,
                'expected-doc-irpf' => 15,
                'expected-doc-total' => 190.8,
                'line-dtopor' => 10,
                'expected-line-pvptotal' => 180,
                'expected-line-iva' => 21,
                'expected-line-recargo' => 0,
                'expected-line-irpf' => 15,
                'expected-line-dtopor' => 10
            ]);
        }

        // eliminamos
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Crea y prueba los 4 tipos de documentos: presupuesto, pedido, albarán y factura.
     *
     * @param Empresa $company Empresa
     * @param Cliente $customer Cliente
     * @param string $testName Nombre del test de origen para identificar errores
     * @param array $data Array con los datos esperados y configuración:
     *   - 'expected-doc-neto': float - Neto esperado
     *   - 'expected-doc-iva': float - IVA esperado
     *   - 'expected-doc-recargo': float - Recargo esperado (opcional)
     *   - 'expected-doc-irpf': float - IRPF esperado (opcional)
     *   - 'expected-doc-total': float - Total esperado
     *   - 'expected-line-pvptotal': float - Precio total de línea esperado (opcional)
     *   - 'expected-line-iva': float - IVA de línea esperado (opcional)
     *   - 'expected-line-recargo': float - Recargo de línea esperado (opcional)
     *   - 'expected-line-irpf': float - IRPF de línea esperado (opcional)
     *   - 'expected-line-dtopor': float - Descuento de línea esperado (opcional)
     *   - 'doc-operacion': string - Operación del documento (opcional)
     *   - 'line-dtopor': float - Descuento de línea (opcional)
     *   - 'line-cantidad': float - Cantidad de línea (opcional, por defecto PRODUCT_QUANTITY)
     *   - 'line-pvpunitario': float - Precio unitario de línea (opcional, por defecto PRODUCT_PRICE)
     *   - 'line-excepcioniva': string - Excepción de IVA de línea (opcional)
     * @param Producto|null $product Producto a añadir en las líneas (opcional)
     */
    private function createAndTestDocuments(Empresa $company, Cliente $customer, string $testName, array $data, ?Producto $product = null): void
    {
        $warehouse = null;
        $documentClasses = [
            PresupuestoCliente::class,
            PedidoCliente::class,
            AlbaranCliente::class,
            FacturaCliente::class
        ];

        // asignar almacén de la empresa
        foreach ($company->getWarehouses() as $w) {
            $warehouse = $w;
            break;
        }

        // comprobamos que existe el almacén y pertenece a la empresa
        $this->assertTrue($warehouse->exists(), $testName . '-no-warehouse-found');
        $this->assertEquals($company->id(), $warehouse->idempresa, $testName . '-warehouse-bad-company');

        // comprobamos que existe el ejercicio y pertenece a la empresa
        $exercise = $this->getRandomExercise($company->id());
        $this->assertTrue($exercise->exists(), $testName . '-no-exercise-found');
        $this->assertEquals($company->id(), $exercise->idempresa, $testName . '-exercise-bad-company');

        // creamos el plan contable para el ejercicio, si no existe
        $this->installAccountingPlan();

        // comprobamos que existe el plan contable para el ejercicio
        $cuenta = new Cuenta();
        $where = [Where::eq('codejercicio', $exercise->codejercicio)];
        $this->assertTrue($cuenta->loadWhere($where), $testName . '-no-accounting-plan-for-exercise');

        // recorrer los tipos de documentos
        foreach ($documentClasses as $docClass) {
            // crear documento
            $doc = new $docClass();

            // asignar almacén
            $doc->setWarehouse($warehouse->codalmacen);

            // asignar cliente
            $doc->setSubject($customer);

            // recorrer los datos y añadir todos los que empiecen por 'doc-' al documento
            foreach ($data as $key => $value) {
                if (str_starts_with($key, 'doc-')) {
                    $property = substr($key, 4);
                    if ($doc->hasColumn($property)) {
                        $doc->$property = $value;
                    }
                }
            }

            // guardar documento
            $this->assertTrue($doc->save(), $testName . '-cant-create-document-' . $doc->modelClassName());

            // añadir una línea
            $line = $product && !empty($product->referencia) ? $doc->getNewProductLine($product->referencia) : $doc->getNewLine();
            $line->cantidad = $data['line-cantidad'] ?? self::PRODUCT_QUANTITY;
            $line->pvpunitario = $data['line-pvpunitario'] ?? self::PRODUCT_PRICE;
            
            // recorrer los datos y añadir todos los que empiecen por 'line-' a la línea
            foreach ($data as $key => $value) {
                if (str_starts_with($key, 'line-') && $key !== 'line-cantidad' && $key !== 'line-pvpunitario') {
                    $property = substr($key, 5);
                    if ($line->hasColumn($property)) {
                        $line->$property = $value;
                    }
                }
            }

            // guardar línea
            $this->assertTrue($line->save(), $testName . '-cant-save-line-' . $doc->modelClassName());

            // recalcular documento
            $lines = $doc->getLines();
            $this->assertTrue(Calculator::calculate($doc, $lines, true), $testName . '-cant-calculate-' . $doc->modelClassName());
            $line = $lines[0];

            // verificar documento
            foreach ($data as $key => $value) {
                if (str_starts_with($key, 'expected-doc-')) {
                    $property = substr($key, 13);
                    if ($doc->hasColumn($property)) {
                        $this->assertEquals($value, $doc->$property, $testName . '-' . $property . '-' . $doc->modelClassName());
                    }
                }
            }

            // verificar línea
            foreach ($data as $key => $value) {
                if (str_starts_with($key, 'expected-line-')) {
                    $property = substr($key, 14);
                    if ($line->hasColumn($property)) {
                        $this->assertEquals($value, $line->$property, $testName . '-line-' . $property . '-' . $doc->modelClassName());
                    }
                }
            }

            // si no es una factura, continuamos
            if (!($doc instanceof FacturaCliente)) {
                // eliminar documento
                $this->assertTrue($doc->delete(), $testName . '-cant-delete-document-' . $doc->modelClassName());
                continue;
            }

            // comprobamos que hay un recibo con el mismo importe que la factura
            $receipts = $doc->getReceipts();
            $this->assertCount(1, $receipts, $testName . '-bad-invoice-receipts-count');
            $this->assertEquals($doc->total, $receipts[0]->importe, $testName . '-bad-invoice-receipt-importe');

            // comprobamos el asiento
            $entry = $doc->getAccountingEntry();
            $this->assertTrue($entry->exists(), $testName . '-accounting-entry-not-found');
            $this->assertEquals($doc->total, $entry->importe, $testName . '-accounting-entry-bad-importe');
            $this->assertEquals($doc->fecha, $entry->fecha, $testName . '-accounting-entry-bad-date');
            $this->assertEquals($doc->idasiento, $entry->idasiento, $testName . '-accounting-entry-bad-idasiento');

            // aplicamos un descuento para modificar el total de la factura
            $doc->dtopor1 = 50;
            $this->assertTrue(Calculator::calculate($doc, $lines, true), $testName . '-cant-update-invoice-discount');

            // comprobamos que el recibo se ha actualizado
            $receipts = $doc->getReceipts();
            $this->assertCount(1, $receipts, $testName . '-bad-updated-invoice-receipts-count');
            $this->assertEquals($doc->total, $receipts[0]->importe, $testName . '-bad-updated-invoice-receipt-importe');

            // comprobamos que se ha actualizado el asiento
            $updEntry = $doc->getAccountingEntry();
            $this->assertTrue($updEntry->exists(), $testName . '-updated-accounting-entry-not-found');
            $this->assertEquals($doc->idasiento, $updEntry->idasiento, $testName . '-accounting-entry-not-updated');
            $this->assertEquals($doc->total, $updEntry->importe, $testName . '-updated-accounting-entry-bad-importe');

            // eliminar documento
            $this->assertTrue($doc->delete(), $testName . '-cant-delete-document-' . $doc->modelClassName());
        }
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
