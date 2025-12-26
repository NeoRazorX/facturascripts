<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\DataSrc\Impuestos;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Model\Almacen;
use FacturaScripts\Core\Model\Empresa;
use FacturaScripts\Core\Model\PedidoCliente;
use FacturaScripts\Core\Model\Stock;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Serie;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class PedidoClienteTest extends TestCase
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
        // Creamos un pedido
        $doc = new PedidoCliente();

        // comprobamos que se asigna almacén, divisa, serie, fecha y hora por defecto
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

        // creamos un pedido y le asignamos el usuario
        $doc = new PedidoCliente();
        $this->assertTrue($doc->setAuthor($user), 'can-not-set-user');

        // comprobamos que se han asignado el usuario, agente y almacén
        $this->assertEquals($user->codagente, $doc->codagente, 'pedido-agente-bad-warehouse');
        $this->assertEquals($user->codalmacen, $doc->codalmacen, 'pedido-usuario-bad-warehouse');
        $this->assertEquals($user->nick, $doc->nick, 'pedido-usuario-bad-nick');

        // eliminamos
        $this->assertTrue($agent->getContact()->delete(), 'contacto-cant-delete');
        $this->assertTrue($agent->delete(), 'can-not-delete-agent');
        $this->assertTrue($warehouse->delete(), 'can-not-delete-warehouse');
    }

    public function testUserSerieOnCustomerSelection(): void
    {
        // creamos dos series: una para el usuario y otra para el cliente
        $serieUser = new Serie();
        $serieUser->codserie = 'US' . mt_rand(10, 99);
        $serieUser->descripcion = 'Serie Usuario';
        $this->assertTrue($serieUser->save(), 'can-not-save-user-serie');

        $serieCustomer = new Serie();
        $serieCustomer->codserie = 'CS' . mt_rand(10, 99);
        $serieCustomer->descripcion = 'Serie Cliente';
        $this->assertTrue($serieCustomer->save(), 'can-not-save-customer-serie');

        // creamos un usuario con la serie del usuario
        $user = $this->getRandomUser();
        $user->codserie = $serieUser->codserie;
        $this->assertTrue($user->save(), 'can-not-save-user');

        // creamos un cliente con su propia serie
        $customer = $this->getRandomCustomer();
        $customer->codserie = $serieCustomer->codserie;
        $this->assertTrue($customer->save(), 'can-not-save-customer');

        // creamos un pedido, asignamos autor y luego el cliente
        $doc = new PedidoCliente();
        $this->assertTrue($doc->setAuthor($user), 'can-not-set-author');
        $this->assertTrue($doc->setSubject($customer), 'can-not-set-customer');

        // debe prevalecer la serie del customer
        $this->assertEquals($customer->codserie, $doc->codserie, 'user-serie-not-applied-on-customer-selection');

        // limpieza
        $this->assertTrue($doc->delete(), 'can-not-delete-pedido');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($customer->delete(), 'can-not-delete-customer');
        $this->assertTrue($user->delete(), 'can-not-delete-user');
        $this->assertTrue($serieUser->delete(), 'can-not-delete-serie-user');
        $this->assertTrue($serieCustomer->delete(), 'can-not-delete-serie-customer');
    }

    public function testUserSerieSelection(): void
    {
        // creamos una serie para el usuario
        $serieUser = new Serie();
        $serieUser->codserie = 'US' . mt_rand(10, 99);
        $serieUser->descripcion = 'Serie Usuario';
        $this->assertTrue($serieUser->save(), 'can-not-save-user-serie');

        // creamos un usuario con la serie del usuario
        $user = $this->getRandomUser();
        $user->codserie = $serieUser->codserie;
        $this->assertTrue($user->save(), 'can-not-save-user');

        // creamos un cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'can-not-save-customer');

        // creamos un pedido, asignamos autor y luego el cliente
        $doc = new PedidoCliente();
        $this->assertTrue($doc->setAuthor($user), 'can-not-set-author');
        $this->assertTrue($doc->setSubject($customer), 'can-not-set-customer');

        // debe prevalecer la serie del customer
        $this->assertEquals($user->codserie, $doc->codserie, 'user-serie-not-applied-on-customer-selection');

        // limpieza
        $this->assertTrue($doc->delete(), 'can-not-delete-pedido');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($customer->delete(), 'can-not-delete-customer');
        $this->assertTrue($user->delete(), 'can-not-delete-user');
        $this->assertTrue($serieUser->delete(), 'can-not-delete-serie-user');
    }

    public function testCreateEmpty(): void
    {
        // creamos un cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer-1');

        // creamos un pedido y le asignamos el cliente
        $doc = new PedidoCliente();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-pedido-cliente-1');

        // comprobamos que se han asignado los datos del cliente
        $this->assertEquals($subject->cifnif, $doc->cifnif, 'pedido-cliente-bad-cifnif-1');
        $this->assertEquals($subject->codcliente, $doc->codcliente, 'pedido-cliente-bad-codcliente-1');
        $this->assertEquals($subject->idcontactoenv, $doc->idcontactoenv, 'pedido-cliente-bad-idcontactoenv-1');
        $this->assertEquals($subject->idcontactofact, $doc->idcontactofact, 'pedido-cliente-bad-idcontactofact-1');
        $this->assertEquals($subject->razonsocial, $doc->nombrecliente, 'pedido-cliente-bad-nombre-1');
        $this->assertEquals(date('d-m-Y'), $doc->fecha, 'pedido-cliente-bad-date-1');
        $this->assertEquals(0, $doc->dtopor1, 'pedido-cliente-bad-dtopor1-1');
        $this->assertEquals(0, $doc->dtopor2, 'pedido-cliente-bad-dtopor2-1');
        $this->assertEquals(0, $doc->netosindto, 'pedido-cliente-bad-netosindto-1');
        $this->assertEquals(0, $doc->neto, 'pedido-cliente-bad-neto-1');
        $this->assertEquals(0, $doc->total, 'pedido-cliente-bad-total-1');
        $this->assertEquals(0, $doc->totaliva, 'pedido-cliente-bad-totaliva-1');
        $this->assertEquals(0, $doc->totalrecargo, 'pedido-cliente-bad-totalrecargo-1');
        $this->assertEquals(0, $doc->totalirpf, 'pedido-cliente-bad-totalirpf-1');
        $this->assertEquals(0, $doc->totalsuplidos, 'pedido-cliente-bad-totalsuplidos-1');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-pedido-cliente-1');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-cliente-1');
    }

    public function testCreateWithoutSubject(): void
    {
        $doc = new PedidoCliente();
        $this->assertFalse($doc->save(), 'can-create-pedido-cliente-without-subject');
    }

    public function testCreateOneLine(): void
    {
        // creamos un cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer-2');

        // creamos un pedido
        $doc = new PedidoCliente();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-pedido-cliente-2');

        // añadimos una línea
        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        $this->assertTrue($line->save(), 'can-not-save-line-2');
        $this->assertNotEmpty($line->idlinea, 'empty-line-id-2');
        $this->assertTrue($line->exists(), 'line-not-persist-2');

        // actualizamos los totales
        $lines = $doc->getLines();
        $this->assertTrue(Calculator::calculate($doc, $lines, true), 'can-not-update-pedido-cliente-2');

        // obtenemos el impuesto predeterminado
        $default_tax = Impuestos::default();
        $total_iva = (100 * $default_tax->iva / 100);
        $total = 100 + $total_iva;

        // comprobamos
        $this->assertEquals(100, $doc->neto, 'pedido-cliente-bad-neto-2');
        $this->assertEquals($total, $doc->total, 'pedido-cliente-bad-total-2');
        $this->assertEquals($total_iva, $doc->totaliva, 'pedido-cliente-bad-totaliva-2');
        $this->assertEquals(0, $doc->totalrecargo, 'pedido-cliente-bad-totalrecargo-2');
        $this->assertEquals(0, $doc->totalirpf, 'pedido-cliente-bad-totalirpf-2');
        $this->assertEquals(0, $doc->totalsuplidos, 'pedido-cliente-bad-totalsuplidos-2');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-pedido-cliente-2');
        $this->assertFalse($line->exists(), 'linea-pedido-cliente-still-exists');
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
        $this->assertTrue($product->save(), 'can-not-save-product-3');

        // modificamos el precio y coste del producto
        foreach ($product->getVariants() as $variant) {
            $variant->precio = 10;
            $variant->coste = 5;
            $this->assertTrue($variant->save(), 'can-not-save-variant-3');
        }

        // creamos un pedido
        $doc = new PedidoCliente();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-pedido-cliente-2');

        // añadimos el producto sin stock
        $line = $doc->getNewProductLine($product->referencia);

        // comprobamos que precio y coste se han asignado correctamente
        $this->assertEquals(10, $line->pvpunitario, 'pedido-cliente-bad-pvpunitario-3');
        $this->assertEquals(5, $line->coste, 'pedido-cliente-bad-coste-3');

        // guardamos la línea
        $this->assertTrue($line->save(), 'can-not-add-product-without-stock');

        // recargamos y comprobamos el stock
        $stock = new Stock();
        $where = [new DataBaseWhere('idproducto', $product->idproducto)];
        $stock->loadWhere($where);
        $this->assertEquals(1, $stock->reservada, 'pedido-cliente-do-not-update-stock');
        $this->assertEquals(0, $stock->disponible, 'pedido-cliente-do-not-update-stock');
        $this->assertEquals(0, $stock->cantidad, 'pedido-cliente-do-not-update-stock');

        // actualizamos los totales
        $lines = $doc->getLines();
        $this->assertTrue(Calculator::calculate($doc, $lines, true), 'can-not-update-pedido-cliente-3');

        // obtenemos el impuesto predeterminado
        $default_tax = Impuestos::default();
        $total_iva = (10 * $default_tax->iva / 100);
        $total = 10 + $total_iva;

        // comprobamos
        $this->assertEquals(10, $doc->neto, 'pedido-cliente-bad-neto-3');
        $this->assertEquals($total, $doc->total, 'pedido-cliente-bad-total-3');
        $this->assertEquals($total_iva, $doc->totaliva, 'pedido-cliente-bad-totaliva-3');
        $this->assertEquals(5, $doc->totalcoste, 'pedido-cliente-bad-totalcoste-3');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-pedido-cliente-3');
        $this->assertFalse($line->exists(), 'linea-pedido-cliente-still-exists-3');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-cliente-3');

        // recargamos y comprobamos el stock
        $stock->loadWhere($where);
        $this->assertEquals(0, $stock->reservada, 'pedido-cliente-do-not-update-stock');
        $this->assertEquals(0, $stock->disponible, 'pedido-cliente-do-not-update-stock');
        $this->assertEquals(0, $stock->cantidad, 'pedido-cliente-do-not-update-stock');

        // eliminamos el producto
        $this->assertTrue($product->delete(), 'can-not-delete-product-3');
    }

    public function testPropertiesLength(): void
    {
        // Definir los campos a validar: campo => [longitud_máxima, longitud_invalida]
        $campos = [
            'apartado' => [10, 11],
            'cifnif' => [30, 31],
            'ciudad' => [100, 101],
            'codigo' => [20, 21],
            'codigoenv' => [200, 201],
            'codpais' => [20, 21],
            'codpostal' => [10, 11],
            'direccion' => [200, 201],
            'nombrecliente' => [100, 101],
            'operacion' => [20, 21],
            'provincia' => [100, 101],
        ];

        // creamos un cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer');

        foreach ($campos as $campo => [$valido, $invalido]) {
            // Creamos un nuevo almacén
            $doc = new PedidoCliente();

            // campo obligatorio (not null)
            $doc->setSubject($subject);

            // Asignamos el valor inválido en el campo a probar
            $doc->{$campo} = Tools::randomString($invalido);
            $this->assertFalse($doc->save(), "can-save-pedidoCliente-bad-{$campo}");

            // Corregimos el campo y comprobamos que ahora sí se puede guardar
            $doc->{$campo} = Tools::randomString($valido);
            $this->assertTrue($doc->save(), "cannot-save-pedidoCliente-fixed-{$campo}");

            // Limpiar
            $this->assertTrue($doc->delete(), "cannot-delete-pedidoCliente-{$campo}");
        }

        // eliminamos
        $this->assertTrue($subject->getDefaultAddress()->delete());
        $this->assertTrue($subject->delete());
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
        $warehouse->loadWhere($where);

        // creamos un cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer-2');

        // creamos un pedido y le asignamos el cliente y el almacén
        $doc = new PedidoCliente();
        $doc->setSubject($subject);
        $doc->codalmacen = $warehouse->codalmacen;
        $this->assertTrue($doc->save(), 'pedido-cant-save');

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

            // al cambiar el estado genera un nuevo albarán
            $doc->idestado = $status->idestado;
            $this->assertTrue($doc->save(), 'pedido-cant-save');

            // comprobamos que el albarán se ha creado
            $children = $doc->childrenDocuments();
            $this->assertNotEmpty($children, 'albaranes-no-creados');
            foreach ($children as $child) {
                // comprobamos que se han asignado el mismo almacén y empresa
                $this->assertEquals($warehouse->codalmacen, $child->codalmacen, 'albaran-bad-codalmacen');
                $this->assertEquals($company2->idempresa, $child->idempresa, 'albaran-bad-idempresa');
            }
        }

        // eliminamos
        $children = $doc->childrenDocuments();
        $this->assertNotEmpty($children, 'albaranes-no-creados');
        foreach ($children as $child) {
            $this->assertTrue($child->delete(), 'albarán-cant-delete');
        }
        $this->assertTrue($doc->delete(), 'pedido-cant-delete');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'cliente-cant-delete');
        $this->assertTrue($company2->delete(), 'empresa-cant-delete');
    }

    protected function setUp(): void
    {
        $this->logErrors();
    }
}
