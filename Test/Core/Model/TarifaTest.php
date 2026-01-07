<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\GrupoClientes;
use FacturaScripts\Core\Model\PresupuestoCliente;
use FacturaScripts\Core\Model\Tarifa;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class TarifaTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    public function testCreate(): void
    {
        // creamos una tarifa
        $tarifa = new Tarifa();
        $tarifa->codtarifa = '01';
        $tarifa->nombre = 'Tarifa de prueba';
        $tarifa->valorx = 10;
        $tarifa->valory = 1;
        $this->assertTrue($tarifa->save(), 'tarifa-can-not-save');

        // comprobamos que se ha guardado correctamente
        $this->assertTrue($tarifa->exists(), 'tarifa-does-not-exist');
        $this->assertEquals('01', $tarifa->codtarifa, 'tarifa-codtarifa-is-not-01');
        $this->assertEquals('Tarifa de prueba', $tarifa->nombre, 'tarifa-nombre-is-not-Tarifa de prueba');
        $this->assertEquals(10, $tarifa->valorx, 'tarifa-valorx-is-not-10');
        $this->assertEquals(1, $tarifa->valory, 'tarifa-valory-is-not-1');

        // eliminamos la tarifa
        $this->assertTrue($tarifa->delete(), 'tarifa-can-not-delete');
    }

    public function testHtmlOnFields(): void
    {
        // creamos una tarifa con html en los campos
        $tarifa = new Tarifa();
        $tarifa->codtarifa = '<test>';
        $tarifa->nombre = '<p>Tarifa de prueba</p>';
        $tarifa->valorx = 10;
        $this->assertFalse($tarifa->save(), 'tarifa-can-not-save');

        // comprobamos que no se puede guardar html en el campo codtarifa
        $this->assertFalse($tarifa->save(), 'tarifa-can-save-html-in-codtarifa');

        // cambiamos el valor del campo codtarifa
        $tarifa->codtarifa = '01';

        // comprobamos que se puede guardar correctamente
        $this->assertTrue($tarifa->save(), 'tarifa-can-not-save');

        // comprobamos que el html del campo nombre se ha escapado correctamente
        $this->assertEquals('&lt;p&gt;Tarifa de prueba&lt;/p&gt;', $tarifa->nombre, 'tarifa-nombre-html-is-not-escaped');

        // eliminamos la tarifa
        $this->assertTrue($tarifa->delete(), 'tarifa-can-not-delete');
    }

    public function testApply(): void
    {
        // probamos una tarifa de precio de coste
        $tarifa = new Tarifa();
        $tarifa->aplicar = Tarifa::APPLY_COST;
        $tarifa->codtarifa = '01';
        $tarifa->nombre = 'Tarifa de prueba';
        $tarifa->valorx = 10;
        $tarifa->valory = 1;

        // probamos
        $coste = 50;
        $precio = 100;
        $this->assertEquals(56, $tarifa->apply($coste, $precio));

        // ahora modificamos la tarifa para que sea de precio de venta
        $tarifa->aplicar = Tarifa::APPLY_PRICE;

        // probamos
        $this->assertEquals(89, $tarifa->apply($coste, $precio));
    }

    public function testApplyCostWithoutLimits(): void
    {
        // tarifa sobre coste sin límites
        $tarifa = new Tarifa();
        $tarifa->aplicar = Tarifa::APPLY_COST;
        $tarifa->maxpvp = false;
        $tarifa->mincoste = false;
        $tarifa->valorx = 10; // +10%
        $tarifa->valory = 5; // +5

        $coste = 100;
        $precio = 150;

        // Resultado: 100 + (100 * 10 / 100) + 5 = 100 + 10 + 5 = 115
        $this->assertEquals(115, $tarifa->apply($coste, $precio));
    }

    public function testApplyCostWithMaxPvp(): void
    {
        // tarifa sobre coste con límite máximo de pvp
        $tarifa = new Tarifa();
        $tarifa->aplicar = Tarifa::APPLY_COST;
        $tarifa->maxpvp = true;
        $tarifa->mincoste = false;
        $tarifa->valorx = 100; // +100%
        $tarifa->valory = 50; // +50

        $coste = 100;
        $precio = 150;

        // Sin límite sería: 100 + (100 * 100 / 100) + 50 = 100 + 100 + 50 = 250
        // Con maxpvp, se limita a 150
        $this->assertEquals(150, $tarifa->apply($coste, $precio));
    }

    public function testApplyCostWithMinCoste(): void
    {
        // tarifa sobre coste con límite mínimo (no debería aplicar si aumenta el precio)
        $tarifa = new Tarifa();
        $tarifa->aplicar = Tarifa::APPLY_COST;
        $tarifa->maxpvp = false;
        $tarifa->mincoste = true;
        $tarifa->valorx = 10; // +10%
        $tarifa->valory = 5; // +5

        $coste = 100;
        $precio = 150;

        // Resultado: 100 + (100 * 10 / 100) + 5 = 115 (mayor que coste, no se limita)
        $this->assertEquals(115, $tarifa->apply($coste, $precio));
    }

    public function testApplyCostWithBothLimits(): void
    {
        // tarifa sobre coste con ambos límites
        $tarifa = new Tarifa();
        $tarifa->aplicar = Tarifa::APPLY_COST;
        $tarifa->maxpvp = true;
        $tarifa->mincoste = true;
        $tarifa->valorx = 100; // +100%
        $tarifa->valory = 50; // +50

        $coste = 100;
        $precio = 150;

        // Sin límite sería: 100 + (100 * 100 / 100) + 50 = 250
        // Con maxpvp, se limita a 150
        $this->assertEquals(150, $tarifa->apply($coste, $precio));
    }

    public function testApplyPriceWithoutLimits(): void
    {
        // tarifa sobre precio sin límites
        $tarifa = new Tarifa();
        $tarifa->aplicar = Tarifa::APPLY_PRICE;
        $tarifa->maxpvp = false;
        $tarifa->mincoste = false;
        $tarifa->valorx = 20; // -20%
        $tarifa->valory = 10; // -10

        $coste = 100;
        $precio = 200;

        // Resultado: 200 - (200 * 20 / 100) - 10 = 200 - 40 - 10 = 150
        $this->assertEquals(150, $tarifa->apply($coste, $precio));
    }

    public function testApplyPriceWithMaxPvp(): void
    {
        // tarifa sobre precio con límite máximo (no debería aplicar si reduce el precio)
        $tarifa = new Tarifa();
        $tarifa->aplicar = Tarifa::APPLY_PRICE;
        $tarifa->maxpvp = true;
        $tarifa->mincoste = false;
        $tarifa->valorx = 20; // -20%
        $tarifa->valory = 10; // -10

        $coste = 100;
        $precio = 200;

        // Resultado: 200 - (200 * 20 / 100) - 10 = 150 (menor que pvp, no se limita)
        $this->assertEquals(150, $tarifa->apply($coste, $precio));
    }

    public function testApplyPriceWithMinCoste(): void
    {
        // tarifa sobre precio con límite mínimo de coste
        $tarifa = new Tarifa();
        $tarifa->aplicar = Tarifa::APPLY_PRICE;
        $tarifa->maxpvp = false;
        $tarifa->mincoste = true;
        $tarifa->valorx = 60; // -60%
        $tarifa->valory = 20; // -20

        $coste = 100;
        $precio = 200;

        // Sin límite sería: 200 - (200 * 60 / 100) - 20 = 200 - 120 - 20 = 60
        // Con mincoste, se limita a 100
        $this->assertEquals(100, $tarifa->apply($coste, $precio));
    }

    public function testApplyPriceWithBothLimits(): void
    {
        // tarifa sobre precio con ambos límites
        $tarifa = new Tarifa();
        $tarifa->aplicar = Tarifa::APPLY_PRICE;
        $tarifa->maxpvp = true;
        $tarifa->mincoste = true;
        $tarifa->valorx = 60; // -60%
        $tarifa->valory = 20; // -20

        $coste = 100;
        $precio = 200;

        // Sin límite sería: 200 - (200 * 60 / 100) - 20 = 60
        // Con mincoste, se limita a 100
        $this->assertEquals(100, $tarifa->apply($coste, $precio));
    }

    public function testApplyCostNegativeDiscount(): void
    {
        // tarifa sobre coste con descuento negativo (reduce el precio desde el coste)
        $tarifa = new Tarifa();
        $tarifa->aplicar = Tarifa::APPLY_COST;
        $tarifa->maxpvp = false;
        $tarifa->mincoste = true;
        $tarifa->valorx = -20; // -20%
        $tarifa->valory = -10; // -10

        $coste = 100;
        $precio = 200;

        // Resultado: 100 + (100 * -20 / 100) + (-10) = 100 - 20 - 10 = 70
        // Con mincoste, se limita a 100
        $this->assertEquals(100, $tarifa->apply($coste, $precio));
    }

    public function testApplyPriceNegativeDiscount(): void
    {
        // tarifa sobre precio con descuento negativo (aumenta el precio)
        $tarifa = new Tarifa();
        $tarifa->aplicar = Tarifa::APPLY_PRICE;
        $tarifa->maxpvp = true;
        $tarifa->mincoste = false;
        $tarifa->valorx = -30; // +30%
        $tarifa->valory = -20; // +20

        $coste = 100;
        $precio = 200;

        // Resultado: 200 - (200 * -30 / 100) - (-20) = 200 + 60 + 20 = 280
        // Con maxpvp, se limita a 200
        $this->assertEquals(200, $tarifa->apply($coste, $precio));
    }

    public function testApplyCostWithBothLimitsAndZeroPvp(): void
    {
        // tarifa sobre coste con ambos límites y pvp = 0 (caso edge)
        $tarifa = new Tarifa();
        $tarifa->aplicar = Tarifa::APPLY_COST;
        $tarifa->maxpvp = true;
        $tarifa->mincoste = true;
        $tarifa->valorx = 10; // +10%
        $tarifa->valory = 5; // +5

        $coste = 100;
        $precio = 0;

        // Resultado: 100 + (100 * 10 / 100) + 5 = 115
        // Con maxpvp = true, se limitaría a 0 (precio)
        // Pero con mincoste = true, nunca debe bajar del coste (100)
        // mincoste debe tener prioridad sobre maxpvp para garantizar que no se venda por debajo del coste
        $this->assertEquals(100, $tarifa->apply($coste, $precio));
    }

    public function testApplyPriceWithBothLimitsWhenPriceBelowCost(): void
    {
        // caso donde el pvp ya está por debajo del coste (vendiendo a pérdida)
        $tarifa = new Tarifa();
        $tarifa->aplicar = Tarifa::APPLY_PRICE;
        $tarifa->maxpvp = true;
        $tarifa->mincoste = true;
        $tarifa->valorx = 20; // -20%
        $tarifa->valory = 10; // -10

        $coste = 100;
        $precio = 80; // ya vendiendo por debajo del coste

        // Resultado: 80 - (80 * 20 / 100) - 10 = 80 - 16 - 10 = 54
        // Con maxpvp: 54 no es mayor que 80, no se limita
        // Con mincoste: 54 < 100, se limita a 100
        $this->assertEquals(100, $tarifa->apply($coste, $precio));
    }

    public function testApplyCostWithBothLimitsWhenPriceBelowCost(): void
    {
        // caso donde el pvp ya está por debajo del coste, aplicando tarifa sobre coste
        $tarifa = new Tarifa();
        $tarifa->aplicar = Tarifa::APPLY_COST;
        $tarifa->maxpvp = true;
        $tarifa->mincoste = true;
        $tarifa->valorx = 20; // +20%
        $tarifa->valory = 10; // +10

        $coste = 100;
        $precio = 80; // ya vendiendo por debajo del coste

        // Resultado: 100 + (100 * 20 / 100) + 10 = 100 + 20 + 10 = 130
        // Con maxpvp: 130 > 80, se limita a 80
        // Con mincoste: 80 < 100, se limita a 100
        $this->assertEquals(100, $tarifa->apply($coste, $precio));
    }

    public function testApplyToCustomer(): void
    {
        // creamos una tarifa
        $tarifa = new Tarifa();
        $tarifa->codtarifa = '01';
        $tarifa->nombre = 'Tarifa de prueba';
        $tarifa->valorx = 10;
        $tarifa->valory = 1;
        $this->assertTrue($tarifa->save(), 'tarifa-can-not-save');

        // creamos un cliente y le asignamos la tarifa
        $cliente = $this->getRandomCustomer();
        $cliente->codgrupo = null;
        $cliente->codtarifa = '01';
        $this->assertTrue($cliente->save(), 'cliente-can-not-save');

        // creamos un producto
        $producto = $this->getRandomProduct();
        $producto->precio = 100;
        $this->assertTrue($producto->save(), 'producto-can-not-save');
        $this->assertEquals(100, $producto->precio, 'producto-precio-is-not-100');

        // hacemos un presupuesto al cliente
        $presupuesto = new PresupuestoCliente();
        $presupuesto->setSubject($cliente);
        $this->assertTrue($presupuesto->save(), 'presupuesto-can-not-save');

        // añadimos un producto al presupuesto
        $line = $presupuesto->getNewProductLine($producto->referencia);

        // comprobamos que el precio del producto es el correcto: (100 - Y) - (100 - X) / 100
        $this->assertEquals(89, $line->pvpunitario, 'line-total-is-not-89');

        // eliminamos
        $this->assertTrue($presupuesto->delete(), 'presupuesto-can-not-delete');
        $this->assertTrue($producto->delete(), 'producto-can-not-delete');
        $this->assertTrue($cliente->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($cliente->delete(), 'cliente-can-not-delete');
        $this->assertTrue($tarifa->delete(), 'tarifa-can-not-delete');
    }

    public function testApplyToGroup(): void
    {
        // creamos una tarifa
        $tarifa = new Tarifa();
        $tarifa->codtarifa = '02';
        $tarifa->nombre = 'Tarifa de prueba';
        $tarifa->valorx = 20;
        $this->assertTrue($tarifa->save(), 'tarifa-can-not-save');

        // creamos un grupo y le asignamos la tarifa
        $grupo = new GrupoClientes();
        $grupo->codgrupo = '01';
        $grupo->codtarifa = '02';
        $grupo->nombre = 'Grupo de prueba';
        $this->assertTrue($grupo->save(), 'grupo-can-not-save');

        // creamos un cliente y le asignamos el grupo
        $cliente = $this->getRandomCustomer();
        $cliente->codgrupo = '01';
        $this->assertTrue($cliente->save(), 'cliente-can-not-save');

        // creamos un producto
        $producto = $this->getRandomProduct();
        $producto->precio = 100;
        $this->assertTrue($producto->save(), 'producto-can-not-save');
        $this->assertEquals(100, $producto->precio, 'producto-precio-is-not-100');

        // hacemos un presupuesto al cliente
        $presupuesto = new PresupuestoCliente();
        $presupuesto->setSubject($cliente);
        $this->assertTrue($presupuesto->save(), 'presupuesto-can-not-save');

        // añadimos un producto al presupuesto
        $line = $presupuesto->getNewProductLine($producto->referencia);

        // comprobamos que el precio del producto es el correcto: (100 - Y) - (100 - X) / 100
        $this->assertEquals(80, $line->pvpunitario, 'line-total-is-not-80');

        // eliminamos
        $this->assertTrue($presupuesto->delete(), 'presupuesto-can-not-delete');
        $this->assertTrue($producto->delete(), 'producto-can-not-delete');
        $this->assertTrue($cliente->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($cliente->delete(), 'cliente-can-not-delete');
        $this->assertTrue($grupo->delete(), 'grupo-can-not-delete');
        $this->assertTrue($tarifa->delete(), 'tarifa-can-not-delete');
    }

    public function testApplyCustomerAndGroup(): void
    {
        // creamos una tarifa
        $tarifa = new Tarifa();
        $tarifa->codtarifa = '03';
        $tarifa->nombre = 'Tarifa de prueba';
        $tarifa->valorx = 30;
        $this->assertTrue($tarifa->save(), 'tarifa-can-not-save');

        // creamos un grupo y le asignamos la tarifa
        $grupo = new GrupoClientes();
        $grupo->codgrupo = '01';
        $grupo->codtarifa = '03';
        $grupo->nombre = 'Grupo de prueba';
        $this->assertTrue($grupo->save(), 'grupo-can-not-save');

        // creamos una segunda tarifa
        $tarifa2 = new Tarifa();
        $tarifa2->codtarifa = '04';
        $tarifa2->nombre = 'Tarifa de prueba';
        $tarifa2->valorx = 40;
        $this->assertTrue($tarifa2->save(), 'tarifa-can-not-save');

        // creamos un cliente y le asignamos el grupo y la segunda tarifa
        $cliente = $this->getRandomCustomer();
        $cliente->codgrupo = '01';
        $cliente->codtarifa = '04';
        $this->assertTrue($cliente->save(), 'cliente-can-not-save');

        // creamos un producto
        $producto = $this->getRandomProduct();
        $producto->precio = 100;
        $this->assertTrue($producto->save(), 'producto-can-not-save');
        $this->assertEquals(100, $producto->precio, 'producto-precio-is-not-100');

        // hacemos un presupuesto al cliente
        $presupuesto = new PresupuestoCliente();
        $presupuesto->setSubject($cliente);
        $this->assertTrue($presupuesto->save(), 'presupuesto-can-not-save');

        // añadimos un producto al presupuesto
        $line = $presupuesto->getNewProductLine($producto->referencia);

        // comprobamos que se ha asignado el precio de la segunda tarifa
        $this->assertEquals(60, $line->pvpunitario, 'line-total-is-not-60');

        // eliminamos
        $this->assertTrue($presupuesto->delete(), 'presupuesto-can-not-delete');
        $this->assertTrue($producto->delete(), 'producto-can-not-delete');
        $this->assertTrue($cliente->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($cliente->delete(), 'cliente-can-not-delete');
        $this->assertTrue($grupo->delete(), 'grupo-can-not-delete');
        $this->assertTrue($tarifa->delete(), 'tarifa-can-not-delete');
        $this->assertTrue($tarifa2->delete(), 'tarifa-can-not-delete');
    }

    public function testDoNotApplyToWrongCustomer(): void
    {
        // creamos una tarifa
        $tarifa = new Tarifa();
        $tarifa->codtarifa = '01';
        $tarifa->nombre = 'Tarifa de prueba';
        $tarifa->valorx = 10;
        $this->assertTrue($tarifa->save(), 'tarifa-can-not-save');

        // creamos un cliente sin tarifa ni grupo
        $cliente = $this->getRandomCustomer();
        $cliente->codgrupo = null;
        $cliente->codtarifa = null;
        $this->assertTrue($cliente->save(), 'cliente-can-not-save');

        // creamos un producto
        $producto = $this->getRandomProduct();
        $producto->precio = 100;
        $this->assertTrue($producto->save(), 'producto-can-not-save');
        $this->assertEquals(100, $producto->precio, 'producto-precio-is-not-100');

        // hacemos un presupuesto al cliente
        $presupuesto = new PresupuestoCliente();
        $presupuesto->setSubject($cliente);
        $this->assertTrue($presupuesto->save(), 'presupuesto-can-not-save');

        // añadimos un producto al presupuesto
        $line = $presupuesto->getNewProductLine($producto->referencia);

        // comprobamos que el precio no ha sufrido alteración
        $this->assertEquals(100, $line->pvpunitario, 'line-total-is-not-100');

        // eliminamos
        $this->assertTrue($presupuesto->delete(), 'presupuesto-can-not-delete');
        $this->assertTrue($producto->delete(), 'producto-can-not-delete');
        $this->assertTrue($cliente->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($cliente->delete(), 'cliente-can-not-delete');
        $this->assertTrue($tarifa->delete(), 'tarifa-can-not-delete');
    }

    public function testDelete(): void
    {
        // creamos una tarifa
        $tarifa = new Tarifa();
        $tarifa->nombre = 'Tarifa de prueba';
        $tarifa->valorx = 10;
        $tarifa->valory = 1;
        $this->assertTrue($tarifa->save(), 'tarifa-can-not-save');

        // creamos un cliente y le asignamos la tarifa
        $cliente = $this->getRandomCustomer();
        $cliente->codgrupo = null;
        $cliente->codtarifa = $tarifa->codtarifa;
        $this->assertTrue($cliente->save(), 'cliente-can-not-save');

        // creamos un grupo y le asignamos la tarifa
        $grupo = new GrupoClientes();
        $grupo->codtarifa = $tarifa->codtarifa;
        $grupo->nombre = 'Grupo de prueba';
        $this->assertTrue($grupo->save(), 'grupo-can-not-save');

        // eliminamos la tarifa
        $this->assertTrue($tarifa->delete(), 'tarifa-can-not-delete');

        // comprobamos que el cliente sigue existiendo sin tarifa
        $this->assertTrue($cliente->exists(), 'cliente-does-not-exist');
        $this->assertTrue($cliente->load($cliente->codcliente), 'cliente-does-not-exist');
        $this->assertNull($cliente->codtarifa, 'cliente-codtarifa-is-not-null');

        // comprobamos que el grupo sigue existiendo sin tarifa
        $this->assertTrue($grupo->exists(), 'grupo-does-not-exist');
        $this->assertTrue($grupo->reload(), 'grupo-does-not-exist');
        $this->assertNull($grupo->codtarifa, 'grupo-codtarifa-is-not-null');

        // eliminamos el cliente y el grupo
        $this->assertTrue($cliente->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($cliente->delete(), 'cliente-can-not-delete');
        $this->assertTrue($grupo->delete(), 'grupo-can-not-delete');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
