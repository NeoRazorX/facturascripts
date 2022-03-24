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
use FacturaScripts\Core\Model\Base\ModelCore;
use FacturaScripts\Core\Model\Almacen;
use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Core\Model\Fabricante;
use FacturaScripts\Core\Model\Familia;
use FacturaScripts\Core\Model\FormaPago;
use FacturaScripts\Core\Model\Impuesto;
use FacturaScripts\Core\Model\PresupuestoCliente;
use FacturaScripts\Core\Model\Producto;
use FacturaScripts\Core\Model\ProductoProveedor;
use FacturaScripts\Core\Model\Proveedor;
use FacturaScripts\Core\Model\Serie;
use FacturaScripts\Core\Model\Stock;
use FacturaScripts\Test\Core\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class ProductoTest extends TestCase
{
    use LogErrorsTrait;

    public function testCreate()
    {
        $product = $this->getTestProduct();
        $this->assertTrue($product->save(), 'product-cant-save');
        $this->assertNotNull($product->primaryColumnValue(), 'estado-product-not-stored');
        $this->assertTrue($product->exists(), 'product-cant-persist');
        $this->assertTrue($product->delete(), 'product-cant-delete');
    }

    public function testCreateWithOutReference()
    {
        $product = new Producto();
        $product->descripcion = 'Test Product';
        $this->assertFalse($product->save(), 'product-cant-save');
    }

    public function testBlocked()
    {
        $product = $this->getTestProduct();
        $product->secompra = true;
        $product->sevende = true;
        $product->publico = true;
        $product->bloqueado = true;
        $this->assertTrue($product->save(), 'product-cant-save');

        // leemos de la base de datos y comprobamos los valores guardados
        $product->loadFromCode($product->primaryColumnValue());
        $this->assertFalse($product->publico, 'product-blocked-can-public');
        $this->assertFalse($product->secompra, 'product-blocked-can-buy');
        $this->assertFalse($product->sevende, 'product-blocked-can-sale');
        $this->assertTrue($product->delete(), 'product-cant-delete');
    }

    public function testFamily()
    {
        // creamos la familia
        $family = new Familia();
        $family->descripcion = 'Test Family';
        $this->assertTrue($family->save(), 'family-cant-save');

        // creamos un producto con esta familia
        $product = $this->getTestProduct();
        $product->codfamilia = $family->primaryColumnValue();
        $this->assertTrue($product->save(), 'product-cant-save');

        // eliminamos la familia
        $this->assertTrue($family->delete(), 'product-cant-delete');

        // recargamos el producto para ver que se ha desvinculado la familia
        $product->loadFromCode($product->primaryColumnValue());
        $this->assertNull($product->codfamilia, 'product-family-not-empty');
        $this->assertTrue($product->delete(), 'product-cant-delete');
    }

    public function testManufacturer()
    {
        // creamos un fabricante
        $manufacturer = new Fabricante();
        $manufacturer->nombre = 'Test Manufacturer';
        $this->assertTrue($manufacturer->save(), 'manufacturer-cant-save');

        // creamos un producto con este fabricante
        $product = $this->getTestProduct();
        $product->codfabricante = $manufacturer->primaryColumnValue();
        $this->assertTrue($product->save(), 'product-cant-save');

        // eliminamos el fabricante
        $this->assertTrue($manufacturer->delete(), 'manufacturer-cant-delete');

        // recargamos el producto para ver que se ha desvinculado el fabricante
        $product->loadFromCode($product->primaryColumnValue());
        $this->assertNull($product->codfabricante, 'product-manufacturer-not-empty');
        $this->assertTrue($product->delete(), 'product-cant-delete');
    }

    public function testTax()
    {
        // creamos un impuesto
        $tax = new Impuesto();
        $tax->descripcion = 'Test Tax';
        $tax->iva = 99;
        $this->assertTrue($tax->save(), 'tax-cant-save');

        // creamos un producto con este impuesto
        $product = $this->getTestProduct();
        $product->codimpuesto = $tax->primaryColumnValue();
        $this->assertTrue($product->save(), 'product-cant-save');

        // eliminamos el impuesto
        $this->assertTrue($tax->delete(), 'tax-cant-delete');

        // recargamos el producto para ver que se ha desvinculado el impuesto
        $product->loadFromCode($product->primaryColumnValue());
        $this->assertNull($product->codimpuesto, 'product-tax-not-empty');
        $this->assertTrue($product->delete(), 'product-cant-delete');
    }

    public function testCostPriceNoPolicy()
    {
        // asignamos ninguna política de precio de coste
        $settings = new AppSettings();
        $settings->set('default', 'costpricepolicy', '');

        // creamos un producto con coste 100
        $product = $this->getTestProduct();
        $this->assertTrue($product->save(), 'product-cant-save');
        $variant = $product->getVariants()[0];
        $variant->coste = 100;
        $this->assertTrue($variant->save(), 'variant-cant-save');

        // creamos un proveedor
        $supplier = $this->getTestSupplier();
        $this->assertTrue($supplier->save(), 'supplier-cant-save');

        // creamos un producto de proveedor con este proveedor y este producto
        $supplierProduct = new ProductoProveedor();
        $supplierProduct->codproveedor = $supplier->codproveedor;
        $supplierProduct->referencia = $product->referencia;
        $supplierProduct->idproducto = $product->idproducto;
        $supplierProduct->precio = 200;
        $this->assertTrue($supplierProduct->save(), 'supplier-product-cant-save');

        // recargamos la variante para comprobar que NO se ha actualizado el coste, ya que no hay política asignada
        $variant->loadFromCode($variant->primaryColumnValue());
        $this->assertTrue($variant->coste == 100, 'variant-cost-should-not-change');

        // eliminamos
        $this->assertTrue($product->delete(), 'product-cant-delete');
        $this->assertTrue($supplier->delete(), 'supplier-cant-delete');
    }

    public function testCostPricePolicyLastPrice()
    {
        // asignamos la política de precio de coste último precio
        $settings = new AppSettings();
        $settings->set('default', 'costpricepolicy', 'last-price');

        // creamos un producto con coste 50
        $product = $this->getTestProduct();
        $this->assertTrue($product->save(), 'product-cant-save');
        $variant = $product->getVariants()[0];
        $variant->coste = 50;
        $this->assertTrue($variant->save(), 'variant-cant-save');

        // creamos el proveedor 1
        $supplier1 = $this->getTestSupplier();
        $this->assertTrue($supplier1->save(), 'supplier-1-cant-save');

        // creamos un producto de proveedor con este proveedor y este producto
        $supplierProduct1 = new ProductoProveedor();
        $supplierProduct1->codproveedor = $supplier1->codproveedor;
        $supplierProduct1->referencia = $product->referencia;
        $supplierProduct1->idproducto = $product->idproducto;
        $supplierProduct1->precio = 100;
        $supplierProduct1->actualizado = date(ModelCore::DATETIME_STYLE, strtotime("- 1 days"));
        $this->assertTrue($supplierProduct1->save(), 'supplier-product-1-cant-save');

        // creamos el proveedor 2
        $supplier2 = $this->getTestSupplier();
        $this->assertTrue($supplier2->save(), 'supplier-2-cant-save');

        // creamos un producto de proveedor con este proveedor y este producto
        $supplierProduct2 = new ProductoProveedor();
        $supplierProduct2->codproveedor = $supplier2->codproveedor;
        $supplierProduct2->referencia = $product->referencia;
        $supplierProduct2->idproducto = $product->idproducto;
        $supplierProduct2->precio = 200;
        $this->assertTrue($supplierProduct2->save(), 'supplier-product-2-cant-save');

        // recargamos la variante para comprobar que SI se ha actualizado el coste
        $variant->loadFromCode($variant->primaryColumnValue());
        $this->assertTrue($variant->coste == 200, 'variant-cost-not-last');

        // eliminamos
        $this->assertTrue($product->delete(), 'product-cant-delete');
        $this->assertTrue($supplier1->delete(), 'supplier-cant-delete');
        $this->assertTrue($supplier2->delete(), 'supplier-cant-delete');
    }

    public function testCostPricePolicyAveragePrice()
    {
        // asignamos la política de precio de coste último precio
        $settings = new AppSettings();
        $settings->set('default', 'costpricepolicy', 'average-price');

        // creamos un producto con coste 50
        $product = $this->getTestProduct();
        $this->assertTrue($product->save(), 'product-cant-save');
        $variant = $product->getVariants()[0];
        $variant->coste = 50;
        $this->assertTrue($variant->save(), 'variant-cant-save');

        // creamos el proveedor 1
        $supplier1 = $this->getTestSupplier();
        $this->assertTrue($supplier1->save(), 'supplier-1-cant-save');

        // creamos un producto de proveedor con este proveedor y este producto
        $supplierProduct1 = new ProductoProveedor();
        $supplierProduct1->codproveedor = $supplier1->codproveedor;
        $supplierProduct1->referencia = $product->referencia;
        $supplierProduct1->idproducto = $product->idproducto;
        $supplierProduct1->precio = 100;
        $this->assertTrue($supplierProduct1->save(), 'supplier-product-cant-save');

        // creamos el proveedor 1
        $supplier2 = $this->getTestSupplier();
        $this->assertTrue($supplier2->save(), 'supplier-2-cant-save');

        // creamos un producto de proveedor con este proveedor y este producto
        $supplierProduct2 = new ProductoProveedor();
        $supplierProduct2->codproveedor = $supplier2->codproveedor;
        $supplierProduct2->referencia = $product->referencia;
        $supplierProduct2->idproducto = $product->idproducto;
        $supplierProduct2->precio = 200;
        $this->assertTrue($supplierProduct2->save(), 'supplier-product-cant-save');

        // recargamos la variante para comprobar que SI se ha actualizado el coste
        $variant->loadFromCode($variant->primaryColumnValue());
        $this->assertTrue($variant->coste == 150, 'variant-cost-not-average');

        // eliminamos
        $this->assertTrue($product->delete(), 'product-cant-delete');
        $this->assertTrue($supplier1->delete(), 'supplier-cant-delete');
        $this->assertTrue($supplier2->delete(), 'supplier-cant-delete');
    }

    public function testStock()
    {
        // creamos el producto
        $product = $this->getTestProduct();
        $this->assertTrue($product->save(), 'product-cant-save');

        // creamos el stock
        $stock = new Stock();
        $stock->idproducto = $product->idproducto;
        $stock->referencia = $product->referencia;
        $stock->cantidad = 99;
        $this->assertTrue($stock->save(), 'stock-cant-save');

        // recargamos el producto para comprobar que el stock se ha actualizado
        $product->loadFromCode($product->primaryColumnValue());
        $variant = $product->getVariants()[0];
        $this->assertTrue($product->stockfis == $stock->cantidad, 'product-different-stock');
        $this->assertTrue($variant->stockfis == $stock->cantidad, 'variant-different-stock');

        // indicamos que el producto no tiene stock
        $product->nostock = true;
        $this->assertTrue($product->save(), 'product-cant-save');

        // recargamos el producto para comprobar que el stock se ha actualizado
        $product->loadFromCode($product->primaryColumnValue());
        $this->assertEquals(0, $product->stockfis, 'product-cant-stock');
        $variant->loadFromCode($variant->primaryColumnValue());
        $this->assertEquals(0, $variant->stockfis, 'variant-cant-stock');

        // Comprobamos que el stock ha sido eliminado
        $this->assertFalse($stock->loadFromCode($stock->primaryColumnValue()), 'stock-cant-load-after-delete');

        // eliminamos
        $this->assertTrue($product->delete(), 'product-cant-delete');
    }

    public function testVariante()
    {
        // creamos un producto con precio
        $product = $this->getTestProduct();
        $product->precio = 10;
        $this->assertTrue($product->save(), 'product-cant-save');

        // obtenemos las variantes
        $variants = $product->getVariants();
        $this->assertCount(1, $variants, 'product-more-than-one-variant');

        // comprobamos que precio y referencia son correctos
        $this->assertEquals($product->referencia, $variants[0]->referencia, 'product-variant-different-reference');
        $this->assertEquals($product->precio, $variants[0]->precio, 'product-variant-different-price');

        // comprobamos que no podemos eliminar la única variante
        $this->assertFalse($variants[0]->delete(), 'can-delete-only-variant');

        // modificamos el precio de la variante
        $variants[0]->precio = 133;
        $this->assertTrue($variants[0]->save(), 'variant-cant-save');

        // recargamos producto y comprobamos que se ha actualizado el precio
        $this->assertTrue($product->loadFromCode($product->idproducto));
        $this->assertEquals(133, $product->precio, 'product-price-not-updated');

        // comprobamos que el precio se calcula a partir de coste y margen
        $variants[0]->coste = 100;
        $variants[0]->margen = 20;
        $this->assertTrue($variants[0]->save(), 'variant-cant-save');

        // recargamos variante y comprobamos precio
        $this->assertTrue($variants[0]->loadFromCode($variants[0]->primaryColumnValue()), 'cant-reload-variant');
        $this->assertEquals(120, $variants[0]->precio, 'variant-difference-price');

        // recargamos producto y comprobamos precio
        $this->assertTrue($product->loadFromCode($product->idproducto));
        $this->assertEquals(120, $product->precio, 'product-price-not-updated');

        // eliminamos
        $this->assertTrue($product->delete(), 'product-cant-delete');
        $this->assertFalse($variants[0]->exists(), 'variant-still-exists');
    }

    public function testNegativePrice()
    {
        /// create a product with negative price
        $product = $this->getTestProduct();
        $product->precio = -10;
        $this->assertTrue($product->save(), 'product-cant-save');

        /// check negative price for product
        $product->loadFromCode($product->primaryColumnValue());
        $this->assertTrue(($product->precio == -10), 'product-negative-price-error');

        /// create customer
        $customer = new Cliente();
        $customer->cifnif = '1234';
        $customer->nombre = 'Pepe Sales';
        $this->assertTrue($customer->save(), 'cliente-save-error');

        /// create a budget for customer
        $budget = new PresupuestoCliente();
        $budget->setSubject($customer);
        $warehouseModel = new Almacen();
        foreach ($warehouseModel->all() as $warehouse) {
            $budget->codalmacen = $warehouse->codalmacen;
            break;
        }
        $paymentModel = new FormaPago();
        foreach ($paymentModel->all() as $payment) {
            $budget->codpago = $payment->codpago;
            break;
        }
        $serieModel = new Serie();
        foreach ($serieModel->all() as $serie) {
            $budget->codserie = $serie->codserie;
            break;
        }

        $this->assertTrue($budget->save(), $budget->modelClassName() . '-save-error');

        /// creating line
        $newLine = $budget->getNewProductLine($product->referencia);
        $this->assertTrue($newLine->save(), $newLine->modelClassName() . '-save-error');

        /// check negative price for budget line
        $newLine->loadFromCode($newLine->primaryColumnValue());
        $this->assertTrue(($newLine->pvpunitario == -10), 'doc-line-negative-price-error');

        /// remove budget
        $this->assertTrue($budget->delete(), $budget->modelClassName() . '-delete-error');

        /// get contact to remove
        $contact = $customer->getDefaultAddress();

        /// remove customer
        $this->assertTrue($customer->delete(), 'cliente-delete-error');

        /// remove the pending contact
        $this->assertTrue($contact->delete(), 'contacto-delete-error');

        // remove product
        $this->assertTrue($product->delete(), 'product-cant-delete');
    }

    private function getTestProduct(): Producto
    {
        $product = new Producto();
        $product->referencia = 'Test';
        $product->descripcion = 'Test Product';
        return $product;
    }

    private function getTestSupplier(): Proveedor
    {
        $num = mt_rand(1, 999);
        $supplier = new Proveedor();
        $supplier->codproveedor = 'TEST' . $num;
        $supplier->nombre = 'Test Supplier ' . $num;
        $supplier->cifnif = $num . '345678A';
        return $supplier;
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
