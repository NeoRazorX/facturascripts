<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class TarifaTest extends TestCase
{
    use RandomDataTrait;

    public function testCreate()
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

    public function testHtmlOnFields()
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

    public function testApplyToCustomer()
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

    public function testApplyToGroup()
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

    public function testApplyCustomerAndGroup()
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

    public function testDoNotApplyToWrongCustomer()
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
}
