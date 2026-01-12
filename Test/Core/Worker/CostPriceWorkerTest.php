<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2025 Pablo Aceituno <civernet@gmail.com>
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

namespace FacturaScripts\Test\Core\Worker;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Model\AlbaranProveedor;
use FacturaScripts\Core\Model\ProductoProveedor;
use FacturaScripts\Core\Model\Variante;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\WorkQueue;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class CostPriceWorkerTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;
    use DefaultSettingsTrait;

    protected function setUp(): void
    {
        self::setDefaultSettings();
    }

    public function testCostPriceUpdatesOnProductoProveedorInsert(): void
    {
        // configuramos la política de precio de coste a último precio
        Tools::settingsSet('default', 'costpricepolicy', 'last-price');
        Tools::settingsSet('default', 'updatesupplierprices', true);

        // creamos un proveedor
        $proveedor = $this->getRandomSupplier();
        $this->assertTrue($proveedor->save());

        // creamos un producto
        $producto = $this->getRandomProduct();
        $this->assertTrue($producto->save());

        // obtenemos la variante
        $variante = new Variante();
        $where = [new DataBaseWhere('referencia', $producto->referencia)];
        $this->assertTrue($variante->loadWhere($where));

        // guardamos el coste inicial
        $costeInicial = $variante->coste;

        // creamos un producto del proveedor con un precio diferente
        $productoProveedor = new ProductoProveedor();
        $productoProveedor->codproveedor = $proveedor->codproveedor;
        $productoProveedor->referencia = $producto->referencia;
        $productoProveedor->precio = 50.0;
        $this->assertTrue($productoProveedor->save());

        // procesamos la cola de trabajos
        $this->processWorkQueue();

        // recargamos la variante
        $this->assertTrue($variante->loadWhere($where));

        // comprobamos que el coste se ha actualizado
        $this->assertNotEquals($costeInicial, $variante->coste);
        $this->assertEquals(50.0, $variante->coste);

        // eliminamos
        $this->assertTrue($productoProveedor->delete());
        $this->assertTrue($producto->delete());
        $this->assertTrue($proveedor->getDefaultAddress()->delete());
        $this->assertTrue($proveedor->delete());
    }

    public function testCostPriceUpdatesOnProductoProveedorUpdate(): void
    {
        // configuramos la política de precio de coste a último precio
        Tools::settingsSet('default', 'costpricepolicy', 'last-price');
        Tools::settingsSet('default', 'updatesupplierprices', true);

        // creamos un proveedor
        $proveedor = $this->getRandomSupplier();
        $this->assertTrue($proveedor->save());

        // creamos un producto
        $producto = $this->getRandomProduct();
        $this->assertTrue($producto->save());

        // obtenemos la variante
        $variante = new Variante();
        $where = [new DataBaseWhere('referencia', $producto->referencia)];
        $this->assertTrue($variante->loadWhere($where));

        // creamos un producto del proveedor
        $productoProveedor = new ProductoProveedor();
        $productoProveedor->codproveedor = $proveedor->codproveedor;
        $productoProveedor->referencia = $producto->referencia;
        $productoProveedor->precio = 50.0;
        $this->assertTrue($productoProveedor->save());

        // procesamos la cola de trabajos
        $this->processWorkQueue();

        // actualizamos el precio
        $productoProveedor->precio = 75.0;
        $this->assertTrue($productoProveedor->save());

        // procesamos la cola de trabajos
        $this->processWorkQueue();

        // recargamos la variante
        $this->assertTrue($variante->loadWhere($where));

        // comprobamos que el coste se ha actualizado
        $this->assertEquals(75.0, $variante->coste);

        // eliminamos
        $this->assertTrue($productoProveedor->delete());
        $this->assertTrue($producto->delete());
        $this->assertTrue($proveedor->getDefaultAddress()->delete());
        $this->assertTrue($proveedor->delete());
    }

    public function testCostPriceUpdatesOnProductoProveedorDelete(): void
    {
        // configuramos la política de precio de coste a último precio
        Tools::settingsSet('default', 'costpricepolicy', 'last-price');
        Tools::settingsSet('default', 'updatesupplierprices', true);

        // creamos dos proveedores
        $proveedor1 = $this->getRandomSupplier();
        $this->assertTrue($proveedor1->save());

        $proveedor2 = $this->getRandomSupplier();
        $this->assertTrue($proveedor2->save());

        // creamos un producto
        $producto = $this->getRandomProduct();
        $this->assertTrue($producto->save());

        // obtenemos la variante
        $variante = new Variante();
        $where = [new DataBaseWhere('referencia', $producto->referencia)];
        $this->assertTrue($variante->loadWhere($where));

        // creamos dos productos del proveedor (uno por cada proveedor)
        $productoProveedor1 = new ProductoProveedor();
        $productoProveedor1->codproveedor = $proveedor1->codproveedor;
        $productoProveedor1->referencia = $producto->referencia;
        $productoProveedor1->precio = 50.0;
        $this->assertTrue($productoProveedor1->save());

        // esperamos 1 segundo para que el segundo tenga fecha posterior
        sleep(1);

        $productoProveedor2 = new ProductoProveedor();
        $productoProveedor2->codproveedor = $proveedor2->codproveedor;
        $productoProveedor2->referencia = $producto->referencia;
        $productoProveedor2->precio = 100.0;
        $this->assertTrue($productoProveedor2->save());

        // procesamos la cola de trabajos
        $this->processWorkQueue();

        // recargamos la variante
        $this->assertTrue($variante->loadWhere($where));

        // el precio de coste debe ser 100 (último precio)
        $this->assertEquals(100.0, $variante->coste);

        // eliminamos el segundo producto proveedor
        $this->assertTrue($productoProveedor2->delete());

        // procesamos la cola de trabajos
        $this->processWorkQueue();

        // recargamos la variante
        $this->assertTrue($variante->loadWhere($where));

        // el precio de coste debe ser 50 (ahora es el último)
        $this->assertEquals(50.0, $variante->coste);

        // eliminamos
        $this->assertTrue($productoProveedor1->delete());
        $this->assertTrue($producto->delete());
        $this->assertTrue($proveedor1->getDefaultAddress()->delete());
        $this->assertTrue($proveedor1->delete());
        $this->assertTrue($proveedor2->getDefaultAddress()->delete());
        $this->assertTrue($proveedor2->delete());
    }

    public function testCostPriceUpdatesFromPurchaseDocument(): void
    {
        // configuramos la política de precio de coste a último precio
        Tools::settingsSet('default', 'costpricepolicy', 'last-price');
        Tools::settingsSet('default', 'updatesupplierprices', true);

        // creamos un proveedor
        $proveedor = $this->getRandomSupplier();
        $this->assertTrue($proveedor->save());

        // creamos un producto
        $producto = $this->getRandomProduct();
        $this->assertTrue($producto->save());

        // obtenemos la variante
        $variante = new Variante();
        $where = [new DataBaseWhere('referencia', $producto->referencia)];
        $this->assertTrue($variante->loadWhere($where));

        // guardamos el coste inicial
        $costeInicial = $variante->coste;

        // creamos un albarán de proveedor
        $albaran = new AlbaranProveedor();
        $albaran->setSubject($proveedor);
        $this->assertTrue($albaran->save());

        // añadimos una línea con el producto
        $linea = $albaran->getNewProductLine($producto->referencia);
        $linea->pvpunitario = 80.0;
        $linea->cantidad = 10;
        $this->assertTrue($linea->save());

        // actualizamos los totales
        $lines = [$linea];
        $this->assertTrue(Calculator::calculate($albaran, $lines, true));

        // procesamos la cola de trabajos
        $this->processWorkQueue();

        // recargamos la variante
        $this->assertTrue($variante->loadWhere($where));

        // comprobamos que el coste se ha actualizado
        $this->assertNotEquals($costeInicial, $variante->coste);
        $this->assertEquals(80.0, $variante->coste);

        // eliminamos
        $this->assertTrue($albaran->delete());
        $this->assertTrue($producto->delete());
        $this->assertTrue($proveedor->getDefaultAddress()->delete());
        $this->assertTrue($proveedor->delete());
        $this->assertTrue(ProductoProveedor::deleteWhere([]));
    }

    private function processWorkQueue(): void
    {
        while (true) {
            if (false === WorkQueue::run()) {
                break;
            }
        }
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
