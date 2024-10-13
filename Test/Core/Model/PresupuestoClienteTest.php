<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Lib\BusinessDocumentGenerator;
use FacturaScripts\Core\Model\Almacen;
use FacturaScripts\Core\Model\Empresa;
use FacturaScripts\Core\Model\PresupuestoCliente;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
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

    public function testDefaultValues(): void
    {
        // creamos un presupuesto
        $doc = new PresupuestoCliente();

        // comprobamos que se asignan almacén, divisa, serie, fecha y hora predeterminada
        $this->assertNotEmpty($doc->codalmacen, 'empty-warehouse');
        $this->assertNotEmpty($doc->coddivisa, 'empty-currency');
        $this->assertNotEmpty($doc->codserie, 'empty-serie');
        $this->assertNotEmpty($doc->fecha, 'empty-date');
        $this->assertNotEmpty($doc->hora, 'empty-time');
    }

    public function testSetAuthor(): void
    {
        // creamos un agente
        $agent = $this->getRandomAgent();
        $this->assertTrue($agent->save(), 'can-not-create-agent');

        // creamos un almacén
        $warehouse = $this->getRandomWarehouse();
        $this->assertTrue($warehouse->save(), 'can-not-create-warehouse');

        // creamos un usuario y le asignamos el agente y el almacén
        $user = $this->getRandomUser();
        $user->codagente = $agent->codagente;
        $user->codalmacen = $warehouse->codalmacen;

        // creamos un presupuesto y le asignamos el usuario
        $doc = new PresupuestoCliente();
        $this->assertTrue($doc->setAuthor($user), 'can-not-set-user');

        // comprobamos que se le han asignado el agente, almacén y usuario
        $this->assertEquals($user->codagente, $doc->codagente, 'presupuesto-usuario-bad-agent');
        $this->assertEquals($user->codalmacen, $doc->codalmacen, 'presupuesto-usuario-bad-warehouse');
        $this->assertEquals($user->nick, $doc->nick, 'presupuesto-usuario-bad-nick');

        // eliminamos
        $this->assertTrue($agent->getContact()->delete(), 'contacto-cant-delete');
        $this->assertTrue($agent->delete(), 'can-not-delete-agent');
        $this->assertTrue($warehouse->delete(), 'can-not-delete-warehouse');
    }

    public function testCreateEmpty(): void
    {
        // creamos un cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer-1');

        // creamos un presupuesto y le asignamos el cliente
        $doc = new PresupuestoCliente();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-presupuesto-cliente-1');

        // comprobamos que se le han asignado los datos del cliente
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
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-cliente-1');
    }

    public function testCreateWithoutSubject(): void
    {
        $doc = new PresupuestoCliente();
        $this->assertTrue($doc->save(), 'can-not-create-presupuesto-cliente-without-subject');
        $this->assertTrue($doc->delete(), 'can-not-delete-presupuesto-cliente');
    }

    public function testCreateOneLine(): void
    {
        // creamos un cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer-2');

        // creamos un presupuesto y le asignamos el cliente
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
        $this->assertTrue(Calculator::calculate($doc, $lines, true), 'can-not-update-presupuesto-cliente-2');

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
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-cliente-2');
    }

    public function testCreateProductLine(): void
    {
        // creamos un cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer-2');

        // creamos un producto
        $product = $this->getRandomProduct();
        $this->assertTrue($product->save(), 'can-not-save-supplier-3');

        // modificamos el precio y coste del producto
        foreach ($product->getVariants() as $variant) {
            $variant->precio = 10;
            $variant->coste = 5;
            $this->assertTrue($variant->save(), 'can-not-save-variant-3');
        }

        // creamos un presupuesto y le asignamos el cliente
        $doc = new PresupuestoCliente();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-presupuesto-cliente-2');

        // añadimos el producto sin stock
        $line = $doc->getNewProductLine($product->referencia);

        // comprobamos que precio y coste se han asignado correctamente
        $this->assertEquals(10, $line->pvpunitario, 'presupuesto-cliente-bad-pvpunitario-3');
        $this->assertEquals(5, $line->coste, 'presupuesto-cliente-bad-coste-3');

        // guardamos la línea
        $this->assertTrue($line->save(), 'can-not-add-product-without-stock');

        // actualizamos los totales
        $lines = $doc->getLines();
        $this->assertTrue(Calculator::calculate($doc, $lines, true), 'can-not-update-presupuesto-cliente-3');

        // comprobamos
        $this->assertEquals(10, $doc->neto, 'presupuesto-cliente-bad-neto-3');
        $this->assertEquals(12.1, $doc->total, 'presupuesto-cliente-bad-total-3');
        $this->assertEquals(2.1, $doc->totaliva, 'presupuesto-cliente-bad-totaliva-3');
        $this->assertEquals(5, $doc->totalcoste, 'presupuesto-cliente-bad-totalcoste-3');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-presupuesto-cliente-3');
        $this->assertFalse($line->exists(), 'linea-presupuesto-cliente-still-exists-3');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-cliente-3');
        $this->assertTrue($product->delete(), 'can-not-delete-product-3');
    }

    public function testCreateProductNotFoundLine(): void
    {
        // creamos un cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer-2');

        // creamos un producto
        $product = $this->getRandomProduct();
        $this->assertTrue($product->save(), 'can-not-save-supplier-3');

        // eliminamos el producto para asegurarnos de que no existe
        $this->assertTrue($product->delete(), 'can-not-delete-product-3');

        // creamos un presupuesto y le asignamos el cliente
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
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-cliente-3');
    }

    public function testSecondCompany(): void
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

        // creamos un cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer-2');

        // creamos un presupuesto y le asignamos el cliente y el almacén
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

            // comprobamos que el pedido se ha creado
            $children = $doc->childrenDocuments();
            $this->assertNotEmpty($children, 'pedidos-no-creados');
            foreach ($children as $child) {
                // comprobamos que el pedido se ha creado en la empresa 2
                $this->assertEquals($company2->idempresa, $child->idempresa, 'pedido-bad-idempresa');
                $this->assertEquals($warehouse->codalmacen, $child->codalmacen, 'pedido-bad-idempresa');
            }
        }

        // eliminamos
        $children = $doc->childrenDocuments();
        $this->assertNotEmpty($children, 'pedidos-no-creados');
        foreach ($children as $child) {
            $this->assertTrue($child->delete(), 'pedido-cant-delete');
        }
        $this->assertTrue($doc->delete(), 'presupuesto-cant-delete');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'cliente-cant-delete');
        $this->assertTrue($company2->delete(), 'empresa-cant-delete');
    }

    public function testChangeExercise(): void
    {
        // creamos un cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer-2');

        // creamos un presupuesto y le asignamos el cliente
        $doc = new PresupuestoCliente();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-presupuesto-cliente-2');

        // añadimos una línea
        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        $this->assertTrue($line->save(), 'can-not-save-line-2');

        // actualizamos los totales
        $lines = $doc->getLines();
        $this->assertTrue(Calculator::calculate($doc, $lines, true), 'can-not-calculate-total');

        // ponemos fecha de hace 2 años
        $doc->fecha = Tools::date('-2 years');
        $this->assertTrue($doc->save(), 'can-not-save-presupuesto-cliente-2');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-presupuesto-cliente-2');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'cliente-cant-delete');
    }

    public function testSplitDocument(): void
    {
        // creamos un cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer-2');

        // creamos un presupuesto y le asignamos el cliente
        $doc = new PresupuestoCliente();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-presupuesto-cliente-2');

        // añadimos una línea
        $line1 = $doc->getNewLine();
        $line1->cantidad = 1;
        $line1->descripcion = 'Linea de prueba';
        $line1->pvpunitario = 100;
        $this->assertTrue($line1->save(), 'can-not-save-line-2');

        // añadimos otra línea
        $line2 = $doc->getNewLine();
        $line2->cantidad = 2;
        $line2->descripcion = 'Linea de prueba 2';
        $line2->pvpunitario = 50;
        $this->assertTrue($line2->save(), 'can-not-save-line-2');

        // actualizamos los totales
        $lines = $doc->getLines();
        $this->assertTrue(Calculator::calculate($doc, $lines, true), 'can-not-calculate-total');

        // ahora vamos a partir el presupuesto y generar un pedido
        $generator = new BusinessDocumentGenerator();
        $this->assertTrue($generator->generate($doc, 'PedidoCliente', [$line2], [$line2->idlinea => 1]), 'can-not-generate-document');

        // ahora comprobamos que el pedido se ha creado
        $pedidos = $generator->getLastDocs();
        $this->assertCount(1, $pedidos, 'pedido-no-creado');

        // comprobamos los totales
        $this->assertEquals(50, $pedidos[0]->neto, 'pedido-bad-neto');
        $this->assertEquals(60.5, $pedidos[0]->total, 'pedido-bad-total');

        // eliminamos
        $this->assertTrue($pedidos[0]->delete(), 'pedido-cant-delete');
        $this->assertTrue($doc->delete(), 'presupuesto-cant-delete');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'cliente-cant-delete');
    }

    protected function setUp(): void
    {
        $this->logErrors();
    }
}
