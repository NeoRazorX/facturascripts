<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Stock;
use FacturaScripts\Core\Model\Variante;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class StockTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    public function testCreate()
    {
        // creamos un producto
        $product = $this->getRandomProduct();
        $this->assertTrue($product->save(), 'product-can-not-save');

        // añadimos stock al producto
        $stock = new Stock();
        $stock->idproducto = $product->idproducto;
        $stock->referencia = $product->referencia;
        $this->assertTrue($stock->save(), 'stock-can-not-save');
        $this->assertNotNull($stock->primaryColumnValue(), 'stock-not-stored');
        $this->assertTrue($stock->exists(), 'stock-can-not-persist');

        // borrar el producto borra el stock
        $this->assertTrue($product->delete(), 'product-cant-delete');
        $this->assertFalse($stock->exists(), 'stock-not-removed-when-delete-product');
    }

    public function testCantCreateWithoutProduct()
    {
        $stock = new Stock();
        $stock->cantidad = 10;
        $this->assertFalse($stock->save(), 'stock-can-not-create-without-product');
    }

    public function testStockChangesProduct()
    {
        // creamos un producto
        $product = $this->getRandomProduct();
        $this->assertTrue($product->save(), 'product-cant-save');
        $this->assertEquals(0, $product->stockfis, 'new-product-cant-have-stock');

        // añadimos stock
        $stock = new Stock();
        $stock->idproducto = $product->idproducto;
        $stock->referencia = $product->referencia;
        $stock->cantidad = 10;
        $this->assertTrue($stock->save(), 'stock-can-not-save');
        $this->assertEquals(10, $stock->cantidad, 'stock-quantity-changed');

        // refrescamos producto
        $product->loadFromCode($product->idproducto);
        $this->assertEquals(10, $product->stockfis, 'product-stock-not-update');

        // cambiamos el stock
        $stock->cantidad = 7;
        $this->assertTrue($stock->save(), 'stock-can-not-update');
        $this->assertEquals(7, $stock->cantidad, 'stock-quantity-not-changed');

        // refrescamos producto
        $product->loadFromCode($product->idproducto);
        $this->assertEquals(7, $product->stockfis, 'product-stock-not-update');

        // borramos el stock
        $this->assertTrue($stock->delete(), 'stock-cant-delete');

        // refrescamos producto
        $product->loadFromCode($product->idproducto);
        $this->assertEquals(0, $product->stockfis, 'stock-not-update');

        // borramos el producto
        $this->assertTrue($product->delete(), 'product-cant-delete');
    }

    public function testMultiWarehouse()
    {
        // creamos un producto
        $product = $this->getRandomProduct();
        $this->assertTrue($product->save(), 'product-cant-save');

        // creamos 2 almacenes
        $warehouse1 = $this->getRandomWarehouse();
        $this->assertTrue($warehouse1->save(), 'warehouse-cant-save');
        $warehouse2 = $this->getRandomWarehouse();
        $this->assertTrue($warehouse2->save(), 'warehouse-cant-save');

        // añadimos stock al primer almacén
        $stock1 = new Stock();
        $stock1->codalmacen = $warehouse1->codalmacen;
        $stock1->idproducto = $product->idproducto;
        $stock1->referencia = $product->referencia;
        $stock1->cantidad = 10;
        $this->assertTrue($stock1->save(), 'stock-cant-save');

        // añadimos stock al segundo almacén
        $stock2 = new Stock();
        $stock2->codalmacen = $warehouse2->codalmacen;
        $stock2->idproducto = $product->idproducto;
        $stock2->referencia = $product->referencia;
        $stock2->cantidad = 5;
        $this->assertTrue($stock2->save(), 'stock-cant-save');

        // comprobamos que el stock del producto es el stock de todos los almacenes
        $product->loadFromCode($product->idproducto);
        $this->assertEquals(15, $product->stockfis, 'producto-stock-not-the-sum');

        // borramos
        $this->assertTrue($product->delete(), 'product-cant-delete');
        $this->assertTrue($warehouse1->delete(), 'warehouse-cant-delete');
        $this->assertTrue($warehouse2->delete(), 'warehouse-cant-delete');
    }

    public function testMultiWarehouseMultiVariant()
    {
        // creamos un producto
        $product = $this->getRandomProduct();
        $this->assertTrue($product->save(), 'product-cant-save');

        // creamos otra variante
        $variant = new Variante();
        $variant->idproducto = $product->idproducto;
        $variant->referencia = '97897987987';
        $this->assertTrue($variant->save(), 'can-not-create-new-variant');

        // creamos 2 almacenes
        $warehouse1 = $this->getRandomWarehouse();
        $this->assertTrue($warehouse1->save(), 'warehouse-cant-save');
        $warehouse2 = $this->getRandomWarehouse();
        $this->assertTrue($warehouse2->save(), 'warehouse-cant-save');

        // añadimos stock al primer almacén
        foreach ($product->getVariants() as $var) {
            $stock1 = new Stock();
            $stock1->codalmacen = $warehouse1->codalmacen;
            $stock1->idproducto = $var->idproducto;
            $stock1->referencia = $var->referencia;
            $stock1->cantidad = 9;
            $this->assertTrue($stock1->save(), 'stock-cant-save');
        }

        // añadimos stock al segundo almacén
        foreach ($product->getVariants() as $var) {
            $stock2 = new Stock();
            $stock2->codalmacen = $warehouse2->codalmacen;
            $stock2->idproducto = $var->idproducto;
            $stock2->referencia = $var->referencia;
            $stock2->cantidad = 3;
            $this->assertTrue($stock2->save(), 'stock-cant-save');
        }

        // comprobamos que el stock del producto es el stock de todas las variantes en los almacenes
        $product->loadFromCode($product->idproducto);
        $this->assertEquals(24, $product->stockfis, 'producto-stock-not-the-sum');

        // borramos
        $this->assertTrue($product->delete(), 'product-cant-delete');
        $this->assertTrue($warehouse1->delete(), 'warehouse-cant-delete');
        $this->assertTrue($warehouse2->delete(), 'warehouse-cant-delete');
    }

    public function testAvailableStock()
    {
        // creamos el producto
        $product = $this->getRandomProduct();
        $this->assertTrue($product->save(), 'product-cant-save');

        // añadimos stock
        $stock = new Stock();
        $stock->idproducto = $product->idproducto;
        $stock->referencia = $product->referencia;
        $stock->cantidad = 10;
        $stock->reservada = 5;
        $this->assertTrue($stock->save(), 'stock-cant-save');

        // comprobamos
        $this->assertTrue($stock->disponible < $stock->cantidad, 'stock-available-wrong');
        $this->assertEquals(5, $stock->disponible, 'stock-disponible-bad');

        // eliminamos
        $this->assertTrue($stock->delete(), 'stock-cant-delete');
        $this->assertTrue($product->delete(), 'product-cant-delete');
    }

    public function testNegativeQuantity()
    {
        // creamos el producto
        $product = $this->getRandomProduct();
        $this->assertTrue($product->save(), 'product-cant-save');

        // añadimos stock
        $stock = new Stock();
        $stock->idproducto = $product->idproducto;
        $stock->referencia = $product->referencia;
        $stock->cantidad = -10;
        $this->assertTrue($stock->save(), 'stock-cant-save');

        // comprobamos
        $this->assertEquals(-10, $stock->cantidad, 'stock-quantity-changed');

        // eliminamos
        $this->assertTrue($stock->delete(), 'stock-cant-delete');
        $this->assertTrue($product->delete(), 'product-cant-delete');
    }

    public function testNegativeAvailable()
    {
        // creamos el producto
        $product = $this->getRandomProduct();
        $this->assertTrue($product->save(), 'product-cant-save');

        // añadimos stock
        $stock = new Stock();
        $stock->idproducto = $product->idproducto;
        $stock->referencia = $product->referencia;
        $stock->cantidad = 2;
        $stock->reservada = 10;
        $this->assertTrue($stock->save(), 'stock-cant-save');

        // comprobamos
        $this->assertEquals(0, $stock->disponible, 'stock-disponible-no-puede-ser-negativo');

        // eliminamos
        $this->assertTrue($stock->delete(), 'stock-cant-delete');
        $this->assertTrue($product->delete(), 'product-cant-delete');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
