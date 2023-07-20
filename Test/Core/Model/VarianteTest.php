<?php

namespace Model;

use FacturaScripts\Core\Model\Producto;
use PHPUnit\Framework\TestCase;

class VarianteTest extends TestCase
{
    public function testNotAllowNullValues()
    {
        $producto = new Producto();
        $producto->save();

        $variante = $producto->getVariants()[0];
        $variante->coste = null;
        $variante->precio = null;
        $variante->margen = null;
        $variante->save();

        $this->assertNotNull($variante->coste);
        $this->assertNotNull($variante->precio);
        $this->assertNotNull($variante->margen);

        $producto->delete();
    }
}
