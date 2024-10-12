<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\DataSrc\Divisas;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Model\AlbaranProveedor;
use FacturaScripts\Core\Model\Divisa;
use FacturaScripts\Core\Model\Producto;
use FacturaScripts\Core\Model\ProductoProveedor;
use FacturaScripts\Core\Model\Proveedor;
use FacturaScripts\Core\Model\Variante;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\WorkQueue;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class ProductoProveedorTest
 * @package FacturaScripts\Test\Core\Base\Model
 */
final class ProductoProveedorTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;
    use DefaultSettingsTrait;

    protected function setUp(): void
    {
        self::setDefaultSettings();
    }

    /**
     * Comprobamos que se puede crear un ProductoProveedor
     */
    public function testCreate(): void
    {
        // creamos un proveedor
        $proveedor = $this->getRandomSupplier();
        $this->assertTrue($proveedor->save());

        // creamos un producto del proveedor
        $productoProveedor = new ProductoProveedor();
        $productoProveedor->referencia = 'test';
        $productoProveedor->codproveedor = $proveedor->codproveedor;
        $this->assertTrue($productoProveedor->save());
        $this->assertNotNull($productoProveedor->id);

        // eliminamos
        $this->assertTrue($productoProveedor->delete());
        $this->assertTrue($proveedor->getDefaultAddress()->delete());
        $this->assertTrue($proveedor->delete());
    }

    public function testCantCreateWithoutReference(): void
    {
        // creamos un proveedor
        $proveedor = $this->getRandomSupplier();
        $this->assertTrue($proveedor->save());

        $productoProveedor = new ProductoProveedor();
        $this->assertFalse($productoProveedor->save());

        // asignamos una referencia que no existe
        $productoProveedor->referencia = 'wrong-ref';
        $this->assertFalse($productoProveedor->save());

        // eliminamos
        $this->assertTrue($proveedor->getDefaultAddress()->delete());
        $this->assertTrue($proveedor->delete());
    }

    /**
     * Comprobamos que NO se puede crear un ProductoProveedor
     * cuando el Proveedor no existe
     */
    public function testItCanNotCreateWithoutSupplier(): void
    {
        $productoProveedor = new ProductoProveedor();
        $productoProveedor->referencia = 'test';
        $this->assertFalse($productoProveedor->save());

        // asignamos un proveedor que no existe
        $productoProveedor->codproveedor = 'wrong-cod';
        $this->assertFalse($productoProveedor->save());
    }

    /**
     * Comprobamos que al eliminar un proveedor
     * se eliminan también sus modelos ProductoProveedor
     */
    public function testItDeleteProductosProveedorOnCascade(): void
    {
        // Creamos un proveedor
        $proveedor = $this->getRandomSupplier();
        $this->assertTrue($proveedor->save());

        // Creamos dos productos del proveedor
        $productoProveedor1 = new ProductoProveedor();
        $productoProveedor1->referencia = 'test-1';
        $productoProveedor1->codproveedor = $proveedor->codproveedor;
        $this->assertTrue($productoProveedor1->save());

        $productoProveedor2 = new ProductoProveedor();
        $productoProveedor2->referencia = 'test-2';
        $productoProveedor2->codproveedor = $proveedor->codproveedor;
        $this->assertTrue($productoProveedor2->save());

        // eliminamos el proveedor
        $this->assertTrue($proveedor->getDefaultAddress()->delete());
        $this->assertTrue($proveedor->delete());

        // Comprobamos que los dos productos ya NO se encuentran en la base de datos
        $this->assertFalse($productoProveedor1->exists());
        $this->assertFalse($productoProveedor2->exists());
    }

    /**
     * Comprobamos que al crear una línea de Albarán se
     * crea un ProductoProveedor
     */
    public function testItCreateWhenCreatingAlbaran(): void
    {
        Tools::settingsSet('default', 'updatesupplierprices', true);

        [$subject, $product, $doc, $pvpUnitario, $dtopor, $dtopor2] = $this->getAlbaranConLineaProducto();
        $this->assertTrue($subject->exists());
        $this->assertTrue($product->exists());
        $this->assertTrue($doc->exists());
        $this->assertCount(1, $doc->getLines());

        $model = new ProductoProveedor();
        $productosProveedor = $model->all([
            new DataBaseWhere('referencia', $product->referencia),
            new DataBaseWhere('codproveedor', $subject->codproveedor),
        ]);

        $this->assertCount(1, $productosProveedor);
        $productoProveedor = $productosProveedor[0];

        $this->assertEquals($doc->coddivisa, $productoProveedor->coddivisa);
        $this->assertEquals($doc->codproveedor, $subject->codproveedor);
        $this->assertEquals(5.6, $productoProveedor->neto);
        $this->assertEquals(5.6, $productoProveedor->netoeuros);
        $this->assertEquals($pvpUnitario, $productoProveedor->precio);
        $this->assertEquals($dtopor, $productoProveedor->dtopor);
        $this->assertEquals($dtopor2, $productoProveedor->dtopor2);

        // eliminamos
        $this->assertTrue($doc->delete());
        $this->assertTrue($subject->getDefaultAddress()->delete());
        $this->assertTrue($subject->delete());
        $this->assertTrue($product->delete());
    }

    /**
     * Comprobamos que al crear una línea de Albarán
     * con un Producto previamente incluido en otro Albarán
     * mantiene el precio del ProductoProveedor creado en el Albarán previo
     * y no el precio del Producto.
     */
    public function testItCanAssignProductoProveedorPrice(): void
    {
        [$subject, $product, $doc, $pvpUnitario, $dtopor, $dtopor2] = $this->getAlbaranConLineaProducto();

        // Creamos otro albarán
        $doc2 = new AlbaranProveedor();
        $doc2->setSubject($subject);
        $this->assertTrue($doc2->save());

        // Añadimos el producto
        $line = $doc2->getNewProductLine($product->referencia);
        $this->assertTrue($line->save());

        // comprobamos que el precio del producto es el del albarán previo
        $this->assertEquals($pvpUnitario, $line->pvpunitario);
        $this->assertEquals($dtopor, $line->dtopor);
        $this->assertEquals($dtopor2, $line->dtopor2);
        $this->assertNotEquals($product->precio, $line->pvpunitario);

        // eliminamos
        $this->assertTrue($doc->delete());
        $this->assertTrue($doc2->delete());
        $this->assertTrue($subject->getDefaultAddress()->delete());
        $this->assertTrue($subject->delete());
        $this->assertTrue($product->delete());
    }

    /**
     * Comprobamos que al crear una línea de Albarán
     * con un Producto previamente incluido en otro Albarán
     * y creamos otro Albarán con distinta Divisa incluyendo el mismo Producto,
     * se crea un ProductoProveedor nuevo con la Divisa del Albarán
     */
    public function testItCanAssignProductoProveedorCurrency(): void
    {
        [$subject, $product, $doc] = $this->getAlbaranConLineaProducto();

        // Creamos otra divisa
        $divisa = new Divisa();
        $divisa->coddivisa = 'XXX';
        $this->assertTrue($divisa->save());

        // Creamos otro albarán
        $doc2 = new AlbaranProveedor();
        $doc2->setSubject($subject);
        $doc2->setCurrency('XXX');
        $this->assertTrue($doc2->save());

        // Añadimos el producto
        $line = $doc2->getNewProductLine($product->referencia);
        $line->pvpunitario = 1000;
        $this->assertTrue($line->save());

        // actualizamos los totales
        $lines = [$line];
        $this->assertTrue(Calculator::calculate($doc2, $lines, true));

        // procesamos la cola de trabajos
        while (true) {
            if (false === WorkQueue::run()) {
                break;
            }
        }

        // buscamos el producto del proveedor
        $productosProveedor = ProductoProveedor::all([
            new DataBaseWhere('referencia', $product->referencia),
            new DataBaseWhere('codproveedor', $subject->codproveedor),
        ]);

        // comprobamos que se han creado dos productos proveedor
        $this->assertCount(2, $productosProveedor);
        $this->assertEquals($productosProveedor[0]->referencia, $productosProveedor[1]->referencia);
        $this->assertEquals('EUR', $productosProveedor[0]->coddivisa);
        $this->assertEquals('XXX', $productosProveedor[1]->coddivisa);

        // eliminamos
        $this->assertTrue($doc->delete());
        $this->assertTrue($doc2->delete());
        $this->assertTrue($subject->getDefaultAddress()->delete());
        $this->assertTrue($subject->delete());
        $this->assertTrue($product->delete());
        $this->assertTrue($divisa->delete());
    }

    public function testPrimaryColumn(): void
    {
        $result = ProductoProveedor::primaryColumn();

        $this->assertEquals('id', $result);
    }

    public function testGetEUDiscount(): void
    {
        $productoProveedor = new ProductoProveedor();

        $result = $productoProveedor->getEUDiscount();

        $this->assertEquals(1, $result);

        $productoProveedor->dtopor = 10;

        $result = $productoProveedor->getEUDiscount();

        $this->assertEquals(0.9, $result);

        $productoProveedor->dtopor = 10;
        $productoProveedor->dtopor2 = 10;

        $result = $productoProveedor->getEUDiscount();

        $this->assertEquals(0.81, $result);
    }

    public function testTableName(): void
    {
        $result = ProductoProveedor::tableName();

        $this->assertEquals('productosprov', $result);
    }

    public function testUrl(): void
    {
        $producto = $this->getRandomProduct();
        $this->assertTrue($producto->save());

        $productoProveedor = new ProductoProveedor();

        $result = $productoProveedor->url();
        $this->assertEquals('ListProducto', $result);

        $productoProveedor->idproducto = $producto->idproducto;
        $productoProveedor->referencia = $producto->referencia;

        $result = $productoProveedor->url();
        $this->assertEquals('EditProducto?code=' . $producto->idproducto, $result);

        $this->assertTrue($producto->delete());
    }

    public function testClear(): void
    {
        $productoProveedor = new ProductoProveedor();

        $productoProveedor->clear();

        $this->assertNotNull($productoProveedor->actualizado);
        $this->assertEquals(Tools::settings('default', 'coddivisa'), $productoProveedor->coddivisa);
        $this->assertEquals(0, $productoProveedor->dtopor);
        $this->assertEquals(0, $productoProveedor->dtopor2);
        $this->assertEquals(0, $productoProveedor->neto);
        $this->assertEquals(0, $productoProveedor->netoeuros);
        $this->assertEquals(0, $productoProveedor->precio);
        $this->assertEquals(0, $productoProveedor->stock);
    }

    public function testGetVariant(): void
    {
        $producto = $this->getRandomProduct();
        $this->assertTrue($producto->save());

        $productoProveedor = new ProductoProveedor();
        $productoProveedor->idproducto = $producto->idproducto;
        $productoProveedor->referencia = $producto->referencia;

        $result = $productoProveedor->getVariant();

        $this->assertTrue($result instanceof Variante);
        $this->assertEquals($productoProveedor->referencia, $result->referencia);

        $this->assertTrue($producto->delete());
    }

    public function testGetSupplier(): void
    {
        $proveedor = $this->getRandomSupplier();
        $this->assertTrue($proveedor->save());

        $productoProveedor = new ProductoProveedor();
        $productoProveedor->codproveedor = $proveedor->codproveedor;

        $result = $productoProveedor->getSupplier();

        $this->assertTrue($result instanceof Proveedor);
        $this->assertEquals($productoProveedor->codproveedor, $result->codproveedor);

        $this->assertTrue($proveedor->getDefaultAddress()->delete());
        $this->assertTrue($proveedor->delete());
    }

    public function testInstall(): void
    {
        $productoProveedor = new ProductoProveedor();

        $result = $productoProveedor->install();

        $this->assertEquals('', $result);
    }

    public function testTest(): void
    {
        $proveedor = $this->getRandomSupplier();
        $this->assertTrue($proveedor->save());

        $productoProveedor = new ProductoProveedor();

        // Comprobamos con modelo vacío, debe dar error de referencia no puede ser nula.
        $result = $productoProveedor->test();

        $logs = MiniLog::read();
        $expected = Tools::lang()->trans(
            'field-can-not-be-null',
            [
                '%fieldName%' => 'referencia',
                '%tableName%' => ProductoProveedor::tableName(),
            ]
        );
        $this->assertEquals($expected, end($logs)['message']);
        $this->assertFalse($result);

        // Agregamos la referencia al modelo
        $productoProveedor->referencia = 'test';

        $result = $productoProveedor->test();

        // Comprobamos con modelo vacío, debe dar error de codproveedor no puede ser nulo.
        $logs = MiniLog::read();
        $expected = Tools::lang()->trans(
            'field-can-not-be-null',
            [
                '%fieldName%' => 'codproveedor',
                '%tableName%' => ProductoProveedor::tableName(),
            ]
        );
        $this->assertEquals($expected, end($logs)['message']);
        $this->assertFalse($result);

        // Agregamos el codproveedor al modelo y borramos los logs para comprobar que ahora no existen errores.
        $productoProveedor->codproveedor = $proveedor->codproveedor;
        MiniLog::clear();

        $this->assertTrue($productoProveedor->save());
        $result = $productoProveedor->test();

        // Comprobamos que las validaciones son correctas.
        $this->assertTrue($result);

        // Comprobamos los valores calculados
        $productoProveedor->precio = 100;
        $productoProveedor->dtopor = 10;
        $productoProveedor->dtopor2 = 10;
        $productoProveedor->coddivisa = 'EUR';

        $result = $productoProveedor->test();

        $this->assertEquals($productoProveedor->refproveedor, $productoProveedor->referencia);
        $this->assertEquals($productoProveedor->idproducto, $productoProveedor->getVariant()->idproducto);
        $this->assertEquals(
            $productoProveedor->neto,
            round(
                $productoProveedor->precio * $productoProveedor->getEUDiscount(),
                Producto::ROUND_DECIMALS
            )
        );
        $this->assertEquals(
            $productoProveedor->netoeuros,
            Divisas::get($productoProveedor->coddivisa)->tasaconvcompra * $productoProveedor->neto
        );
        $this->assertTrue($result);

        $this->assertTrue($productoProveedor->delete());
        $this->assertTrue($proveedor->getDefaultAddress()->delete());
        $this->assertTrue($proveedor->delete());
    }

    /**
     * Devuelve un albarán con una línea de producto.
     * También se devuelven los modelos y variables que han sido
     * necesarias para crear el albarán.
     *
     * @return array
     */
    private function getAlbaranConLineaProducto(): array
    {
        // creamos un proveedor
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save());

        // creamos un producto
        $product = $this->getRandomProduct();
        $this->assertTrue($product->save());

        // creamos un albarán
        $doc = new AlbaranProveedor();
        $doc->setSubject($subject);
        $doc->observaciones = 'test producto proveedor';
        $this->assertTrue($doc->save());

        // añadimos el producto
        $pvpUnitario = 10;
        $dtopor = 20;
        $dtopor2 = 30;
        $line = $doc->getNewProductLine($product->referencia);
        $line->pvpunitario = $pvpUnitario;
        $line->dtopor = $dtopor;
        $line->dtopor2 = $dtopor2;
        $this->assertTrue($line->save());

        // actualizamos los totales
        $lines = [$line];
        $this->assertTrue(Calculator::calculate($doc, $lines, true));

        // procesamos la cola de trabajos
        while (true) {
            if (false === WorkQueue::run()) {
                break;
            }
        }

        return [
            $subject,
            $product,
            $doc,
            $pvpUnitario,
            $dtopor,
            $dtopor2,
        ];
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
