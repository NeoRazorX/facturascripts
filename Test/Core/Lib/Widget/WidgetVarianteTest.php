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

namespace FacturaScripts\Test\Core\Lib\Widget;

use FacturaScripts\Core\Lib\Widget\WidgetVariante;
use FacturaScripts\Core\Model\Producto;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class WidgetVarianteTest extends TestCase
{
    use LogErrorsTrait;

    /** @var Producto[] */
    private $products = [];

    public function testModalListIsNotRenderedOnEdit(): void
    {
        $selected = $this->createProduct('ZZZ-WV-' . mt_rand(1, 99999));

        $model = (object)['test' => $selected->referencia];
        $html = $this->getWidget('referencia')->edit($model);

        $this->assertStringContainsString('widget-variante-modal', $html);
        $this->assertStringContainsString($selected->referencia, $html);
        $this->assertStringNotContainsString('widget-variante-option', $html);
        $this->assertMatchesRegularExpression('/<tbody id="list_\d+"><\/tbody>/', $html);
    }

    public function testSearchKeepsSortOrder(): void
    {
        $token = 'WVSORT' . mt_rand(1, 99999);
        $cheap = $this->createProduct($token . '-B', 10);
        $expensive = $this->createProduct($token . '-A', 30);
        $middle = $this->createProduct($token . '-C', 20);

        $cases = [
            'price-asc' => [$cheap, $middle, $expensive],
            'price-desc' => [$expensive, $middle, $cheap],
            'ref-asc' => [$expensive, $cheap, $middle],
            'ref-desc' => [$middle, $cheap, $expensive],
        ];
        foreach ($cases as $sort => $expected) {
            $list = $this->getWidget('referencia')->variantes($token, '', '', $sort);

            $this->assertSame(
                array_map(fn($product) => $product->referencia, $expected),
                array_map(fn($variante) => $variante->referencia, $list),
                $sort
            );
        }
    }

    public function testSelectedVariantIsFirstWhenMatchingByIdProducto(): void
    {
        $this->createProduct('AAA-WV-' . mt_rand(1, 99999));
        $selected = $this->createProduct('ZZZ-WV-' . mt_rand(1, 99999));

        $widget = $this->getWidget('idproducto');
        $widget->setCustomValue($selected->idproducto);
        $list = $widget->variantes();

        $this->assertNotEmpty($list);
        $this->assertSame($selected->idproducto, $list[0]->idproducto);
        $this->assertSame($selected->referencia, $list[0]->referencia);
        $this->assertSelectedOnlyOnce($list, $selected->referencia);
    }

    public function testSelectedVariantIsFirstWhenMatchingByReferencia(): void
    {
        $this->createProduct('AAA-WV-' . mt_rand(1, 99999));
        $selected = $this->createProduct('ZZZ-WV-' . mt_rand(1, 99999));

        $widget = $this->getWidget('referencia');
        $widget->setCustomValue($selected->referencia);
        $list = $widget->variantes();

        $this->assertNotEmpty($list);
        $this->assertSame($selected->referencia, $list[0]->referencia);
        $this->assertSelectedOnlyOnce($list, $selected->referencia);
    }

    protected function tearDown(): void
    {
        foreach ($this->products as $product) {
            $product->delete();
        }
        $this->products = [];

        $this->logErrors();
    }

    private function assertSelectedOnlyOnce(array $list, string $referencia): void
    {
        $count = 0;
        foreach ($list as $item) {
            if ($item->referencia === $referencia) {
                $count++;
            }
        }
        $this->assertSame(1, $count);
    }

    private function createProduct(string $referencia, float $precio = 0): Producto
    {
        $product = new Producto();
        $product->referencia = $referencia;
        $product->descripcion = 'Widget variante test ' . $referencia;
        $this->assertTrue($product->save());

        $variante = $product->getVariants()[0];
        $variante->precio = $precio;
        $this->assertTrue($variante->save());

        $this->products[] = $product;
        return $product;
    }

    private function getWidget(string $match): WidgetVariante
    {
        return new WidgetVariante([
            'children' => [],
            'fieldname' => 'test',
            'match' => $match,
            'type' => 'variante',
        ]);
    }
}
