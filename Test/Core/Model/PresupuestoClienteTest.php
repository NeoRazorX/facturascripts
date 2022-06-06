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
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Almacen;
use FacturaScripts\Core\Model\Empresa;
use FacturaScripts\Core\Model\PresupuestoCliente;
use FacturaScripts\Test\Core\DefaultSettingsTrait;
use FacturaScripts\Test\Core\LogErrorsTrait;
use FacturaScripts\Test\Core\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class PresupuestoClienteTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
    }

    public function testDefaultValues()
    {
        $doc = new PresupuestoCliente();
        $this->assertNotEmpty($doc->codalmacen, 'empty-warehouse');
        $this->assertNotEmpty($doc->coddivisa, 'empty-currency');
        $this->assertNotEmpty($doc->codserie, 'empty-serie');
        $this->assertNotEmpty($doc->fecha, 'empty-date');
        $this->assertNotEmpty($doc->hora, 'empty-time');
    }

    public function testSetAuthor()
    {
        // creamos un agente
        $agent = $this->getRandomAgent();
        $this->assertTrue($agent->save(), 'can-not-create-agent');

        // creamos un almacén
        $warehouse = $this->getRandomWarehouse();
        $this->assertTrue($warehouse->save(), 'can-not-create-warehouse');

        // creamos un usuario
        $user = $this->getRandomUser();
        $user->codagente = $agent->codagente;
        $user->codalmacen = $warehouse->codalmacen;

        // asignamos el usuario
        $doc = new PresupuestoCliente();
        $this->assertTrue($doc->setAuthor($user), 'can-not-set-user');
        $this->assertEquals($user->codagente, $doc->codagente, 'presupuesto-usuario-bad-agent');
        $this->assertEquals($user->codalmacen, $doc->codalmacen, 'presupuesto-usuario-bad-warehouse');
        $this->assertEquals($user->nick, $doc->nick, 'presupuesto-usuario-bad-nick');

        // eliminamos
        $this->assertTrue($agent->delete(), 'can-not-delete-agent');
        $this->assertTrue($warehouse->delete(), 'can-not-delete-warehouse');
    }

    public function testCreateEmpty()
    {
        // creamos el cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer-1');

        // creamos el presupuesto
        $doc = new PresupuestoCliente();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-presupuesto-cliente-1');

        // comprobamos valores
        $this->assertEquals($subject->cifnif, $doc->cifnif, 'presupuesto-cliente-bad-cifnif-1');
        $this->assertEquals($subject->codcliente, $doc->codcliente, 'presupuesto-cliente-bad-codcliente-1');
        $this->assertEquals($subject->idcontactoenv, $doc->idcontactoenv, 'presupuesto-cliente-bad-idcontactoenv-1');
        $this->assertEquals($subject->idcontactofact, $doc->idcontactofact, 'presupuesto-cliente-bad-idcontactofact-1');
        $this->assertEquals($subject->razonsocial, $doc->nombrecliente, 'presupuesto-cliente-bad-nombre-1');
        $this->assertEquals(date('d-m-Y'), $doc->fecha, 'presupuesto-cliente-bad-date-1');
        $this->assertEquals(0, $doc->dtopor1, 'presupuesto-cliente-bad-dtopor1-1');
        $this->assertEquals(0, $doc->dtopor2, 'presupuesto-cliente-bad-dtopor2-1');
        $this->assertEquals(0, $doc->netosindto, 'presupuesto-cliente-bad-netosindto-1');
        $this->assertEquals(0, $doc->neto, 'presupuesto-cliente-bad-neto-1');
        $this->assertEquals(0, $doc->total, 'presupuesto-cliente-bad-total-1');
        $this->assertEquals(0, $doc->totaliva, 'presupuesto-cliente-bad-totaliva-1');
        $this->assertEquals(0, $doc->totalrecargo, 'presupuesto-cliente-bad-totalrecargo-1');
        $this->assertEquals(0, $doc->totalirpf, 'presupuesto-cliente-bad-totalirpf-1');
        $this->assertEquals(0, $doc->totalsuplidos, 'presupuesto-cliente-bad-totalsuplidos-1');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-presupuesto-cliente-1');
        $this->assertTrue($subject->delete(), 'can-not-delete-cliente-1');
    }

    public function testCreateWithoutSubject()
    {
        $doc = new PresupuestoCliente();
        $this->assertTrue($doc->save(), 'can-not-create-presupuesto-cliente-without-subject');
    }

    public function testCreateOneLine()
    {
        // creamos el cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer-2');

        // creamos el presupuesto
        $doc = new PresupuestoCliente();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-presupuesto-cliente-2');

        // añadimos una línea
        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        $this->assertTrue($line->save(), 'can-not-save-line-2');
        $this->assertNotEmpty($line->idlinea, 'empty-line-id-2');
        $this->assertTrue($line->exists(), 'line-not-persist-2');

        // actualizamos los totales
        $lines = $doc->getLines();
        Calculator::calculate($doc, $lines, true);

        // comprobamos
        $this->assertEquals(100, $doc->neto, 'presupuesto-cliente-bad-neto-2');
        $this->assertEquals(121, $doc->total, 'presupuesto-cliente-bad-total-2');
        $this->assertEquals(21, $doc->totaliva, 'presupuesto-cliente-bad-totaliva-2');
        $this->assertEquals(0, $doc->totalrecargo, 'presupuesto-cliente-bad-totalrecargo-2');
        $this->assertEquals(0, $doc->totalirpf, 'presupuesto-cliente-bad-totalirpf-2');
        $this->assertEquals(0, $doc->totalsuplidos, 'presupuesto-cliente-bad-totalsuplidos-2');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-presupuesto-cliente-2');
        $this->assertFalse($line->exists(), 'linea-presupuesto-cliente-still-exists');
        $this->assertTrue($subject->delete(), 'can-not-delete-cliente-2');
    }

    public function testCreateProductLine()
    {
        // creamos el cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer-2');

        // creamos el producto
        $product = $this->getRandomProduct();
        $this->assertTrue($product->save(), 'can-not-save-supplier-3');

        // creamos el presupuesto
        $doc = new PresupuestoCliente();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-presupuesto-cliente-2');

        // añadimos el producto sin stock
        $line = $doc->getNewProductLine($product->referencia);
        $line->pvpunitario = 10;
        $this->assertTrue($line->save(), 'can-not-add-product-without-stock');

        // actualizamos los totales
        $lines = $doc->getLines();
        Calculator::calculate($doc, $lines, true);

        // comprobamos
        $this->assertEquals(10, $doc->neto, 'presupuesto-cliente-bad-neto-3');
        $this->assertEquals(12.1, $doc->total, 'presupuesto-cliente-bad-total-3');
        $this->assertEquals(2.1, $doc->totaliva, 'presupuesto-cliente-bad-totaliva-3');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-presupuesto-cliente-3');
        $this->assertFalse($line->exists(), 'linea-presupuesto-cliente-still-exists-3');
        $this->assertTrue($subject->delete(), 'can-not-delete-cliente-3');
        $this->assertTrue($product->delete(), 'can-not-delete-product-3');
    }

    public function testCreateProductNotFoundLine()
    {
        // creamos el cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer-2');

        // creamos un producto
        $product = $this->getRandomProduct();
        $this->assertTrue($product->save(), 'can-not-save-supplier-3');

        // eliminamos el producto para asegurarnos de que no existe
        $this->assertTrue($product->delete(), 'can-not-delete-product-3');

        // creamos el presupuesto
        $doc = new PresupuestoCliente();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-presupuesto-cliente-2');

        // añadimos el producto que ya no existe
        $line = $doc->getNewProductLine($product->referencia);
        $line->pvpunitario = 10;
        $this->assertTrue($line->save(), 'can-not-add-product-without-stock');

        // la línea debe tener la referencia incluso aunque el producto no exista
        $this->assertEquals($product->referencia, $line->referencia, 'linea-presupuesto-cliente-bad-referencia-3');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-presupuesto-cliente-3');
        $this->assertFalse($line->exists(), 'linea-presupuesto-cliente-still-exists-3');
        $this->assertTrue($subject->delete(), 'can-not-delete-cliente-3');
    }

    public function testSecondCompany()
    {
        // creamos la empresa 2
        $company2 = new Empresa();
        $company2->nombre = 'Company 2';
        $company2->nombrecorto = 'Company-2';
        $this->assertTrue($company2->save(), 'company-cant-save');

        // obtenemos el almacén de la empresa 2
        $warehouse = new Almacen();
        $where = [new DataBaseWhere('idempresa', $company2->idempresa)];
        $warehouse->loadFromCode('', $where);

        // creamos el cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer-2');

        // creamos el presupuesto
        $doc = new PresupuestoCliente();
        $doc->setSubject($subject);
        $doc->codalmacen = $warehouse->codalmacen;
        $this->assertTrue($doc->save(), 'presupuesto-cant-save');

        // añadimos una línea
        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        $this->assertTrue($line->save(), 'can-not-save-line-2');

        // aprobamos
        foreach ($doc->getAvailableStatus() as $status) {
            if (empty($status->generadoc)) {
                continue;
            }

            // al cambiar el estado genera un nuevo pedido
            $doc->idestado = $status->idestado;
            $this->assertTrue($doc->save(), 'pedido-cant-save');

            $children = $doc->childrenDocuments();
            $this->assertNotEmpty($children, 'pedidos-no-creados');
            foreach ($children as $child) {
                $this->assertEquals($company2->idempresa, $child->idempresa, 'pedido-bad-idempresa');
            }
        }

        // eliminamos
        $children = $doc->childrenDocuments();
        $this->assertNotEmpty($children, 'pedidos-no-creados');
        foreach ($children as $child) {
            $this->assertTrue($child->delete(), 'pedido-cant-delete');
        }
        $this->assertTrue($doc->delete(), 'presupuesto-cant-delete');
        $this->assertTrue($subject->delete(), 'cliente-cant-delete');
        $this->assertTrue($company2->delete(), 'empresa-cant-delete');
    }

    protected function setUp(): void
    {
        $this->logErrors();
    }
}
