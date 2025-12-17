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

use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Lib\InvoiceOperation;
use FacturaScripts\Dinamic\Lib\ProductType;
use FacturaScripts\Dinamic\Lib\TaxException;
use FacturaScripts\Dinamic\Lib\TaxRegime;
use FacturaScripts\Dinamic\Lib\Vies;
use FacturaScripts\Dinamic\Model\AlbaranProveedor;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\Cuenta;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\PedidoProveedor;
use FacturaScripts\Dinamic\Model\PresupuestoProveedor;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class PurchasesDocumentTest extends TestCase
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
     * Prueba que un proveedor con operación lo copia correctamente en la factura,
     * si no, prueba copiar el campo operación de la empresa, y si no, dejarlo vacío.
     */
    public function testInvoiceOperationField(): void
    {
        // creamos una empresa con operación
        $company = $this->getRandomCompany();
        $company->operacion = InvoiceOperation::ES_WORK_CERTIFICATION;
        $this->assertTrue($company->save(), 'cant-create-company');

        // obtenemos el almacén de la empresa
        $warehouses = $company->getWarehouses();

        // creamos un proveedor con operación
        $supplierWithOp = $this->getRandomSupplier();
        $supplierWithOp->operacion = InvoiceOperation::EXEMPT;
        $this->assertTrue($supplierWithOp->save(), 'cant-create-supplier-with-op');

        // creamos un proveedor sin operación
        $supplierWithoutOp = $this->getRandomSupplier();
        $this->assertTrue($supplierWithoutOp->save(), 'cant-create-supplier-without-op');

        $documentClasses = [
            PresupuestoProveedor::class,
            PedidoProveedor::class,
            AlbaranProveedor::class,
            FacturaProveedor::class
        ];

        foreach ($documentClasses as $documentClass) {
            // creamos un documento para el proveedor con operación
            $doc = new $documentClass();
            $doc->setWarehouse($warehouses[0]->codalmacen);
            $doc->setSubject($supplierWithOp);
            $this->assertTrue($doc->save(), 'cant-create-' . $doc->modelClassName() . '-with-supplier-with-op');
            $this->assertEquals(InvoiceOperation::EXEMPT, $doc->operacion, 'bad-' . $doc->modelClassName() . '-operation-from-supplier');

            // creamos un documento para el proveedor sin operación, pero con operación en la empresa
            $doc2 = new $documentClass();
            $doc2->setWarehouse($warehouses[0]->codalmacen);
            $doc2->setSubject($supplierWithoutOp);
            $this->assertTrue($doc2->save(), 'cant-create-' . $doc2->modelClassName() . '-with-supplier-without-op');
            $this->assertEquals(InvoiceOperation::ES_WORK_CERTIFICATION, $doc2->operacion, 'bad-' . $doc2->modelClassName() . '-operation-from-company');

            // creamos un documento sin operación en proveedor ni empresa, usando la empresa por defecto
            $doc3 = new $documentClass();
            $doc3->setSubject($supplierWithoutOp);
            $this->assertTrue($doc3->save(), 'cant-create-' . $doc3->modelClassName() . '-without-operation');
            $this->assertNull($doc3->operacion, 'bad-' . $doc3->modelClassName() . '-operation-empty');

            // eliminamos documentos
            $this->assertTrue($doc->delete(), 'cant-delete-' . $doc->modelClassName() . '-with-supplier-with-op');
            $this->assertTrue($doc2->delete(), 'cant-delete-' . $doc2->modelClassName() . '-with-supplier-without-op');
            $this->assertTrue($doc3->delete(), 'cant-delete-' . $doc3->modelClassName() . '-without-operation');
        }

        // eliminamos
        $this->assertTrue($supplierWithOp->delete(), 'cant-delete-supplier-with-op');
        $this->assertTrue($supplierWithoutOp->delete(), 'cant-delete-supplier-without-op');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Prueba que al crear una línea en la factura, ponga la excepción de iva del producto,
     * si no tiene, que ponga la del cliente, y si no tiene, que ponga la de la empresa.
     */
    public function testInvoiceLineIvaExemption(): void
    {
        // creamos una empresa con exención de iva
        $company = $this->getRandomCompany();
        $company->excepcioniva = TaxException::ES_TAX_EXCEPTION_20;
        $this->assertTrue($company->save(), 'cant-create-company');

        // obtenemos el almacén de la empresa
        $warehouses = $company->getWarehouses();

        // creamos un proveedor con exención de iva
        $supplierWithExemption = $this->getRandomSupplier();
        $supplierWithExemption->excepcioniva = TaxException::ES_TAX_EXCEPTION_21;
        $this->assertTrue($supplierWithExemption->save(), 'cant-create-supplier-with-exemption');

        // creamos un cliente sin exención de iva
        $supplierWithoutExemption = $this->getRandomSupplier();
        $this->assertTrue($supplierWithoutExemption->save(), 'cant-create-supplier-without-exemption');

        // creamos un producto con exención de iva
        $productWithExemption = $this->getRandomProduct();
        $productWithExemption->codimpuesto = 'IVA0';
        $productWithExemption->excepcioniva = TaxException::ES_TAX_EXCEPTION_22;
        $productWithExemption->nostock = true;
        $this->assertTrue($productWithExemption->save(), 'cant-create-product-with-exemption');

        // creamos un producto sin exención de iva
        $productWithoutExemption = $this->getRandomProduct();
        $productWithoutExemption->nostock = true;
        $this->assertTrue($productWithoutExemption->save(), 'cant-create-product-without-exemption');


        $documentClasses = [
            PresupuestoProveedor::class,
            PedidoProveedor::class,
            AlbaranProveedor::class,
            FacturaProveedor::class
        ];

        foreach ($documentClasses as $documentClass) {
            // creamos un documento para la empresa por defecto y el proveedor con exención de iva
            $doc = new $documentClass();
            $doc->setSubject($supplierWithExemption);
            $this->assertTrue($doc->save(), 'cant-create-' . $doc->modelClassName() . '-with-supplier-with-exemption');

            // línea con producto con exención
            $line1 = $doc->getNewProductLine($productWithExemption->referencia);
            $this->assertEquals(TaxException::ES_TAX_EXCEPTION_22, $line1->excepcioniva, 'bad-line1-iva-exemption-in-' . $doc->modelClassName());
            $this->assertTrue($line1->save(), 'cant-save-line1-in-' . $doc->modelClassName());

            // línea con producto sin exención
            $line2 = $doc->getNewProductLine($productWithoutExemption->referencia);
            $this->assertEquals(TaxException::ES_TAX_EXCEPTION_21, $line2->excepcioniva, 'bad-line2-iva-exemption-in-' . $doc->modelClassName());
            $this->assertTrue($line2->save(), 'cant-save-line2-in-' . $doc->modelClassName());

            // línea en blanco sin producto
            $line3 = $doc->getNewLine();
            $this->assertEquals(TaxException::ES_TAX_EXCEPTION_21, $line3->excepcioniva, 'bad-line3-iva-exemption-in-' . $doc->modelClassName());
            $this->assertTrue($line3->save(), 'cant-save-line3-in-' . $doc->modelClassName());

            // creamos un documento para la empresa por defecto y el proveedor sin exención de iva
            $doc2 = new $documentClass();
            $doc2->setSubject($supplierWithoutExemption);
            $this->assertTrue($doc2->save(), 'cant-create-' . $doc2->modelClassName() . '-with-supplier-without-exemption');

            // línea con producto con exención
            $line4 = $doc2->getNewProductLine($productWithExemption->referencia);
            $this->assertEquals(TaxException::ES_TAX_EXCEPTION_22, $line4->excepcioniva, 'bad-line4-iva-exemption-in-' . $doc2->modelClassName());
            $this->assertTrue($line4->save(), 'cant-save-line4-in-' . $doc2->modelClassName());

            // línea con producto sin exención
            $line5 = $doc2->getNewProductLine($productWithoutExemption->referencia);
            $this->assertNull($line5->excepcioniva, 'bad-line5-iva-exemption-in-' . $doc2->modelClassName());
            $this->assertTrue($line5->save(), 'cant-save-line5-in-' . $doc2->modelClassName());

            // línea en blanco sin producto
            $line6 = $doc2->getNewLine();
            $this->assertNull($line6->excepcioniva, 'bad-line6-iva-exemption-in-' . $doc2->modelClassName());
            $this->assertTrue($line6->save(), 'cant-save-line6-in-' . $doc2->modelClassName());

            // creamos un documento para la empresa con exención de iva y el proveedor sin exención de iva
            $doc3 = new $documentClass();
            $doc3->setSubject($supplierWithoutExemption);
            $doc3->setWarehouse($warehouses[0]->codalmacen);
            $this->assertTrue($doc3->save(), 'cant-create-' . $doc3->modelClassName() . '-with-company-with-exemption-and-supplier-without-exemption');

            // línea con producto con exención
            $line7 = $doc3->getNewProductLine($productWithExemption->referencia);
            $this->assertEquals(TaxException::ES_TAX_EXCEPTION_22, $line7->excepcioniva, 'bad-line7-iva-exemption-in-' . $doc3->modelClassName());
            $this->assertTrue($line7->save(), 'cant-save-line7-in-' . $doc3->modelClassName());

            // línea con producto sin exención
            $line8 = $doc3->getNewProductLine($productWithoutExemption->referencia);
            $this->assertEquals(TaxException::ES_TAX_EXCEPTION_20, $line8->excepcioniva, 'bad-line8-iva-exemption-in-' . $doc3->modelClassName());
            $this->assertTrue($line8->save(), 'cant-save-line8-in-' . $doc3->modelClassName());

            // línea en blanco sin producto
            $line9 = $doc3->getNewLine();
            $this->assertEquals(TaxException::ES_TAX_EXCEPTION_20, $line9->excepcioniva, 'bad-line9-iva-exemption-in-' . $doc3->modelClassName());
            $this->assertTrue($line9->save(), 'cant-save-line9-in-' . $doc3->modelClassName());

            // eliminamos documentos
            $this->assertTrue($doc->delete(), 'cant-delete-' . $doc->modelClassName() . '-with-supplier-with-exemption');
            $this->assertTrue($doc2->delete(), 'cant-delete-' . $doc2->modelClassName() . '-with-supplier-without-exemption');
            $this->assertTrue($doc3->delete(), 'cant-delete-' . $doc3->modelClassName() . '-with-company-with-exemption-and-supplier-without-exemption');
        }

        // eliminamos
        $this->assertTrue($supplierWithExemption->delete(), 'cant-delete-supplier-with-exemption');
        $this->assertTrue($supplierWithoutExemption->delete(), 'cant-delete-supplier-without-exemption');
        $this->assertTrue($productWithExemption->delete(), 'cant-delete-product-with-exemption');
        $this->assertTrue($productWithoutExemption->delete(), 'cant-delete-product-without-exemption');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Prueba compra a proveedor en régimen general con empresa en régimen general.
     * IVA soportado deducible sobre el neto. Sin recargo de equivalencia.
     */
    public function testGeneralSupplierWithGeneralCompany(): void
    {
        // creamos una empresa con régimen general
        $company = $this->getRandomCompany();
        $company->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($company->save(), 'cant-create-company');

        // creamos un proveedor con régimen general
        $supplier = $this->getRandomSupplier();
        $supplier->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($supplier->save(), 'cant-create-supplier');

        // creamos los 4 tipos de documentos
        $this->createAndTestDocuments($company, $supplier, 'testGeneralSupplierWithGeneralCompany', [
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
        $this->assertTrue($supplier->delete(), 'cant-delete-supplier');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Prueba compra a proveedor en recargo con empresa NO en recargo.
     * No pagamos recargo porque nosotros (empresa) no estamos en recargo.
     */
    public function testSurchargeSupplierWithGeneralCompany(): void
    {
        // creamos una empresa con régimen general (NO recargo)
        $company = $this->getRandomCompany();
        $company->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($company->save(), 'cant-create-company');

        // creamos un proveedor con recargo de equivalencia
        $supplier = $this->getRandomSupplier();
        $supplier->regimeniva = TaxRegime::ES_TAX_REGIME_SURCHARGE;
        $this->assertTrue($supplier->save(), 'cant-create-supplier');

        // Solo IVA 21% sobre 200€ = 42, sin recargo (nosotros no estamos en recargo)
        $this->createAndTestDocuments($company, $supplier, 'testSurchargeSupplierWithGeneralCompany', [
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
        $this->assertTrue($supplier->delete(), 'cant-delete-supplier');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Prueba compra a proveedor en recargo con empresa también en recargo.
     * Pagamos IVA + recargo porque nosotros (empresa) también estamos en recargo.
     */
    public function testSurchargeSupplierWithSurchargeCompany(): void
    {
        // creamos una empresa con recargo de equivalencia
        $company = $this->getRandomCompany();
        $company->regimeniva = TaxRegime::ES_TAX_REGIME_SURCHARGE;
        $this->assertTrue($company->save(), 'cant-create-company');

        // creamos un proveedor con recargo de equivalencia
        $supplier = $this->getRandomSupplier();
        $supplier->regimeniva = TaxRegime::ES_TAX_REGIME_SURCHARGE;
        $this->assertTrue($supplier->save(), 'cant-create-supplier');

        // IVA 21% + Recargo 5.2% sobre 200€ = 42 + 10.4 = 52.4
        $this->createAndTestDocuments($company, $supplier, 'testSurchargeSupplierWithSurchargeCompany', [
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
        $this->assertTrue($supplier->delete(), 'cant-delete-supplier');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Prueba compra a proveedor general con empresa en recargo.
     * No pagamos recargo porque el proveedor no está en recargo.
     */
    public function testGeneralSupplierWithSurchargeCompany(): void
    {
        // creamos una empresa con recargo de equivalencia
        $company = $this->getRandomCompany();
        $company->regimeniva = TaxRegime::ES_TAX_REGIME_SURCHARGE;
        $this->assertTrue($company->save(), 'cant-create-company');

        // creamos un proveedor con régimen general
        $supplier = $this->getRandomSupplier();
        $supplier->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($supplier->save(), 'cant-create-supplier');

        // Solo IVA 21% sobre 200€ = 42, sin recargo (proveedor no está en recargo)
        $this->createAndTestDocuments($company, $supplier, 'testGeneralSupplierWithSurchargeCompany', [
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
        $this->assertTrue($supplier->delete(), 'cant-delete-supplier');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Prueba el régimen de bienes usados en compras.
     * Si nosotros también estamos en bienes usados y el producto es de segunda mano,
     * no aplicamos impuestos (compra sin IVA).
     */
    public function testUsedGoodsRegime(): void
    {
        // creamos una empresa con régimen de bienes usados
        $company = $this->getRandomCompany();
        $company->regimeniva = TaxRegime::ES_TAX_REGIME_USED_GOODS;
        $this->assertTrue($company->save(), 'cant-create-company');

        // creamos un producto de segunda mano
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

        // creamos un proveedor en bienes usados
        $supplier = $this->getRandomSupplier();
        $supplier->regimeniva = TaxRegime::ES_TAX_REGIME_USED_GOODS;
        $this->assertTrue($supplier->save(), 'cant-create-supplier');

        // En bienes usados, si nosotros también estamos en ese régimen,
        // la compra de productos de segunda mano no tiene IVA
        $this->createAndTestDocuments($company, $supplier, 'testUsedGoodsRegime', [
            'expected-doc-neto' => 200,
            'expected-doc-iva' => 0,
            'expected-doc-recargo' => 0,
            'expected-doc-irpf' => 0,
            'expected-doc-total' => 200,
            'expected-line-pvptotal' => 200,
            'expected-line-iva' => 0,
            'expected-line-recargo' => 0,
            'expected-line-irpf' => 0
        ], $product);

        // eliminamos
        $this->assertTrue($supplier->delete(), 'cant-delete-supplier');
        $this->assertTrue($product->delete(), 'cant-delete-product');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Prueba el régimen de criterio de caja en compras.
     * El cálculo es idéntico al general, solo difiere el momento del devengo.
     */
    public function testCashCriteriaRegime(): void
    {
        // creamos una empresa con régimen general
        $company = $this->getRandomCompany();
        $company->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($company->save(), 'cant-create-company');

        // creamos un proveedor con régimen de criterio de caja
        $supplier = $this->getRandomSupplier();
        $supplier->regimeniva = TaxRegime::ES_TAX_REGIME_CASH_CRITERIA;
        $this->assertTrue($supplier->save(), 'cant-create-supplier');

        // IVA 21% sobre 200€ = 42
        $this->createAndTestDocuments($company, $supplier, 'testCashCriteriaRegime', [
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
        $this->assertTrue($supplier->delete(), 'cant-delete-supplier');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Prueba compra a proveedor en régimen agrario.
     * IVA reducido sobre el neto. Sin recargo normalmente.
     */
    public function testAgrarianRegime(): void
    {
        // creamos una empresa con régimen general
        $company = $this->getRandomCompany();
        $company->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($company->save(), 'cant-create-company');

        // creamos un proveedor con régimen agrario
        $supplier = $this->getRandomSupplier();
        $supplier->regimeniva = TaxRegime::ES_TAX_REGIME_AGRARIAN;
        $this->assertTrue($supplier->save(), 'cant-create-supplier');

        // IVA 21% sobre 200€ = 42 (puede variar según el producto)
        $this->createAndTestDocuments($company, $supplier, 'testAgrarianRegime', [
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
        $this->assertTrue($supplier->delete(), 'cant-delete-supplier');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Prueba compra a proveedor en régimen simplificado.
     * El cálculo es similar al régimen general.
     */
    public function testSimplifiedRegime(): void
    {
        // creamos una empresa con régimen general
        $company = $this->getRandomCompany();
        $company->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($company->save(), 'cant-create-company');

        // creamos un proveedor con régimen simplificado
        $supplier = $this->getRandomSupplier();
        $supplier->regimeniva = TaxRegime::ES_TAX_REGIME_SIMPLIFIED;
        $this->assertTrue($supplier->save(), 'cant-create-supplier');

        // IVA 21% sobre 200€ = 42
        $this->createAndTestDocuments($company, $supplier, 'testSimplifiedRegime', [
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
        $this->assertTrue($supplier->delete(), 'cant-delete-supplier');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Prueba operaciones intracomunitarias en compras.
     * IVA = 0% con inversión del sujeto pasivo (autoliquidación).
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

        // creamos un proveedor de otro país de la UE
        $supplier = $this->getRandomSupplier();
        $supplier->cifnif = 'PT513969144';
        $supplier->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($supplier->save(), 'cant-create-supplier');
        $address = $supplier->getDefaultAddress();
        $address->codpais = 'PRT'; // Portugal
        $this->assertTrue($address->save(), 'cant-update-address');

        // IVA 0% en operación intracomunitaria (inversión del sujeto pasivo)
        $this->createAndTestDocuments($company, $supplier, 'testIntraCommunityOperation', [
            'expected-doc-neto' => 200,
            'expected-doc-iva' => 0,
            'expected-doc-recargo' => 0,
            'expected-doc-irpf' => 0,
            'expected-doc-total' => 200,
            'doc-operacion' => InvoiceOperation::ES_INTRA_COMMUNITY,
            'line-excepcioniva' => TaxException::ES_TAX_EXCEPTION_84,
            'line-iva' => 0,
            'line-codimpuesto' => 'IVA0',
            'expected-line-pvptotal' => 200,
            'expected-line-iva' => 0,
            'expected-line-recargo' => 0,
            'expected-line-irpf' => 0
        ]);

        // eliminamos
        $this->assertTrue($supplier->delete(), 'cant-delete-supplier');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Prueba operaciones de importación (compras fuera de la UE).
     * IVA = 0% en la factura del proveedor, se paga en aduanas.
     */
    public function testImportOperation(): void
    {
        // creamos una empresa
        $company = $this->getRandomCompany();
        $company->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($company->save(), 'cant-create-company');

        // creamos un proveedor fuera de la UE
        $supplier = $this->getRandomSupplier();
        $supplier->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($supplier->save(), 'cant-create-supplier');
        $address = $supplier->getDefaultAddress();
        $address->codpais = 'USA'; // Estados Unidos
        $this->assertTrue($address->save(), 'cant-update-address');

        // IVA 0% en importación (se paga en aduanas)
        $this->createAndTestDocuments($company, $supplier, 'testImportOperation', [
            'expected-doc-neto' => 200,
            'expected-doc-iva' => 0,
            'expected-doc-recargo' => 0,
            'expected-doc-irpf' => 0,
            'expected-doc-total' => 200,
            'doc-operacion' => InvoiceOperation::ES_IMPORT,
            'line-iva' => 0,
            'line-codimpuesto' => 'IVA0',
            'expected-line-pvptotal' => 200,
            'expected-line-iva' => 0,
            'expected-line-recargo' => 0,
            'expected-line-irpf' => 0
        ]);

        // eliminamos
        $this->assertTrue($supplier->delete(), 'cant-delete-supplier');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Prueba compra con descuento del 10%.
     * Neto con descuento: 200€ - 10% = 180€
     * IVA 21% sobre 180€ = 37.8€
     */
    public function testGeneralRegimeWithDiscount(): void
    {
        // creamos una empresa con régimen general
        $company = $this->getRandomCompany();
        $company->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($company->save(), 'cant-create-company');

        // creamos un proveedor con régimen general
        $supplier = $this->getRandomSupplier();
        $supplier->regimeniva = TaxRegime::ES_TAX_REGIME_GENERAL;
        $this->assertTrue($supplier->save(), 'cant-create-supplier');

        // Con descuento del 10%: Neto = 180, IVA = 37.8, Total = 217.8
        $this->createAndTestDocuments($company, $supplier, 'testGeneralRegimeWithDiscount', [
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
        $this->assertTrue($supplier->delete(), 'cant-delete-supplier');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Prueba compra con recargo y descuento del 5%.
     * Neto con descuento: 200€ - 5% = 190€
     * IVA 21% sobre 190€ = 39.9€
     * Recargo 5.2% sobre 190€ = 9.88€
     */
    public function testSurchargeRegimeWithDiscount(): void
    {
        // creamos una empresa con recargo de equivalencia
        $company = $this->getRandomCompany();
        $company->regimeniva = TaxRegime::ES_TAX_REGIME_SURCHARGE;
        $this->assertTrue($company->save(), 'cant-create-company');

        // creamos un proveedor con recargo de equivalencia
        $supplier = $this->getRandomSupplier();
        $supplier->regimeniva = TaxRegime::ES_TAX_REGIME_SURCHARGE;
        $this->assertTrue($supplier->save(), 'cant-create-supplier');

        // Con descuento del 5%: Neto = 190, IVA = 39.9, Recargo = 9.88, Total = 239.78
        $this->createAndTestDocuments($company, $supplier, 'testSurchargeRegimeWithDiscount', [
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
        $this->assertTrue($supplier->delete(), 'cant-delete-supplier');
        $this->assertTrue($company->delete(), 'cant-delete-company');
    }

    /**
     * Crea y prueba los 4 tipos de documentos de compra: presupuesto, pedido, albarán y factura.
     *
     * @param Empresa $company Empresa
     * @param Proveedor $supplier Proveedor
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
    private function createAndTestDocuments(Empresa $company, Proveedor $supplier, string $testName, array $data, ?Producto $product = null): void
    {
        $warehouse = null;
        $documentClasses = [
            PresupuestoProveedor::class,
            PedidoProveedor::class,
            AlbaranProveedor::class,
            FacturaProveedor::class
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

            // asignar proveedor
            $doc->setSubject($supplier);

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
            if (!($doc instanceof FacturaProveedor)) {
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