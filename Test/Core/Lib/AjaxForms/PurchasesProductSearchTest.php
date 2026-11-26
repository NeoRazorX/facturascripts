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
use FacturaScripts\Core\Model\Atributo;
use FacturaScripts\Core\Model\PresupuestoProveedor;
use FacturaScripts\Core\Model\Producto;
use FacturaScripts\Core\Model\ProductoImagen;
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

        // al borrar el producto se consultan sus imágenes, y la tabla no se puede crear dentro de una transacción
        new ProductoImagen();
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
        // la variante tiene un atributo, que debe aparecer en la descripción
        $attribute = $this->createAttributeValue($this->variant, 'Color', 'rojo');

        $supplierRef = 'PRV-AUTO-' . mt_rand(1, 99999);
        $this->createSupplierProduct($this->variant->referencia, $supplierRef);

        $list = $this->autocomplete(strtolower($supplierRef), $this->supplier->codproveedor);
        $this->assertSame([$this->variant->referencia], array_keys($list));
        $this->assertSame(
            $supplierRef . ' | ' . Tools::fixHtml($this->variant->description()),
            $list[$this->variant->referencia]['value']
        );
        $this->assertStringContainsString('rojo', $list[$this->variant->referencia]['value']);

        // las referencias de otro proveedor no se muestran
        $this->assertEmpty($this->autocomplete($supplierRef, 'NOEXISTE'));

        $this->assertTrue($this->product->delete(), 'product-cant-delete');
        $this->assertTrue($attribute->delete(), 'attribute-cant-delete');
    }

    public function testFindByAttributeValue(): void
    {
        $attribute = $this->createAttributeValue($this->variant, 'Color', 'granate' . mt_rand(1, 99999));
        $value = $attribute->getValues()[0]->valor;

        // una sola palabra: el valor del atributo
        $html = $this->findProducts($value);
        $this->assertStringContainsString('data-reference="' . $this->variant->referencia . '"', $html);
        $this->assertStringNotContainsString('data-reference="' . $this->product->referencia . '"', $html);

        // varias palabras: descripción del producto y valor del atributo
        $html = $this->findProducts($this->product->descripcion . ' ' . $value);
        $this->assertStringContainsString('data-reference="' . $this->variant->referencia . '"', $html);
        $this->assertStringNotContainsString('data-reference="' . $this->product->referencia . '"', $html);

        $this->assertTrue($this->product->delete(), 'product-cant-delete');
        $this->assertTrue($attribute->delete(), 'attribute-cant-delete');
    }

    /**
     * El valor de atributo también se encuentra cuando está en la última posición de la variante.
     */
    public function testFindByAttributeValueInLastSlot(): void
    {
        $attribute = $this->createAttributeValue($this->variant, 'Acabado', 'satinado' . mt_rand(1, 99999), 4);
        $value = $attribute->getValues()[0]->valor;

        $html = $this->findProducts($value);
        $this->assertStringContainsString('data-reference="' . $this->variant->referencia . '"', $html);
        $this->assertStringNotContainsString('data-reference="' . $this->product->referencia . '"', $html);

        $this->assertTrue($this->product->delete(), 'product-cant-delete');
        $this->assertTrue($attribute->delete(), 'attribute-cant-delete');
    }

    /**
     * Los espacios repetidos no generan palabras vacías en la búsqueda.
     */
    public function testFindIgnoresEmptyWords(): void
    {
        $attribute = $this->createAttributeValue($this->variant, 'Tejido', 'lino' . mt_rand(1, 99999));
        $value = $attribute->getValues()[0]->valor;

        $modal = new class extends PurchasesModalHTML {
            /** @var string[] */
            public static $words = [];

            protected static function wordFilter(string $word): string
            {
                self::$words[] = $word;
                return parent::wordFilter($word);
            }
        };

        $doc = new PresupuestoProveedor();
        $doc->setSubject($this->supplier);
        $modal::apply($doc, ['fp_query' => '  ' . $this->product->descripcion . '   ' . $value . '  ']);
        $html = $modal::renderProductList();

        $this->assertStringContainsString('data-reference="' . $this->variant->referencia . '"', $html);
        $this->assertStringNotContainsString('data-reference="' . $this->product->referencia . '"', $html);
        $this->assertNotContains('', $modal::$words);
        $this->assertContains($value, $modal::$words);

        $this->assertTrue($this->product->delete(), 'product-cant-delete');
        $this->assertTrue($attribute->delete(), 'attribute-cant-delete');
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

    /**
     * La fila muestra las descripciones de todos los valores de atributo de la variante.
     */
    public function testListShowsAttributeValues(): void
    {
        $first = $this->createAttributeValue($this->variant, 'Ancho', 'ancho' . mt_rand(1, 99999));
        $second = $this->createAttributeValue($this->variant, 'Largo', 'largo' . mt_rand(1, 99999), 3);
        $firstDesc = $first->getValues()[0]->descripcion;
        $secondDesc = $second->getValues()[0]->descripcion;

        $html = $this->findProducts($this->product->referencia);
        $this->assertStringContainsString(
            ', ' . $firstDesc . ', ' . $secondDesc . '</td>',
            $this->getRow($html, $this->variant->referencia)
        );
        $this->assertStringNotContainsString($firstDesc, $this->getRow($html, $this->product->referencia));

        $this->assertTrue($this->product->delete(), 'product-cant-delete');
        $this->assertTrue($first->delete(), 'attribute-cant-delete');
        $this->assertTrue($second->delete(), 'attribute-cant-delete');
    }

    public function testPriceColumns(): void
    {
        $this->variant->coste = 7;
        $this->variant->precio = 15;
        $this->assertTrue($this->variant->save(), 'variant-cant-save');

        // solo la variante principal tiene precio de este proveedor
        $this->createSupplierProduct($this->product->referencia, 'PRV-PRICE-' . mt_rand(1, 99999));

        $html = $this->findProducts($this->product->referencia);
        $mainRow = $this->getRow($html, $this->product->referencia);
        $variantRow = $this->getRow($html, $this->variant->referencia);

        // precio proveedor, coste y precio de venta (el coste de la principal depende de la política de costes)
        $this->assertStringContainsString('</td>' . $this->moneyCell(10) . '<td class="text-end d-none d-md-table-cell">', $mainRow);
        $this->assertStringContainsString(
            '<td class="text-end"><span class="text-muted">-</span></td>'
            . $this->moneyCell(7, ' d-none d-md-table-cell') . $this->moneyCell(15),
            $variantRow
        );
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
        return $method->invoke($controller, $query, $codproveedor);
    }

    /**
     * Crea un atributo con un valor y lo asigna a la variante en la posición indicada.
     *
     * @param Variante $variant Variante a la que se asigna el valor.
     * @param string $name Nombre base del atributo.
     * @param string $value Valor del atributo.
     * @param int $slot Posición del atributo en la variante (1 a 4).
     * @return Atributo
     */
    private function createAttributeValue(Variante $variant, string $name, string $value, int $slot = 1): Atributo
    {
        $attribute = new Atributo();
        $attribute->nombre = $name . ' ' . mt_rand(1, 99999);
        $this->assertTrue($attribute->save(), 'attribute-cant-save');

        $attValue = $attribute->getNewValue($value);
        $this->assertTrue($attValue->save(), 'attribute-value-cant-save');

        $variant->{'idatributovalor' . $slot} = $attValue->id;
        $this->assertTrue($variant->save(), 'variant-cant-save');
        return $attribute;
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

    private function getRow(string $html, string $referencia): string
    {
        $pattern = '/<tr[^>]*data-reference="' . preg_quote($referencia, '/') . '".*?<\/tr>/s';
        $this->assertSame(1, preg_match($pattern, $html, $matches), 'row-not-found: ' . $referencia);
        return $matches[0];
    }

    private function moneyCell(float $value, string $cssClass = ''): string
    {
        return '<td class="text-end' . $cssClass . '">' . str_replace(' ', '&nbsp;', Tools::money($value)) . '</td>';
    }
}
