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
use FacturaScripts\Core\Model\Fabricante;
use FacturaScripts\Core\Model\Familia;
use FacturaScripts\Core\Model\Impuesto;
use FacturaScripts\Core\Model\Producto;
use FacturaScripts\Core\Model\ProductoProveedor;
use FacturaScripts\Core\Model\Proveedor;
use FacturaScripts\Core\Model\Stock;
use FacturaScripts\Test\Core\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class ProductoTest extends TestCase
{
    use LogErrorsTrait;

    /*
     * Test Almacen: crear y no poder borrar todos.
     */

    public function testBlocked()
    {
        $product = $this->getTestProduct();
        $product->secompra = true;
        $product->sevende = true;
        $product->publico = true;
        $product->bloqueado = true;
        $this->assertTrue($product->save(), 'product-cant-save');
        $product->loadFromCode($product->primaryColumnValue());
        $this->assertFalse($product->publico, 'product-blocked-can-public');
        $this->assertFalse($product->secompra, 'product-blocked-can-buy');
        $this->assertFalse($product->sevende, 'product-blocked-can-sale');
        $this->assertTrue($product->delete(), 'product-cant-delete');
    }

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

    public function testFamily()
    {
        $family = new Familia();
        $family->descripcion = 'Test Family';
        $this->assertTrue($family->save(), 'family-cant-save');

        $product = $this->getTestProduct();
        $product->codfamilia = $family->primaryColumnValue();
        $this->assertTrue($product->save(), 'product-cant-save');

        $this->assertTrue($family->delete(), 'product-cant-delete');
        $product->loadFromCode($product->primaryColumnValue());
        $this->assertTrue(empty($product->codfamilia), 'product-family-not-empty');
        $this->assertTrue($product->delete(), 'product-cant-delete');
    }

    public function testManufacturer()
    {
        $manufacturer = new Fabricante();
        $manufacturer->nombre = 'Test Manufacturer';
        $this->assertTrue($manufacturer->save(), 'manufacturer-cant-save');

        $product = $this->getTestProduct();
        $product->codfabricante = $manufacturer->primaryColumnValue();
        $this->assertTrue($product->save(), 'product-cant-save');

        $this->assertTrue($manufacturer->delete(), 'manufacturer-cant-delete');
        $product->loadFromCode($product->primaryColumnValue());
        $this->assertTrue(empty($product->codfabricante), 'product-manufacturer-not-empty');
        $this->assertTrue($product->delete(), 'product-cant-delete');
    }

    public function testCostPricePolicy1()
    {
        $settings = new AppSettings();
        $settings->set('default', 'costpricepolicy', '');

        $product = $this->getTestProduct();
        $this->assertTrue($product->save(), 'product-cant-save');

        $variant = $product->getVariants()[0];
        $variant->coste = 100;
        $this->assertTrue($variant->save(), 'variant-cant-save');

        $supplier = new Proveedor();
        $supplier->codproveedor = 'Test';
        $supplier->nombre = 'Test Supplier';
        $supplier->cifnif = '12345678A';
        $this->assertTrue($supplier->save(), 'supplier-cant-save');

        $supplierProduct = new ProductoProveedor();
        $supplierProduct->codproveedor = $supplier->codproveedor;
        $supplierProduct->referencia = $product->referencia;
        $supplierProduct->idproducto = $product->idproducto;
        $supplierProduct->precio = 200;
        $this->assertTrue($supplierProduct->save(), 'supplier-product-cant-save');

        $variant->loadFromCode($variant->primaryColumnValue());
        $this->assertTrue($variant->coste == 100, 'variant-cost-should-not-change');

        $this->assertTrue($product->delete(), 'product-cant-delete');
        $this->assertTrue($supplierProduct->delete(), 'supplier-product-cant-delete');
        $this->assertTrue($supplier->delete(), 'supplier-cant-delete');
    }

    public function testCostPricePolicy2()
    {
        $settings = new AppSettings();
        $settings->set('default', 'costpricepolicy', 'last-price');

        $product = $this->getTestProduct();
        $this->assertTrue($product->save(), 'product-cant-save');

        $variant = $product->getVariants()[0];
        $variant->coste = 50;
        $this->assertTrue($variant->save(), 'variant-cant-save');

        $supplier1 = new Proveedor();
        $supplier1->codproveedor = 'Test1';
        $supplier1->nombre = 'Test Supplier 1';
        $supplier1->cifnif = '12345678A';
        $this->assertTrue($supplier1->save(), 'supplier-cant-save');

        $supplierProduct1 = new ProductoProveedor();
        $supplierProduct1->codproveedor = $supplier1->codproveedor;
        $supplierProduct1->referencia = $product->referencia;
        $supplierProduct1->idproducto = $product->idproducto;
        $supplierProduct1->precio = 100;
        $supplierProduct1->actualizado = date(
            ProductoProveedor::DATETIME_STYLE,
            strtotime("- 1 days")
        );
        $this->assertTrue($supplierProduct1->save(), 'supplier-product-cant-save');

        $supplier2 = new Proveedor();
        $supplier2->codproveedor = 'Test2';
        $supplier2->nombre = 'Test Supplier 2';
        $supplier2->cifnif = '12345678A';
        $this->assertTrue($supplier2->save(), 'supplier-cant-save');

        $supplierProduct2 = new ProductoProveedor();
        $supplierProduct2->codproveedor = $supplier2->codproveedor;
        $supplierProduct2->referencia = $product->referencia;
        $supplierProduct2->idproducto = $product->idproducto;
        $supplierProduct2->precio = 200;
        $this->assertTrue($supplierProduct2->save(), 'supplier-product-cant-save');

        $variant->loadFromCode($variant->primaryColumnValue());
        $this->assertTrue($variant->coste == 200, 'variant-cost-not-last');

        $this->assertTrue($product->delete(), 'product-cant-delete');
        $this->assertTrue($supplierProduct1->delete(), 'supplier-product-cant-delete');
        $this->assertTrue($supplierProduct2->delete(), 'supplier-product-cant-delete');
        $this->assertTrue($supplier1->delete(), 'supplier-cant-delete');
        $this->assertTrue($supplier2->delete(), 'supplier-cant-delete');
    }

    public function testCostPricePolicy3()
    {
        $settings = new AppSettings();
        $settings->set('default', 'costpricepolicy', 'average-price');

        $product = $this->getTestProduct();
        $this->assertTrue($product->save(), 'product-cant-save');

        $variant = $product->getVariants()[0];
        $variant->coste = 50;
        $this->assertTrue($variant->save(), 'variant-cant-save');

        $supplier1 = new Proveedor();
        $supplier1->codproveedor = 'Test1';
        $supplier1->nombre = 'Test Supplier 1';
        $supplier1->cifnif = '12345678A';
        $this->assertTrue($supplier1->save(), 'supplier-cant-save');

        $supplierProduct1 = new ProductoProveedor();
        $supplierProduct1->codproveedor = $supplier1->codproveedor;
        $supplierProduct1->referencia = $product->referencia;
        $supplierProduct1->idproducto = $product->idproducto;
        $supplierProduct1->precio = 100;
        $this->assertTrue($supplierProduct1->save(), 'supplier-product-cant-save');

        $supplier2 = new Proveedor();
        $supplier2->codproveedor = 'Test2';
        $supplier2->nombre = 'Test Supplier 2';
        $supplier2->cifnif = '12345678A';
        $this->assertTrue($supplier2->save(), 'supplier-cant-save');

        $supplierProduct2 = new ProductoProveedor();
        $supplierProduct2->codproveedor = $supplier2->codproveedor;
        $supplierProduct2->referencia = $product->referencia;
        $supplierProduct2->idproducto = $product->idproducto;
        $supplierProduct2->precio = 200;
        $this->assertTrue($supplierProduct2->save(), 'supplier-product-cant-save');

        $variant->loadFromCode($variant->primaryColumnValue());
        $this->assertTrue($variant->coste == 150, 'variant-cost-not-average');

        $this->assertTrue($product->delete(), 'product-cant-delete');
        $this->assertTrue($supplierProduct1->delete(), 'supplier-product-cant-delete');
        $this->assertTrue($supplierProduct2->delete(), 'supplier-product-cant-delete');
        $this->assertTrue($supplier1->delete(), 'supplier-cant-delete');
        $this->assertTrue($supplier2->delete(), 'supplier-cant-delete');
    }

    public function testStock()
    {
        $product = $this->getTestProduct();
        $this->assertTrue($product->save(), 'product-cant-save');

        $stock = new Stock();
        $stock->idproducto = $product->idproducto;
        $stock->referencia = $product->referencia;
        $stock->codalmacen = AppSettings::get('default', 'codalmacen');
        $stock->cantidad = 99;
        $this->assertTrue($stock->save(), 'stock-cant-save');

        $product->loadFromCode($product->primaryColumnValue());
        $variant = $product->getVariants()[0];
        $this->assertTrue($product->stockfis == $stock->cantidad, 'product-different-stock');
        $this->assertTrue($variant->stockfis == $stock->cantidad, 'variant-different-stock');

        $product->nostock = true;
        $this->assertTrue($product->save(), 'product-cant-save');
        $product->loadFromCode($product->primaryColumnValue());
        $variant->loadFromCode($variant->primaryColumnValue());
        $this->assertTrue($product->stockfis == 0, 'product-cant-stock');
        $this->assertTrue($variant->stockfis == 0, 'variant-cant-stock');
        $this->assertFalse($stock->loadFromCode($stock->primaryColumnValue()), 'stock-cant-load-after-delete');
        $this->assertTrue($product->delete(), 'product-cant-delete');
    }

    public function testTax()
    {
        $tax = new Impuesto();
        $tax->descripcion = 'Test Tax';
        $tax->iva = 99;
        $this->assertTrue($tax->save(), 'tax-cant-save');

        $product = $this->getTestProduct();
        $product->codimpuesto = $tax->primaryColumnValue();
        $this->assertTrue($product->save(), 'product-cant-save');

        $this->assertTrue($tax->delete(), 'tax-cant-delete');
        $product->loadFromCode($product->primaryColumnValue());
        $this->assertTrue(empty($product->codimpuesto), 'product-tax-not-empty');
        $this->assertTrue($product->delete(), 'product-cant-delete');
    }

    public function testVariante()
    {
        $product = $this->getTestProduct();
        $product->precio = 10;
        $this->assertTrue($product->save(), 'product-cant-save');

        $variants = $product->getVariants();
        $this->assertEquals(1, count($variants), 'product-more-than-one-variant');

        $variant = $variants[0];
        $this->assertTrue($variant->referencia == $product->referencia, 'product-variant-diferent-reference');
        $this->assertTrue($variant->precio == $product->precio, 'product-variant-diferent-price');

        $product->price = 50;
        $product->save();
        $variant->loadFromCode($variant->primaryColumnValue());
        $this->assertTrue($variant->precio == $product->precio, 'product-variant-diferent-price');

        $variant->coste = 100;
        $variant->margen = 20;
        $this->assertTrue($variant->save(), 'variant-cant-save');
        $variant->loadFromCode($variant->primaryColumnValue());
        $this->assertTrue($variant->precio == 120, 'variant-difference-price');

        $this->assertFalse($variant->delete(), 'variant-cant-delete');
        $this->assertTrue($product->delete(), 'product-cant-delete');
        $this->assertEquals(0, count($product->getVariants()), 'variant-must-be-delete');
    }

     /* Para una variante,
      * si tiene precio de coste y se aplica un margen (modificas el margen) se tiene que recalcular el precio de venta */


    protected function tearDown()
    {
        $this->logErrors();
    }

    private function getTestProduct()
    {
        $product = new Producto();
        $product->referencia = 'Test';
        $product->descripcion = 'Test Product';
        return $product;
    }
}
