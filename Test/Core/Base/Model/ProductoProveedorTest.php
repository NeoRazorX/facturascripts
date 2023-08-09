<?php

declare(strict_types=1);

namespace FacturaScripts\Test\Core\Base\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\DataSrc\Divisas;
use FacturaScripts\Core\Model\AlbaranProveedor;
use FacturaScripts\Core\Model\Divisa;
use FacturaScripts\Core\Model\Producto;
use FacturaScripts\Core\Model\ProductoProveedor;
use FacturaScripts\Core\Model\Proveedor;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Model\Variante;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class ProductoProveedorTest
 * @package FacturaScripts\Test\Core\Base\Model
 */
class ProductoProveedorTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;
    use DefaultSettingsTrait;

    protected function setUp(): void
    {
        parent::setUp();
        new User();
        self::setDefaultSettings();
    }

    /**
     * Comprobamos que se puede crear un ProductoProveedor
     */
    public function testItCanCreateProductoProveedor()
    {
        $proveedor = static::getRandomSupplier();
        static::assertTrue($proveedor->save());

        $productoProveedor = new ProductoProveedor();

        $productoProveedor->referencia = 'test';
        $productoProveedor->codproveedor = $proveedor->codproveedor;

        static::assertTrue($productoProveedor->save());
        static::assertNotNull($productoProveedor->id);

        static::assertTrue($proveedor->delete());
        static::assertTrue($productoProveedor->delete());
    }

    /**
     * Comprobamos que NO se puede crear un ProductoProveedor
     * cuando el Proveedor no existe
     */
    public function testItCanNotCreateProductoProveedorWhitoutSupplier()
    {
        $productoProveedor = new ProductoProveedor();

        $productoProveedor->referencia = 'test';
        $productoProveedor->codproveedor = 'wrong-cod';

        static::assertFalse($productoProveedor->save());
        static::assertNull($productoProveedor->id);

        $logs = MiniLog::read();
        static::assertEquals('database', end($logs)['channel']);
        static::assertEquals('error', end($logs)['level']);
    }

    /**
     * Comprobamos que al eliminar un proveedor
     * se eliminan tambien sus modelos ProductoProveedor
     */
    public function testItDeleteProductosProveedorOnCascade()
    {
        // Creamos un proveedor con dos productos
        $proveedor = static::getRandomSupplier();
        static::assertTrue($proveedor->save());

        $productoProveedor1 = new ProductoProveedor();
        $productoProveedor1->referencia = 'test-1';
        $productoProveedor1->codproveedor = $proveedor->codproveedor;
        static::assertTrue($productoProveedor1->save());

        $productoProveedor2 = new ProductoProveedor();
        $productoProveedor2->referencia = 'test-2';
        $productoProveedor2->codproveedor = $proveedor->codproveedor;
        static::assertTrue($productoProveedor2->save());

        // Comprobamos que los dos productos se encuentran en la BBDD
        $productoProveedor = new ProductoProveedor();
        $productoProveedor->loadFromCode($productoProveedor1->id);
        static::assertNotNull($productoProveedor->id);
        $productoProveedor->loadFromCode($productoProveedor2->id);
        static::assertNotNull($productoProveedor->id);

        // Borramos el proveedor
        static::assertTrue($proveedor->delete());

        // Comprobamos que los dos productos ya NO se encuentran en la BBDD
        $productoProveedor->loadFromCode($productoProveedor1->id);
        static::assertNull($productoProveedor->id);
        $productoProveedor->loadFromCode($productoProveedor2->id);
        static::assertNull($productoProveedor->id);
    }

    /**
     * Comprobamos que al crear una linea de Albarán se
     * crea un ProductoProveedor
     */
    public function testItCanCreateProductoProveedorWhenCreatingAlbaran()
    {
        [$subject, $product, $doc, $pvpunitario, $dtopor, $dtopor2] = $this->getAlbaranConLineaProducto();

        $productoProveedor = new ProductoProveedor();
        $productoProveedor = $productoProveedor->all(
            [
                new DataBaseWhere('referencia', $product->referencia),
                new DataBaseWhere('codproveedor', $subject->codproveedor),
            ]
        )[0];

        static::assertEquals($doc->coddivisa, $productoProveedor->coddivisa);
        static::assertEquals($doc->codproveedor, $subject->codproveedor);
        static::assertEquals(5.6, $productoProveedor->neto);
        static::assertEquals(5.6, $productoProveedor->netoeuros);
        static::assertEquals($pvpunitario, $productoProveedor->precio);
        static::assertEquals($dtopor, $productoProveedor->dtopor);
        static::assertEquals($dtopor2, $productoProveedor->dtopor2);

        // eliminamos
        static::assertTrue($doc->delete());
        static::assertTrue($subject->getDefaultAddress()->delete());
        static::assertTrue($subject->delete());
        static::assertTrue($product->delete());
    }

    /**
     * Comprobamos que al crear una linea de Albarán
     * con un Producto previamente incluido en otro Albarán
     * mantiene el precio del ProductoProveedor creado en el Albarán previo
     * y no el precio del Producto.
     */
    public function testItCanAssignProductoProveedorPrice()
    {
        [$subject, $product, $doc, $pvpunitario, $dtopor, $dtopor2] = $this->getAlbaranConLineaProducto();

        // Creamos otro albarán
        $doc2 = new AlbaranProveedor();
        $doc2->setSubject($subject);
        static::assertTrue($doc2->save());

        $line = $doc2->getNewProductLine($product->referencia);
        static::assertTrue($line->save());

        static::assertEquals($pvpunitario, $line->pvpunitario);
        static::assertEquals($dtopor, $line->dtopor);
        static::assertEquals($dtopor2, $line->dtopor2);
        static::assertNotEquals($product->precio, $line->pvpunitario);

        // eliminamos
        static::assertTrue($doc->delete());
        static::assertTrue($doc2->delete());
        static::assertTrue($subject->getDefaultAddress()->delete());
        static::assertTrue($subject->delete());
        static::assertTrue($product->delete());
    }

    /**
     * Comprobamos que al crear una linea de Albarán
     * con un Producto previamente incluido en otro Albarán
     * y creamos otro Albarán con distinta Divisa incluyendo el mismo Producto,
     * se crea un ProductoProveedor nuevo con la Divisa del Albarán
     */
    public function testItCanAssignProductoProveedorCurrency()
    {
        [$subject, $product, $doc] = $this->getAlbaranConLineaProducto();

        // Creamos otra divisa
        $divisa = new Divisa();
        $divisa->coddivisa = 'USD';
        static::assertTrue($divisa->save());

        // Creamos otro albarán
        $doc2 = new AlbaranProveedor();
        $doc2->setSubject($subject);
        $doc2->setCurrency('USD');
        static::assertTrue($doc2->save());

        $line = $doc2->getNewProductLine($product->referencia);
        $line->pvpunitario = 1000;
        static::assertTrue($line->save());

        $productosProveedor = new ProductoProveedor();
        $productosProveedor = $productosProveedor->all(
            [
                new DataBaseWhere('referencia', $product->referencia),
                new DataBaseWhere('codproveedor', $subject->codproveedor),
            ]
        );

        static::assertCount(2, $productosProveedor);
        static::assertEquals($productosProveedor[0]->referencia, $productosProveedor[1]->referencia);
        static::assertEquals('EUR', $productosProveedor[0]->coddivisa);
        static::assertEquals('USD', $productosProveedor[1]->coddivisa);

        // eliminamos
        static::assertTrue($doc->delete());
        static::assertTrue($doc2->delete());
        static::assertTrue($subject->getDefaultAddress()->delete());
        static::assertTrue($subject->delete());
        static::assertTrue($product->delete());
        static::assertTrue($divisa->delete());
    }

    public function testPrimaryColumn()
    {
        $result = ProductoProveedor::primaryColumn();

        static::assertEquals('id', $result);
    }

    public function testGetEUDiscount()
    {
        $productoProveedor = new ProductoProveedor();

        $result = $productoProveedor->getEUDiscount();

        static::assertEquals(1, $result);

        $productoProveedor->dtopor = 10;

        $result = $productoProveedor->getEUDiscount();

        static::assertEquals(0.9, $result);

        $productoProveedor->dtopor = 10;
        $productoProveedor->dtopor2 = 10;

        $result = $productoProveedor->getEUDiscount();

        static::assertEquals(0.81, $result);
    }

    public function testTableName()
    {
        $result = ProductoProveedor::tableName();

        static::assertEquals('productosprov', $result);
    }

    public function testUrl()
    {
        $producto = static::getRandomProduct();
        static::assertTrue($producto->save());

        $productoProveedor = new ProductoProveedor();

        $result = $productoProveedor->url();
        static::assertEquals('ListProducto', $result);

        $productoProveedor->idproducto = $producto->idproducto;
        $productoProveedor->referencia = $producto->referencia;

        $result = $productoProveedor->url();
        static::assertEquals('EditProducto?code=' . $producto->idproducto, $result);

        static::assertTrue($producto->delete());
    }

    public function testClear()
    {
        $productoProveedor = new ProductoProveedor();

        $productoProveedor->clear();

        static::assertNotNull($productoProveedor->actualizado);
        static::assertEquals(ToolBox::appSettings()::get('default', 'coddivisa'), $productoProveedor->coddivisa);
        static::assertEquals(0, $productoProveedor->dtopor);
        static::assertEquals(0, $productoProveedor->dtopor2);
        static::assertEquals(0, $productoProveedor->neto);
        static::assertEquals(0, $productoProveedor->netoeuros);
        static::assertEquals(0, $productoProveedor->precio);
        static::assertEquals(0, $productoProveedor->stock);
    }

    public function testGetVariant()
    {
        $producto = static::getRandomProduct();
        static::assertTrue($producto->save());

        $productoProveedor = new ProductoProveedor();
        $productoProveedor->idproducto = $producto->idproducto;
        $productoProveedor->referencia = $producto->referencia;

        $result = $productoProveedor->getVariant();

        static::assertTrue($result instanceof Variante);
        static::assertEquals($productoProveedor->referencia, $result->referencia);

        static::assertTrue($producto->delete());
    }

    public function testGetSupplier()
    {
        $proveedor = static::getRandomSupplier();
        static::assertTrue($proveedor->save());

        $productoProveedor = new ProductoProveedor();
        $productoProveedor->codproveedor = $proveedor->codproveedor;

        $result = $productoProveedor->getSupplier();

        static::assertTrue($result instanceof Proveedor);
        static::assertEquals($productoProveedor->codproveedor, $result->codproveedor);

        static::assertTrue($proveedor->delete());
    }

    public function testInstall()
    {
        $productoProveedor = new ProductoProveedor();

        $result = $productoProveedor->install();

        static::assertEquals('', $result);
    }

    public function testTest()
    {
        $proveedor = static::getRandomSupplier();
        static::assertTrue($proveedor->save());

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
        static::assertEquals($expected, end($logs)['message']);
        static::assertFalse($result);

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
        static::assertEquals($expected, end($logs)['message']);
        static::assertFalse($result);

        // Agregamos el codproveedor al modelo y borramos los logs para comprobar que ahora no existen errores.
        $productoProveedor->codproveedor = $proveedor->codproveedor;
        MiniLog::clear();

        static::assertTrue($productoProveedor->save());
        $result = $productoProveedor->test();

        // Comprobamos que las validaciones son correctas.
        static::assertTrue($result);

        // Comprobamos los valores calculados
        $productoProveedor->precio = 100;
        $productoProveedor->dtopor = 10;
        $productoProveedor->dtopor2 = 10;
        $productoProveedor->coddivisa = 'EUR';

        $result = $productoProveedor->test();

        static::assertEquals($productoProveedor->refproveedor, $productoProveedor->referencia);
        static::assertEquals($productoProveedor->idproducto, $productoProveedor->getVariant()->idproducto);
        static::assertEquals(
            $productoProveedor->neto,
            round(
                $productoProveedor->precio * $productoProveedor->getEUDiscount(),
                Producto::ROUND_DECIMALS
            )
        );
        static::assertEquals(
            $productoProveedor->netoeuros,
            Divisas::get($productoProveedor->coddivisa)->tasaconvcompra * $productoProveedor->neto
        );
        static::assertTrue($result);

        static::assertTrue($productoProveedor->delete());
        static::assertTrue($proveedor->delete());
    }

    /**
     * Devuelve un albarán con una linea de producto.
     * Tambien se devuelven los modelos y variables que han sido
     * necesarias para crear el albarán.
     *
     * @return array
     */
    private function getAlbaranConLineaProducto()
    {
        // creamos un proveedor
        $subject = static::getRandomSupplier();
        static::assertTrue($subject->save());

        // creamos un producto
        $product = static::getRandomProduct();
        static::assertTrue($product->save());

        // creamos un albarán
        $doc = new AlbaranProveedor();
        $doc->setSubject($subject);
        static::assertTrue($doc->save());

        // añadimos el producto
        $pvpunitario = 10;
        $dtopor = 20;
        $dtopor2 = 30;
        $line = $doc->getNewProductLine($product->referencia);
        $line->pvpunitario = $pvpunitario;
        $line->dtopor = $dtopor;
        $line->dtopor2 = $dtopor2;
        static::assertTrue($line->save());

        return [
            $subject,
            $product,
            $doc,
            $pvpunitario,
            $dtopor,
            $dtopor2,
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        static::logErrors();
    }
}
