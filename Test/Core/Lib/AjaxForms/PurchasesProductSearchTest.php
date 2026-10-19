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

namespace FacturaScripts\Test\Core\Lib\AjaxForms;

use FacturaScripts\Core\Controller\EditPedidoProveedor;
use FacturaScripts\Core\Lib\AjaxForms\PurchasesModalHTML;
use FacturaScripts\Core\Model\PresupuestoProveedor;
use FacturaScripts\Core\Model\Producto;
use FacturaScripts\Core\Model\ProductoProveedor;
use FacturaScripts\Core\Model\Proveedor;
use FacturaScripts\Core\Model\Stock;
use FacturaScripts\Core\Model\Variante;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class PurchasesProductSearchTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    /** @var Producto */
    private $product;

    /** @var Proveedor */
    private $supplier;

    /** @var Variante */
    private $variant;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();

        // la consulta del modal necesita la tabla de stocks
        new Stock();
    }

    protected function setUp(): void
    {
        $this->supplier = $this->getRandomSupplier();
        $this->assertTrue($this->supplier->save(), 'supplier-cant-save');

        $this->product = $this->getRandomProduct();
        $this->assertTrue($this->product->save(), 'product-cant-save');

        // segunda variante, distinta de la principal
        $this->variant = new Variante();
        $this->variant->idproducto = $this->product->idproducto;
        $this->variant->referencia = $this->product->referencia . '-B';
        $this->assertTrue($this->variant->save(), 'variant-cant-save');
    }

    public function testAutocompleteBySupplierReference(): void
    {
        $supplierRef = 'PRV-AUTO-' . mt_rand(1, 99999);
        $this->createSupplierProduct($this->variant->referencia, $supplierRef);

        $list = $this->autocomplete(strtolower($supplierRef), $this->supplier->codproveedor);
        $this->assertSame([$this->variant->referencia], array_keys($list));
        $this->assertStringStartsWith($supplierRef . ' | ', $list[$this->variant->referencia]['value']);

        // las referencias de otro proveedor no se muestran
        $this->assertEmpty($this->autocomplete($supplierRef, 'NOEXISTE'));
    }

    public function testFindByMainVariantSupplierReference(): void
    {
        $supplierRef = 'PRV-MAIN-' . mt_rand(1, 99999);
        $this->createSupplierProduct($this->product->referencia, $supplierRef);

        $html = $this->findProducts($supplierRef);
        $this->assertStringContainsString('data-reference="' . $this->product->referencia . '"', $html);
        $this->assertStringNotContainsString('data-reference="' . $this->variant->referencia . '"', $html);
    }

    public function testFindBySecondaryVariantSupplierReference(): void
    {
        $supplierRef = 'PRV-VAR-' . mt_rand(1, 99999);
        $this->createSupplierProduct($this->variant->referencia, $supplierRef);

        $html = $this->findProducts($supplierRef);
        $this->assertStringContainsString('data-reference="' . $this->variant->referencia . '"', $html);
        $this->assertStringNotContainsString('data-reference="' . $this->product->referencia . '"', $html);

        // la referencia del proveedor se identifica al pasar el ratón
        $title = 'title="' . Tools::trans('supplier-reference') . '">' . $supplierRef . '</span>';
        $this->assertStringContainsString($title, $html);
    }

    protected function tearDown(): void
    {
        $this->product->delete();
        $this->supplier->getDefaultAddress()->delete();
        $this->supplier->delete();
        $this->logErrors();
    }

    private function autocomplete(string $query, string $codproveedor): array
    {
        $controller = new EditPedidoProveedor('EditPedidoProveedor', '/EditPedidoProveedor');
        $method = new ReflectionMethod(EditPedidoProveedor::class, 'autocompleteSupplierReference');
        $method->setAccessible(true);
        return $method->invoke($controller, $query, $codproveedor);
    }

    private function createSupplierProduct(string $referencia, string $refproveedor): void
    {
        $supplierProduct = new ProductoProveedor();
        $supplierProduct->codproveedor = $this->supplier->codproveedor;
        $supplierProduct->referencia = $referencia;
        $supplierProduct->refproveedor = $refproveedor;
        $supplierProduct->precio = 10;
        $this->assertTrue($supplierProduct->save(), 'supplier-product-cant-save');
    }

    private function findProducts(string $query): string
    {
        $doc = new PresupuestoProveedor();
        $doc->setSubject($this->supplier);

        PurchasesModalHTML::apply($doc, ['fp_query' => $query]);
        return PurchasesModalHTML::renderProductList();
    }
}
