<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021  Carlos Garcia Gomez     <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Almacen;
use FacturaScripts\Core\Model\Producto;
use FacturaScripts\Core\Model\Stock;
use FacturaScripts\Core\Model\Variante;
use FacturaScripts\Test\Core\LogErrorsTrait;
use PHPUnit\Framework\TestCase;


final class StockTest extends TestCase
{
    use LogErrorsTrait;

    public function testCreate()
    {
        $product = new Producto();
        $product->referencia = 'Test';
        $product->descripcion = 'Test Product';
        $this->assertTrue($product->save(), 'product-cant-save');

        $stock = new Stock();
        $stock->codalmacen = AppSettings::get('default', 'codalmacen');
        $stock->idproducto = $product->idproducto;
        $stock->referencia = $product->referencia;

        // control sobre la creación
        $this->assertTrue($stock->save(), 'stock-cant-save');
        $this->assertNotNull($stock->primaryColumnValue(), 'stock-not-stored');
        $this->assertTrue($stock->exists(), 'stock-cant-persist');

        // control sobre el borrado
        $this->assertTrue($product->delete(), 'product-cant-delete');
        $this->assertFalse($stock->loadFromCode($stock->idstock), 'stock-not-removed-when-delete-product');
    }

    public function testCreateWithoutProduct()
    {
        $stock = new Stock();
        $stock->codalmacen = AppSettings::get('default', 'codalmacen');
        $this->assertFalse($stock->save(), 'stock-cant-save-without-product');
    }

    public function testProductStock()
    {
        $product = new Producto();
        $product->referencia = 'Test';
        $product->descripcion = 'Test Product';
        $this->assertTrue($product->save(), 'product-cant-save');

        $stock = new Stock();
        $stock->codalmacen = AppSettings::get('default', 'codalmacen');
        $stock->idproducto = $product->idproducto;
        $stock->referencia = $product->referencia;
        $stock->cantidad = 10;

        // control sobre la creación y stock
        $this->assertTrue($stock->save(), 'stock-cant-save');

        // refrescamos producto y su stock
        $product->loadFromCode($product->idproducto);
        $this->assertTrue($product->stockfis !== 10, 'stock-not-update');

        $this->assertTrue($stock->delete(), 'stock-cant-delete');
        $product->loadFromCode($product->idproducto);
        $this->assertTrue($product->stockfis !== 0, 'stock-not-update');

        $this->assertTrue($product->delete(), 'product-cant-delete');
    }

    public function testProductAvailableStock()
    {
        $product = new Producto();
        $product->referencia = 'Test';
        $product->descripcion = 'Test Product';
        $this->assertTrue($product->save(), 'product-cant-save');

        $stock = new Stock();
        $stock->codalmacen = AppSettings::get('default', 'codalmacen');
        $stock->idproducto = $product->idproducto;
        $stock->referencia = $product->referencia;
        $stock->cantidad = 10;
        $stock->disponible = 10;
        $stock->reservada = 5;

        // control sobre available stock
        $this->assertTrue($stock->save(), 'stock-cant-save');
        $stock->loadFromCode($stock->idstock);
        $this->assertTrue($stock->disponible < $stock->cantidad, 'stock-available-wrong');

        $this->assertTrue($stock->delete(), 'stock-cant-delete');
        $this->assertTrue($product->delete(), 'product-cant-delete');
    }

    public function testVariantStock()
    {
        $product = new Producto();
        $product->referencia = 'Test';
        $product->descripcion = 'Test Product';
        $this->assertTrue($product->save(), 'product-cant-save');

        $warehouse1 = new Almacen();
        $warehouse1->nombre = 'Warehouse test B';
        $this->assertTrue($warehouse1->save(), 'warehouse-cant-save');

        $warehouse2 = new Almacen();
        $warehouse2->nombre = 'Warehouse test B';
        $this->assertTrue($warehouse2->save(), 'warehouse-cant-save');

        $stock1 = new Stock();
        $stock1->codalmacen = $warehouse1->codalmacen;
        $stock1->idproducto = $product->idproducto;
        $stock1->referencia = $product->referencia;
        $stock1->cantidad = 10;
        $this->assertTrue($stock1->save(), 'stock-cant-save');

        $stock2 = new Stock();
        $stock2->codalmacen = $warehouse2->codalmacen;
        $stock2->idproducto = $product->idproducto;
        $stock2->referencia = $product->referencia;
        $stock2->cantidad = 5;
        $this->assertTrue($stock2->save(), 'stock-cant-save');

        // control stock variante
        $variant = new Variante();
        $this->assertTrue($variant->loadFromCode('', [ new DataBaseWhere('referencia', $product->referencia) ]), 'variant-not-found');
        $this->assertTrue($variant->stockfis == ($stock1->cantidad + $stock2->cantidad), 'stock-variant-wrong');

        $this->assertTrue($stock1->delete(), 'stock-cant-delete');
        $this->assertTrue($stock2->delete(), 'stock-cant-delete');
        $this->assertTrue($product->delete(), 'product-cant-delete');
        $this->assertTrue($warehouse1->delete(), 'warehouse-cant-delete');
        $this->assertTrue($warehouse2->delete(), 'warehouse-cant-delete');
    }

    protected function tearDown()
    {
        $this->logErrors();
    }
}
