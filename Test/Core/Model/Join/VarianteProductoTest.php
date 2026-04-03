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

namespace FacturaScripts\Test\Core\Model\Join;

use FacturaScripts\Core\Model\Join\VarianteProducto;
use FacturaScripts\Core\Model\Producto;
use FacturaScripts\Core\Where;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class VarianteProductoTest extends TestCase
{
    use LogErrorsTrait;

    public function testCanListProductVariant(): void
    {
        // creamos un producto
        $product = new Producto();
        $product->referencia = 'test-vp-' . mt_rand(1, 999999);
        $product->descripcion = 'Test VarianteProducto';
        $this->assertTrue($product->save(), 'product-cant-save');

        // obtenemos la variante por defecto
        $variants = $product->getVariants();
        $this->assertNotEmpty($variants, 'product-has-no-variants');
        $variant = $variants[0];

        // buscamos con VarianteProducto filtrando por referencia
        $where = [Where::eq('variantes.referencia', $variant->referencia)];
        $results = VarianteProducto::all($where);
        $this->assertNotEmpty($results, 'variante-producto-not-found');

        // comprobamos que los datos coinciden
        $vp = $results[0];
        $this->assertEquals($product->idproducto, $vp->idproducto);
        $this->assertEquals($product->descripcion, $vp->descripcion);
        $this->assertEquals($variant->referencia, $vp->referencia);
        $this->assertEquals($variant->idvariante, $vp->idvariante);
        $this->assertEquals($variant->precio, $vp->precio);
        $this->assertEquals($variant->coste, $vp->coste);

        // comprobamos count
        $count = VarianteProducto::count($where);
        $this->assertEquals(1, $count);

        // eliminamos el producto
        $this->assertTrue($product->delete(), 'product-cant-delete');

        // verificamos que ya no aparece
        $results = VarianteProducto::all($where);
        $this->assertEmpty($results, 'variante-producto-still-exists');
    }

    public function testClearResetsAttributes(): void
    {
        $vp = new VarianteProducto();
        $this->assertNull($vp->referencia);
        $this->assertNull($vp->idproducto);
        $this->assertNull($vp->descripcion);
    }

    public function testIdReturnsProductId(): void
    {
        // creamos un producto
        $product = new Producto();
        $product->referencia = 'test-vp-id-' . mt_rand(1, 999999);
        $product->descripcion = 'Test id()';
        $this->assertTrue($product->save(), 'product-cant-save');

        // buscamos con VarianteProducto
        $variant = $product->getVariants()[0];
        $where = [Where::eq('variantes.referencia', $variant->referencia)];
        $results = VarianteProducto::all($where);
        $this->assertNotEmpty($results);

        // id() debe devolver el idproducto (master model es Producto)
        $vp = $results[0];
        $this->assertEquals($product->idproducto, $vp->id());

        // eliminamos
        $this->assertTrue($product->delete());
    }
}
