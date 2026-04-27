<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Lib;

use FacturaScripts\Core\Lib\CostPriceTools;
use FacturaScripts\Core\Model\Producto;
use FacturaScripts\Core\Model\ProductoProveedor;
use FacturaScripts\Core\Model\Proveedor;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class CostPriceToolsTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    /** @var Proveedor[] */
    private array $suppliersToCleanup = [];

    /** @var ProductoProveedor[] */
    private array $supplierProductsToCleanup = [];

    /** @var Producto[] */
    private array $productsToCleanup = [];

    protected function setUp(): void
    {
        self::setDefaultSettings();
        $this->suppliersToCleanup = [];
        $this->supplierProductsToCleanup = [];
        $this->productsToCleanup = [];
    }

    public function testLastPriceExcludesZeroOrNegative(): void
    {
        $product = $this->createProduct();
        $variant = $product->getVariants()[0];

        // p1 más antiguo y positivo
        $this->createSupplierProduct($product, 10.0, '2023-01-01 00:00:00');
        // p2 es el más reciente pero con neto = 0 (muestra/promo)
        $this->createSupplierProduct($product, 0.0, '2024-06-01 00:00:00');
        // p3 intermedio y positivo
        $this->createSupplierProduct($product, 20.0, '2023-06-01 00:00:00');

        Tools::settingsSet('default', 'costpricepolicy', 'last-price');
        CostPriceTools::update($variant);

        $variant->loadFromCode($variant->idvariante);
        $this->assertEquals(20.0, (float)$variant->coste, 'last-price debe ignorar neto = 0 y devolver el último positivo');
    }

    public function testAveragePriceExcludesZeroOrNegative(): void
    {
        $product = $this->createProduct();
        $variant = $product->getVariants()[0];

        $this->createSupplierProduct($product, 10.0, '2024-01-01 00:00:00');
        $this->createSupplierProduct($product, 0.0, '2024-02-01 00:00:00');
        $this->createSupplierProduct($product, 20.0, '2024-03-01 00:00:00');

        Tools::settingsSet('default', 'costpricepolicy', 'average-price');
        CostPriceTools::update($variant);

        $variant->loadFromCode($variant->idvariante);
        // media de [10, 20] excluyendo el 0; sin filtro sería (10+0+20)/3 = 10
        $this->assertEquals(15.0, (float)$variant->coste, 'average-price debe excluir filas con neto <= 0');
    }

    public function testHighPriceExcludesZeroOrNegative(): void
    {
        $product = $this->createProduct();
        $variant = $product->getVariants()[0];

        // precio alto pero descuento del 100% deja neto = 0
        $this->createSupplierProduct($product, 50.0, '2024-01-01 00:00:00', 100.0);
        // precio menor pero neto positivo
        $this->createSupplierProduct($product, 30.0, '2024-01-01 00:00:00');
        $this->createSupplierProduct($product, 15.0, '2024-01-01 00:00:00');

        Tools::settingsSet('default', 'costpricepolicy', 'high-price');
        CostPriceTools::update($variant);

        $variant->loadFromCode($variant->idvariante);
        // sin filtro habría elegido la fila precio=50 (neto=0); con filtro, precio=30 (neto=30)
        $this->assertEquals(30.0, (float)$variant->coste, 'high-price debe ignorar filas con neto <= 0 aunque su precio sea mayor');
    }

    public function testLastPriceLeavesCosteUnchangedWhenAllZero(): void
    {
        $product = $this->createProduct();
        $variant = $product->getVariants()[0];
        $variant->coste = 7.5;
        $this->assertTrue($variant->save());

        $this->createSupplierProduct($product, 0.0, '2024-01-01 00:00:00');
        $this->createSupplierProduct($product, 0.0, '2024-02-01 00:00:00');

        Tools::settingsSet('default', 'costpricepolicy', 'last-price');
        CostPriceTools::update($variant);

        $variant->loadFromCode($variant->idvariante);
        $this->assertEquals(7.5, (float)$variant->coste, 'sin filas con neto > 0 el coste anterior debe preservarse');
    }

    private function createProduct(): Producto
    {
        $product = $this->getRandomProduct();
        $this->assertTrue($product->save());
        $this->productsToCleanup[] = $product;

        return $product;
    }

    private function createSupplierProduct(
        Producto $product,
        float $precio,
        string $actualizado,
        float $dtopor = 0.0
    ): ProductoProveedor {
        // un proveedor distinto por fila para evitar el unique (codproveedor, refproveedor, referencia, coddivisa)
        $supplier = $this->getRandomSupplier();
        $this->assertTrue($supplier->save());
        $this->suppliersToCleanup[] = $supplier;

        $row = new ProductoProveedor();
        $row->referencia = $product->referencia;
        $row->idproducto = $product->idproducto;
        $row->codproveedor = $supplier->codproveedor;
        $row->precio = $precio;
        $row->dtopor = $dtopor;
        $row->actualizado = $actualizado;
        $this->assertTrue($row->save());
        $this->supplierProductsToCleanup[] = $row;

        return $row;
    }

    protected function tearDown(): void
    {
        foreach ($this->supplierProductsToCleanup as $row) {
            $row->delete();
        }
        foreach ($this->productsToCleanup as $product) {
            $product->delete();
        }
        foreach ($this->suppliersToCleanup as $supplier) {
            $address = $supplier->getDefaultAddress();
            if ($address->exists()) {
                $address->delete();
            }
            $supplier->delete();
        }

        $this->logErrors();
    }
}
