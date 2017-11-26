<?php
/*
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Carlos Garcia Gomez  carlos@facturascripts.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Init;

use FacturaScripts\Core\Lib\RandomDataGenerator\AccountingGenerator;
use FacturaScripts\Core\Lib\RandomDataGenerator\DocumentGenerator;
use FacturaScripts\Core\Lib\RandomDataGenerator\ModelDataGenerator;
use FacturaScripts\Core\Model\Empresa;

/**
 * Used to create some default data.
 */
class InitDataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Empresa
     */
    protected $company;
    /**
     * @var AccountingGenerator
     */
    protected $accountingGenerator;

    /**
     * @var DocumentGenerator
     */
    protected $documentGenerator;

    /**
     * @var ModelDataGenerator
     */
    protected $modelDataGenerator;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->company = new Empresa();
        $this->accountingGenerator = new AccountingGenerator($this->company);
        $this->documentGenerator = new DocumentGenerator($this->company);
        $this->modelDataGenerator = new ModelDataGenerator($this->company);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {

    }

    /**
     * Create 50 manufacturers
     */
    public function testFabricantes()
    {
        $max = 50;
        $total = $this->modelDataGenerator->fabricantes($max);
        $this->assertTrue(is_int($total));
        $this->assertTrue($total == $max);
    }

    /**
     * Create 50 families
     */
    public function testFamilias()
    {
        $max = 50;
        $total = $this->modelDataGenerator->familias($max);
        $this->assertTrue(is_int($total));
        $this->assertTrue($total == $max);
    }

    /**
     * Create 50 articles
     */
    public function testArticulos()
    {
        $max = 50;
        $total = $this->modelDataGenerator->articulos($max);
        $this->assertTrue(is_int($total));
        $this->assertTrue($total == $max);
    }

    /**
     * Create 50 articles to buy from customers
     */
    public function testArticulosProveedor()
    {
        $max = 50;
        $total = $this->modelDataGenerator->articulosProveedor($max);
        $this->assertTrue(is_int($total));
        $this->assertTrue($total == $max);
    }

    /**
     * Create 50 agents
     */
    public function testAgentes()
    {
        $max = 50;
        $total = $this->modelDataGenerator->agentes($max);
        $this->assertTrue(is_int($total));
        $this->assertTrue($total == $max);
    }

    /**
     * Create 50 customers
     */
    public function testProveedores()
    {
        $max = 25;
        $total = $this->modelDataGenerator->proveedores($max);
        $this->assertTrue(is_int($total));
        $this->assertTrue($total == $max);
    }

    /**
     * Create 25 customer orders
     */
    public function testPedidosProveedor()
    {
        $max = 25;
        $total = $this->documentGenerator->pedidosProveedor($max);
        $this->assertTrue(is_int($total));
        $this->assertTrue($total == $max);
    }

    /**
     * Create 25 delivery notes from customers
     */
    public function testAlbaranesProveedor()
    {
        $max = 25;
        $total = $this->documentGenerator->albaranesProveedor($max);
        $this->assertTrue(is_int($total));
        $this->assertTrue($total == $max);
    }

    /**
     * Create 50 client groups
     */
    public function testGruposClientes()
    {
        $max = 50;
        $total = $this->modelDataGenerator->gruposClientes($max);
        $this->assertTrue(is_int($total));
        $this->assertTrue($total == $max);
    }

    public function testClientes()
    {
        $max = 50;
        $total = $this->modelDataGenerator->clientes($max);
        $this->assertTrue(is_int($total));
        $this->assertTrue($total == $max);
    }

    /**
     * Create 50 customer orders
     */
    public function testPresupuestosCliente()
    {
        $max = 25;
        $total = $this->documentGenerator->presupuestosCliente($max);
        $this->assertTrue(is_int($total));
        $this->assertTrue($total == $max);
    }

    /**
     * Create 25 client orders
     */
    public function testPedidosClientes()
    {
        $max = 25;
        $total = $this->documentGenerator->pedidosCliente($max);
        $this->assertTrue(is_int($total));
        $this->assertTrue($total == $max);
    }

    /**
     * Create 25 delivery notes for clients
     */
    public function testAlbaranesClientes()
    {
        $max = 25;
        $total = $this->documentGenerator->albaranesCliente($max);
        $this->assertTrue(is_int($total));
        $this->assertTrue($total == $max);
    }

    /**
     * Create 50 epigraph groups
     */
    public function testGruposEpigrafes()
    {
        $maxGE = 2;
        $maxE = 4;
        $maxC = 8;
        $totalGE = $this->accountingGenerator->gruposEpigrafes($maxGE);
        $this->assertTrue(is_int($totalGE));
        $this->assertTrue($totalGE == $maxGE);
        $totalE = $this->accountingGenerator->epigrafes($maxE);
        $this->assertTrue(is_int($totalE));
        $this->assertTrue($totalE == $maxE);
        $totalC = $this->accountingGenerator->cuentas($maxC);
        $this->assertTrue(is_int($totalC));
        $this->assertTrue($totalC == $maxC);
    }

    /**
     * Create 50 subaccounts
     */
    public function testSubcuentas()
    {
        $max = 50;
        $total = $this->accountingGenerator->subcuentas($max);
        $this->assertTrue(is_int($total));
        $this->assertTrue($total == $max);
    }

    /**
     * Create 50 accounting seats
     */
    public function testAsientos()
    {
        $max = 50;
        $total = $this->accountingGenerator->asientos($max);
        $this->assertTrue(is_int($total));
        $this->assertTrue($total == $max);
    }
}
