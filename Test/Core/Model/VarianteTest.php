<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2022-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\ProductType;
use FacturaScripts\Core\Model\Producto;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class VarianteTest extends TestCase
{
    use LogErrorsTrait;

    public function testNotAllowNullValues(): void
    {
        // creamos un producto
        $producto = new Producto();
        $this->assertTrue($producto->save());

        // obtenemos la primera variante y le ponemos valores nulos
        $variante = $producto->getVariants()[0];
        $variante->coste = null;
        $variante->precio = null;
        $variante->margen = null;
        $this->assertTrue($variante->save());

        $this->assertNotNull($variante->coste);
        $this->assertNotNull($variante->precio);
        $this->assertNotNull($variante->margen);

        // eliminamos el producto
        $this->assertTrue($producto->delete());

        // comprobamos que se ha eliminado la variante
        $this->assertEmpty($variante->exists());
    }

    public function testSetPriceWithTax(): void
    {
        // creamos un producto con IVA 21%
        $producto = new Producto();
        $producto->codimpuesto = 'IVA21';
        $this->assertTrue($producto->save());

        // obtenemos la primera variante y le ponemos un precio con IVA
        $variante = $producto->getVariants()[0];
        $variante->setPriceWithTax(100);

        // comprobamos que el precio sin IVA es correcto
        $this->assertEquals(82.64463, $variante->precio);

        // comprobamos que el precio con IVA es correcto
        $this->assertEquals(100.00000, $variante->priceWithTax());

        // eliminamos el producto
        $this->assertTrue($producto->delete());
    }

    public function testSetPriceWithTaxSecondHand(): void
    {
        // creamos un producto de segunda mano con IVA 21%
        $producto = new Producto();
        $producto->codimpuesto = 'IVA21';
        $producto->tipo = ProductType::SECOND_HAND;
        $this->assertTrue($producto->save());

        // obtenemos la primera variante y le ponemos un conste y un precio con IVA
        $variante = $producto->getVariants()[0];
        $variante->coste = 50;
        $variante->setPriceWithTax(100);

        // comprobamos que el precio sin IVA es correcto
        $this->assertEquals(91.32231, $variante->precio);

        // comprobamos que el precio con IVA es correcto
        $this->assertEquals(100.00000, $variante->priceWithTax());

        // eliminamos el producto
        $this->assertTrue($producto->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
