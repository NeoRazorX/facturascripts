<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\Calculator;
use FacturaScripts\Core\Model\PresupuestoCliente;
use FacturaScripts\Test\Core\LogErrorsTrait;
use FacturaScripts\Test\Core\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class TarifaTest extends TestCase
{

    use LogErrorsTrait;
    use RandomDataTrait;

    public function testCreate()
    {
        // creamos la tarifa
        $rate = $this->getRandomRate();
        $this->assertTrue($rate->save(), 'rate-cant-save');
        $this->assertNotNull($rate->primaryColumnValue(), 'rate-not-stored');
        $this->assertTrue($rate->exists(), 'rate-cant-persist');

        // eliminamos
        $this->assertTrue($rate->delete(), 'rate-cant-delete');
    }

    public function testAsignedRateToCustomer()
    {
        // creamos la tarifa
        $rate = $this->getRandomRate();
        $this->assertTrue($rate->save(), 'rate-cant-save');

        // creamos el cliente
        $customer = $this->getRandomCustomer();
        $customer->codtarifa = $rate->codtarifa;
        $this->assertTrue($customer->save(), 'cant-create-customer');
        $this->assertEquals($rate->codtarifa, $customer->codtarifa, 'bad-customer-rate');

        // eliminamos
        $this->assertTrue($customer->delete(), 'customer-cant-delete');
        $this->assertTrue($rate->delete(), 'rate-cant-delete');
    }

    public function testAsignedRateToCustomerGroup()
    {
        // creamos la tarifa
        $rate = $this->getRandomRate();
        $this->assertTrue($rate->save(), 'rate-cant-save');

        // creamos el grupo de clientes
        $group = $this->getRandomCustomerGroup();
        $group->codtarifa = $rate->codtarifa;
        $this->assertTrue($group->save(), 'cant-create-customer-group-rate');
        $this->assertEquals($rate->codtarifa, $group->codtarifa, 'bad-customer-group-rate');

        // eliminamos
        $this->assertTrue($group->delete(), 'customer-group-cant-delete');
        $this->assertTrue($rate->delete(), 'rate-cant-delete');
    }

    public function testApplyCustomerFee()
    {
        // creamos la tarifa
        $rate = $this->getRandomRate();
        $this->assertTrue($rate->save(), 'rate-cant-save');

        // creamos el cliente
        $subject = $this->getRandomCustomer();
        $subject->codtarifa = $rate->codtarifa;
        $this->assertTrue($subject->save(), 'can-not-save-customer');

        // creamos el producto
        $product = $this->getRandomProduct();
        $this->assertTrue($product->save(), 'can-not-save-product');
        $variants = $product->getVariants();
        $variants[0]->precio = 100;
        $this->assertTrue($variants[0]->save(), 'can-not-save-variant');

        // creamos el presupuesto
        $doc = new PresupuestoCliente();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-presupuesto-cliente');

        // añadimos una línea
        $line = $doc->getNewProductLine($product->referencia);
        $line->cantidad = 1;
        $this->assertEquals(95, $line->pvpunitario, 'bad-presupuesto-cliente-customer-rate');

        // eliminamos
        $this->assertTrue($doc->delete(), 'presupuesto-cliente-cant-delete');
        $this->assertTrue($product->delete(), 'product-cant-delete');
        $this->assertTrue($subject->delete(), 'customer-cant-delete');
        $this->assertTrue($rate->delete(), 'rate-cant-delete');
    }

    public function testNotApplyCustomerFee()
    {
        // creamos la tarifa
        $rate = $this->getRandomRate();
        $this->assertTrue($rate->save(), 'rate-cant-save');

        // creamos el cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer');

        // creamos el producto
        $product = $this->getRandomProduct();
        $this->assertTrue($product->save(), 'can-not-save-product');
        $variants = $product->getVariants();
        $variants[0]->precio = 100;
        $this->assertTrue($variants[0]->save(), 'can-not-save-variant');

        // creamos el presupuesto
        $doc = new PresupuestoCliente();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-presupuesto-cliente');

        // añadimos una línea
        $line = $doc->getNewProductLine($product->referencia);
        $line->cantidad = 1;
        $this->assertEquals(100, $line->pvpunitario, 'bad-presupuesto-cliente-customer-rate');

        // eliminamos
        $this->assertTrue($doc->delete(), 'presupuesto-cliente-cant-delete');
        $this->assertTrue($product->delete(), 'product-cant-delete');
        $this->assertTrue($subject->delete(), 'customer-cant-delete');
        $this->assertTrue($rate->delete(), 'rate-cant-delete');
    }

    public function testApplyCustomerGroupFee()
    {
        // creamos la tarifa
        $rate = $this->getRandomRate();
        $this->assertTrue($rate->save(), 'rate-cant-save');

        // creamos el grupo de clientes
        $group = $this->getRandomCustomerGroup();
        $group->codtarifa = $rate->codtarifa;
        $this->assertTrue($group->save(), 'cant-create-customer-group-rate');

        // creamos el cliente
        $subject = $this->getRandomCustomer();
        $subject->codgrupo = $group->codgrupo;
        $this->assertTrue($subject->save(), 'can-not-save-customer');

        // creamos el producto
        $product = $this->getRandomProduct();
        $this->assertTrue($product->save(), 'can-not-save-product');
        $variants = $product->getVariants();
        $variants[0]->precio = 100;
        $this->assertTrue($variants[0]->save(), 'can-not-save-variant');

        // creamos el presupuesto
        $doc = new PresupuestoCliente();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-presupuesto-cliente');

        // añadimos una línea
        $line = $doc->getNewProductLine($product->referencia);
        $line->cantidad = 1;
        $this->assertEquals(95, $line->pvpunitario, 'bad-presupuesto-cliente-customer-group-rate');

        // eliminamos
        $this->assertTrue($doc->delete(), 'presupuesto-cliente-cant-delete');
        $this->assertTrue($product->delete(), 'product-cant-delete');
        $this->assertTrue($subject->delete(), 'customer-cant-delete');
        $this->assertTrue($group->delete(), 'customer-group-cant-delete');
        $this->assertTrue($rate->delete(), 'rate-cant-delete');
    }

    public function testNotApplyCustomerGroupFee()
    {
        // creamos la tarifa
        $rate = $this->getRandomRate();
        $this->assertTrue($rate->save(), 'rate-cant-save');

        // creamos el grupo de clientes
        $group = $this->getRandomCustomerGroup();
        $this->assertTrue($group->save(), 'cant-create-customer-group');

        // creamos el cliente
        $subject = $this->getRandomCustomer();
        $subject->codgrupo = $group->codgrupo;
        $this->assertTrue($subject->save(), 'can-not-save-customer');

        // creamos el producto
        $product = $this->getRandomProduct();
        $this->assertTrue($product->save(), 'can-not-save-product');
        $variants = $product->getVariants();
        $variants[0]->precio = 100;
        $this->assertTrue($variants[0]->save(), 'can-not-save-variant');

        // creamos el presupuesto
        $doc = new PresupuestoCliente();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-presupuesto-cliente');

        // añadimos una línea
        $line = $doc->getNewProductLine($product->referencia);
        $line->cantidad = 1;
        $this->assertEquals(100, $line->pvpunitario, 'bad-presupuesto-cliente-customer-group-rate');

        // eliminamos
        $this->assertTrue($doc->delete(), 'presupuesto-cliente-cant-delete');
        $this->assertTrue($product->delete(), 'product-cant-delete');
        $this->assertTrue($subject->delete(), 'customer-cant-delete');
        $this->assertTrue($group->delete(), 'customer-group-cant-delete');
        $this->assertTrue($rate->delete(), 'rate-cant-delete');
    }

    public function testApplyCustomerAndGroupFee()
    {
        // creamos la tarifa del cliente
        $rateCustomer = $this->getRandomRate('tCus', 'Test customer rate');
        $this->assertTrue($rateCustomer->save(), 'rate-customer-cant-save');

        // creamos la tarifa del grupo
        $rateGroup = $this->getRandomRate('tGro', 'Test group rate', 'pvp', 10);
        $this->assertTrue($rateGroup->save(), 'rate-customer-group-cant-save');

        // creamos el grupo de clientes
        $group = $this->getRandomCustomerGroup();
        $group->codtarifa = $rateGroup->codtarifa;
        $this->assertTrue($group->save(), 'cant-create-customer-group');

        // creamos el cliente
        $subject = $this->getRandomCustomer();
        $subject->codtarifa = $rateCustomer->codtarifa;
        $subject->codgrupo = $group->codgrupo;
        $this->assertTrue($subject->save(), 'can-not-save-customer');

        // creamos el producto
        $product = $this->getRandomProduct();
        $this->assertTrue($product->save(), 'can-not-save-product');
        $variants = $product->getVariants();
        $variants[0]->precio = 100;
        $this->assertTrue($variants[0]->save(), 'can-not-save-variant');

        // creamos el presupuesto
        $doc = new PresupuestoCliente();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-presupuesto-cliente');

        // añadimos una línea
        $line = $doc->getNewProductLine($product->referencia);
        $line->cantidad = 1;
        $this->assertEquals(95, $line->pvpunitario, 'bad-presupuesto-cliente-customer-group-rate');

        // eliminamos
        $this->assertTrue($doc->delete(), 'presupuesto-cliente-cant-delete');
        $this->assertTrue($product->delete(), 'product-cant-delete');
        $this->assertTrue($subject->delete(), 'customer-cant-delete');
        $this->assertTrue($group->delete(), 'customer-group-cant-delete');
        $this->assertTrue($rateCustomer->delete(), 'rate-customer-cant-delete');
        $this->assertTrue($rateGroup->delete(), 'rate-customer-group-cant-delete');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
