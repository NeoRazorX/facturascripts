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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Almacen;
use FacturaScripts\Core\Model\AttachedFile;
use FacturaScripts\Core\Model\Base\ModelCore;
use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Core\Model\Fabricante;
use FacturaScripts\Core\Model\Familia;
use FacturaScripts\Core\Model\FormaPago;
use FacturaScripts\Core\Model\Impuesto;
use FacturaScripts\Core\Model\PresupuestoCliente;
use FacturaScripts\Core\Model\Producto;
use FacturaScripts\Core\Model\ProductoImagen;
use FacturaScripts\Core\Model\ProductoProveedor;
use FacturaScripts\Core\Model\Proveedor;
use FacturaScripts\Core\Model\Serie;
use FacturaScripts\Core\Model\Stock;
use FacturaScripts\Core\Model\Variante;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class ProductoTest extends TestCase
{
    use LogErrorsTrait;

    const TEST_REFERENCE = 'Test';

    public static function setUpBeforeClass(): void
    {
        $productModel = new Producto();
        $where = [new DataBaseWhere('referencia', self::TEST_REFERENCE)];
        foreach ($productModel->all($where, [], 0, 0) as $product) {
            $product->delete();
        }
    }

    public function testCreate(): void
    {
        // creamos un producto
        $product = $this->getTestProduct();
        $this->assertTrue($product->save(), 'product-cant-save');
        $this->assertNotNull($product->primaryColumnValue(), 'estado-product-not-stored');
        $this->assertTrue($product->exists(), 'product-cant-persist');

        // lo eliminamos
        $this->assertTrue($product->delete(), 'product-cant-delete');
    }

    public function testCreateWithOutReference(): void
    {
        // creamos un producto sin referencia
        $product = new Producto();
        $product->descripcion = 'Test Product';
        $this->assertTrue($product->save(), 'product-cant-save-without-ref');

        // lo eliminamos
        $this->assertTrue($product->delete(), 'product-cant-delete-without-ref');
    }

    public function textCreateWithNullDescription(): void
    {
        // creamos un producto con descripción nula
        $product = new Producto();
        $product->referencia = self::TEST_REFERENCE;
        $this->assertTrue($product->save(), 'product-cant-save-with-null-description');

        // recargamos el producto para comprobar que se ha guardado correctamente
        $product->loadFromCode($product->primaryColumnValue());

        // comprobamos que la descripción no es nula
        $this->assertNotNull($product->descripcion, 'product-description-is-null');

        // lo eliminamos
        $this->assertTrue($product->delete(), 'product-cant-delete-with-null-description');
    }

    public function textCreateWithNullObservations(): void
    {
        // creamos un producto con observaciones nulas
        $product = new Producto();
        $product->referencia = self::TEST_REFERENCE;
        $product->descripcion = 'Test Product';
        $this->assertTrue($product->save(), 'product-cant-save-with-null-observations');

        // recargamos el producto para comprobar que se ha guardado correctamente
        $product->loadFromCode($product->primaryColumnValue());

        // comprobamos que las observaciones no son nulas
        $this->assertNotNull($product->observaciones, 'product-observations-is-null');

        // lo eliminamos
        $this->assertTrue($product->delete(), 'product-cant-delete-with-null-observations');
    }

    public function testBlocked(): void
    {
        // creamos un producto
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

        // lo eliminamos
        $this->assertTrue($product->delete(), 'product-cant-delete');
    }

    public function testFamily(): void
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

        // lo eliminamos
        $this->assertTrue($product->delete(), 'product-cant-delete');
    }

    public function testManufacturer(): void
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

        // lo eliminamos
        $this->assertTrue($product->delete(), 'product-cant-delete');
    }

    public function testTax(): void
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

        // lo eliminamos
        $this->assertTrue($product->delete(), 'product-cant-delete');
    }

    public function testCostPriceNoPolicy(): void
    {
        // asignamos ninguna política de precio de coste
        Tools::settingsSet('default', 'costpricepolicy', '');

        // creamos un producto con coste 100
        $product = $this->getTestProduct();
        $this->assertTrue($product->save(), 'product-cant-save');
        $variant = $product->getVariants()[0];
        $variant->coste = 66;
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
        $this->assertEquals(66, $variant->coste, 'variant-cost-should-not-change');

        // eliminamos
        $this->assertTrue($product->delete(), 'product-cant-delete');
        $this->assertTrue($supplier->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($supplier->delete(), 'supplier-cant-delete');
    }

    public function testCostPricePolicyLastPrice(): void
    {
        // asignamos la política de precio de coste último precio
        Tools::settingsSet('default', 'costpricepolicy', 'last-price');

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
        $this->assertEquals(200, $variant->coste, 'variant-cost-not-last');

        // eliminamos
        $this->assertTrue($product->delete(), 'product-cant-delete');
        $this->assertTrue($supplier1->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($supplier1->delete(), 'supplier-cant-delete');
        $this->assertTrue($supplier2->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($supplier2->delete(), 'supplier-cant-delete');
    }

    public function testCostPricePolicyHighPrice(): void
    {
        // asignamos la política de precio de coste precio más alto
        Tools::settingsSet('default', 'costpricepolicy', 'high-price');

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
        $supplierProduct1->precio = 200;
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
        $supplierProduct2->precio = 100;
        $this->assertTrue($supplierProduct2->save(), 'supplier-product-2-cant-save');

        // recargamos la variante para comprobar que SI se ha actualizado el coste
        $variant->loadFromCode($variant->primaryColumnValue());
        $this->assertEquals(200, $variant->coste, 'variant-cost-not-last');

        // eliminamos
        $this->assertTrue($product->delete(), 'product-cant-delete');
        $this->assertTrue($supplier1->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($supplier1->delete(), 'supplier-cant-delete');
        $this->assertTrue($supplier2->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($supplier2->delete(), 'supplier-cant-delete');
    }

    public function testCostPricePolicyAveragePrice(): void
    {
        // asignamos la política de precio de coste precio medio
        Tools::settingsSet('default', 'costpricepolicy', 'average-price');

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
        $this->assertEquals(150, $variant->coste, 'variant-cost-not-average');

        // eliminamos
        $this->assertTrue($product->delete(), 'product-cant-delete');
        $this->assertTrue($supplier1->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($supplier1->delete(), 'supplier-cant-delete');
        $this->assertTrue($supplier2->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($supplier2->delete(), 'supplier-cant-delete');
    }

    public function testStock(): void
    {
        // creamos un producto
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

    public function testVariante(): void
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

    public function testVarianteWithoutRef(): void
    {
        // creamos un producto
        $product = $this->getTestProduct();
        $this->assertTrue($product->save(), 'product-cant-save');

        // añadimos una variante sin referencia
        $variant = new Variante();
        $variant->idproducto = $product->idproducto;
        $this->assertTrue($variant->save(), 'variant-cant-save-without-ref');

        // eliminamos
        $this->assertTrue($product->delete(), 'product-cant-delete');
        $this->assertFalse($variant->exists(), 'variant-still-exists');
    }

    public function testVarianteWithRef(): void
    {
        // creamos un producto
        $product = $this->getTestProduct();
        $this->assertTrue($product->save(), 'product-cant-save');

        // añadimos una variante con referencia
        $variant = new Variante();
        $variant->idproducto = $product->idproducto;
        $variant->referencia = '0' . $product->referencia;
        $this->assertTrue($variant->save(), 'variant-cant-save-with-ref');

        // comprobamos que la referencia del producto no se ha modificado
        $ref = $product->referencia;
        $this->assertTrue($product->loadFromCode($product->idproducto), 'cant-reload-product');
        $this->assertEquals($ref, $product->referencia, 'product-reference-changed');

        // eliminamos variante
        $this->assertTrue($variant->delete(), 'variant-cant-delete: '
            . $variant->referencia . ' === ' . $variant->getProducto()->referencia);

        // comprobamos que no podemos eliminar la única variante
        $where = [new DataBaseWhere('referencia', $product->referencia)];
        $this->assertTrue($variant->loadFromCode('', $where), 'cant-reload-variant');
        $this->assertFalse($variant->delete(), 'can-delete-only-variant');

        // eliminamos el producto
        $this->assertTrue($product->delete(), 'product-cant-delete');
        $this->assertFalse($variant->exists(), 'variant-still-exists');
    }

    public function testNegativePrice(): void
    {
        // creamos un producto con precio negativo
        $product = $this->getTestProduct();
        $product->precio = -10;
        $this->assertTrue($product->save(), 'product-cant-save');

        // comprobamos que no se ha alterado el precio
        $product->loadFromCode($product->primaryColumnValue());
        $this->assertEquals(-10, $product->precio, 'product-negative-price-error');

        // creamos un cliente
        $customer = new Cliente();
        $customer->cifnif = '1234';
        $customer->nombre = 'Pepe Sales';
        $this->assertTrue($customer->save(), 'cliente-save-error');

        // hacemos un presupuesto
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

        // añadimos el producto al presupuesto
        $newLine = $budget->getNewProductLine($product->referencia);
        $this->assertTrue($newLine->save(), $newLine->modelClassName() . '-save-error');

        // comprobamos que el precio es el original
        $this->assertEquals(-10, $newLine->pvpunitario, 'doc-line-negative-price-error');

        // eliminamos
        $this->assertTrue($budget->delete(), $budget->modelClassName() . '-delete-error');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($customer->delete(), 'cliente-delete-error');
        $this->assertTrue($product->delete(), 'product-cant-delete');
    }

    public function testCantDeleteWithDocuments(): void
    {
        // creamos un producto
        $product = $this->getTestProduct();
        $this->assertTrue($product->save(), 'product-cant-save');

        // creamos un cliente
        $customer = new Cliente();
        $customer->cifnif = '1234';
        $customer->nombre = 'Pepe Sales';
        $this->assertTrue($customer->save(), 'cliente-save-error');

        // hacemos un presupuesto
        $budget = new PresupuestoCliente();
        $budget->setSubject($customer);
        $this->assertTrue($budget->save(), $budget->modelClassName() . '-save-error');

        // añadimos el producto al presupuesto
        $newLine = $budget->getNewProductLine($product->referencia);
        $this->assertTrue($newLine->save(), $newLine->modelClassName() . '-save-error');

        // comprobamos que no podemos eliminar el producto
        $this->assertFalse($product->delete(), 'product-can-delete-with-documents');

        // eliminamos
        $this->assertTrue($budget->delete(), $budget->modelClassName() . '-delete-error');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($customer->delete(), 'cliente-delete-error');
        $this->assertTrue($product->delete(), 'product-cant-delete');
    }

    public function testDeleteImages(): void
    {
        // creamos un producto
        $product = $this->getTestProduct();
        $this->assertTrue($product->save(), 'product-cant-save');

        // añadimos una variante
        $variant = new Variante();
        $variant->idproducto = $product->idproducto;
        $variant->referencia = 'test-2';
        $this->assertTrue($variant->save(), 'variant-cant-save');

        // copiamos una imagen
        $original = 'xss_img_src_onerror_alert(123).jpeg';
        $originalPath = FS_FOLDER . '/Test/__files/' . $original;
        $this->assertTrue(copy($originalPath, FS_FOLDER . '/MyFiles/' . $original), 'File not copied');

        // guardamos la imagen
        $model = new AttachedFile();
        $model->path = $original;
        $this->assertTrue($model->save(), 'can-not-save-file');

        // añadimos una imagen al producto
        $imageProducto = new ProductoImagen();
        $imageProducto->idproducto = $product->idproducto;
        $imageProducto->idfile = $model->idfile;
        $this->assertTrue($imageProducto->save(), 'can-not-save-image-producto');

        // comprobamos que el producto tiene imágenes
        $this->assertCount(1, $product->getImages(false), 'product-no-images');

        // añadimos una imagen a la variante
        $imageVariante = new ProductoImagen();
        $imageVariante->idproducto = $product->idproducto;
        $imageVariante->referencia = $variant->referencia;
        $imageVariante->idfile = $model->idfile;
        $this->assertTrue($imageVariante->save(), 'can-not-save-image-variante');

        // comprobamos que la variante tiene imágenes
        $this->assertCount(1, $variant->getImages(false), 'variant-no-images');

        // eliminamos la variante
        $this->assertTrue($variant->delete(), 'variant-cant-delete');

        // comprobamos que la variante no tiene imágenes
        $this->assertCount(0, $variant->getImages(false), 'variant-images-not-deleted');

        // eliminamos el producto
        $this->assertTrue($product->delete(), 'product-cant-delete');

        // comprobamos que el producto no tiene imágenes
        $this->assertCount(0, $product->getImages(false), 'product-images-not-deleted');

        // eliminamos la imagen subida
        $this->assertTrue($model->delete(), 'can-not-delete-file');
    }

    public function testDetectChangesWithLeadingZeroes(): void
    {
        // creamos un producto
        $product = $this->getTestProduct();
        $product->referencia = "0001";
        $this->assertTrue($product->save(), 'product-cant-save');

        // eliminamos dos ceros a la referencia de la variante
        $variante = $product->getVariants()[0];
        $variante->referencia = '01';
        $variante->save();

        // refrescamos los datos desde la base de datos
        $product->loadFromCode($product->idproducto);

        // comprobamos que se haya actualizado la referencia en el producto
        $this->assertEquals('01', $product->referencia);

        // eliminamos
        $this->assertTrue($product->delete(), 'product-cant-delete');
    }

    private function getTestProduct(): Producto
    {
        $product = new Producto();
        $product->referencia = self::TEST_REFERENCE;
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
