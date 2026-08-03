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

use FacturaScripts\Core\Model\Impuesto;
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

    public function testTotalSumPrecioIva(): void
    {
        // creamos un impuesto con IVA conocido
        $tax = new Impuesto();
        $tax->codimpuesto = 'Test999';
        $tax->descripcion = 'Test IVA totalSum';
        $tax->iva = 10.0;
        $this->assertTrue($tax->save(), 'tax-cant-save');

        // creamos un producto con ese impuesto
        $product = new Producto();
        $product->referencia = 'test-999';
        $product->descripcion = 'Test totalSum precio_iva';
        $product->codimpuesto = $tax->codimpuesto;
        $this->assertTrue($product->save(), 'product-cant-save');

        // asignamos un precio conocido a la variante por defecto
        $variant = $product->getVariants()[0];
        $variant->precio = 100.0;
        $this->assertTrue($variant->save(), 'variant-cant-save');

        // totalSum('precio_iva') debe devolver 100 * (100 + 10) / 100 = 110
        $where = [Where::eq('variantes.referencia', $variant->referencia)];
        $vp = new VarianteProducto();
        $result = $vp->totalSum('precio_iva', $where);
        $this->assertEqualsWithDelta(110.0, $result, 0.001, 'total-sum-precio-iva-wrong');

        // limpiamos
        $this->assertTrue($product->delete(), 'product-cant-delete');
        $this->assertTrue($tax->delete(), 'tax-cant-delete');
    }

    public function testTotalSumMultipleVariants(): void
    {
        // creamos un impuesto con IVA del 21%
        $tax = new Impuesto();
        $tax->codimpuesto = 'TST999';
        $tax->descripcion = 'Test IVA totalSum multiple';
        $tax->iva = 21.0;
        $this->assertTrue($tax->save(), 'tax-cant-save');

        // creamos dos productos con ese impuesto y precios distintos
        $product1 = new Producto();
        $product1->referencia = 'test-tsm1-999';
        $product1->descripcion = 'Test totalSum multiple 1';
        $product1->codimpuesto = $tax->codimpuesto;
        $this->assertTrue($product1->save(), 'product1-cant-save');
        $variant1 = $product1->getVariants()[0];
        $variant1->precio = 100.0;
        $this->assertTrue($variant1->save(), 'variant1-cant-save');

        $product2 = new Producto();
        $product2->referencia = 'test-tsm2-100';
        $product2->descripcion = 'Test totalSum multiple 2';
        $product2->codimpuesto = $tax->codimpuesto;
        $this->assertTrue($product2->save(), 'product2-cant-save');
        $variant2 = $product2->getVariants()[0];
        $variant2->precio = 200.0;
        $this->assertTrue($variant2->save(), 'variant2-cant-save');

        // totalSum('precio_iva') para ambas variantes debe ser (100 + 200) * 1.21 = 363
        $where = [Where::in('variantes.referencia', [$variant1->referencia, $variant2->referencia])];
        $vp = new VarianteProducto();
        $result = $vp->totalSum('precio_iva', $where);
        $this->assertEqualsWithDelta(363.0, $result, 0.001, 'total-sum-precio-iva-multiple-wrong');

        // limpiamos
        $this->assertTrue($product1->delete(), 'product1-cant-delete');
        $this->assertTrue($product2->delete(), 'product2-cant-delete');
        $this->assertTrue($tax->delete(), 'tax-cant-delete');
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

    public function testHasColumn(): void
    {
        $variant = new VarianteProducto();

        $this->assertTrue($variant->hasColumn('referencia'));
        $this->assertTrue($variant->hasColumn('precio_iva'));
        $this->assertFalse($variant->hasColumn('columna_inexistente'));
        $this->assertFalse($variant->hasColumn(''));
    }
}
