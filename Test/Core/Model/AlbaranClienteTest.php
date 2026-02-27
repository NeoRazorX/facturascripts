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
use FacturaScripts\Core\Model\AlbaranCliente;
use FacturaScripts\Core\Model\Almacen;
use FacturaScripts\Core\Model\Empresa;
use FacturaScripts\Core\Model\Stock;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class AlbaranClienteTest extends TestCase
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
        // creamos un albarán
        $doc = new AlbaranCliente();

        // comprobamos que tiene almacén, divisa, serie, fecha y hora por defecto
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
        $user->codalmacen = $warehouse->codalmacen;
        $user->codagente = $agent->codagente;

        // creamos un albarán y le asignamos el usuario
        $doc = new AlbaranCliente();
        $this->assertTrue($doc->setAuthor($user), 'can-not-set-user');

        // comprobamos que se han asignado usuario, almacén y agente
        $this->assertEquals($user->codagente, $doc->codagente, 'albaran-usuario-bad-agent');
        $this->assertEquals($user->codalmacen, $doc->codalmacen, 'albaran-usuario-bad-warehouse');
        $this->assertEquals($user->nick, $doc->nick, 'albaran-usuario-bad-nick');

        // eliminamos
        $this->assertTrue($warehouse->delete(), 'can-not-delete-warehouse');
        $this->assertTrue($agent->getContact()->delete(), 'contacto-cant-delete');
        $this->assertTrue($agent->delete(), 'can-not-delete-agent');
    }

    public function testCreateEmpty(): void
    {
        // creamos un cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer-1');

        // creamos un albarán y le asignamos el cliente
        $doc = new AlbaranCliente();
        $this->assertTrue($doc->setSubject($subject), 'can-not-set-subject-1');
        $this->assertTrue($doc->save(), 'can-not-create-albaran-cliente-1');

        // comprobamos que se le han asignado los datos del cliente
        $this->assertEquals($subject->cifnif, $doc->cifnif, 'albaran-cliente-bad-cifnif-1');
        $this->assertEquals($subject->codcliente, $doc->codcliente, 'albaran-cliente-bad-codcliente-1');
        $this->assertEquals($subject->idcontactoenv, $doc->idcontactoenv, 'albaran-cliente-bad-idcontactoenv-1');
        $this->assertEquals($subject->idcontactofact, $doc->idcontactofact, 'albaran-cliente-bad-idcontactofact-1');
        $this->assertEquals($subject->razonsocial, $doc->nombrecliente, 'albaran-cliente-bad-nombre-1');
        $this->assertEquals(date('d-m-Y'), $doc->fecha, 'albaran-cliente-bad-date-1');
        $this->assertEquals(0, $doc->dtopor1, 'albaran-cliente-bad-dtopor1-1');
        $this->assertEquals(0, $doc->dtopor2, 'albaran-cliente-bad-dtopor2-1');
        $this->assertEquals(0, $doc->netosindto, 'albaran-cliente-bad-netosindto-1');
        $this->assertEquals(0, $doc->neto, 'albaran-cliente-bad-neto-1');
        $this->assertEquals(0, $doc->total, 'albaran-cliente-bad-total-1');
        $this->assertEquals(0, $doc->totaliva, 'albaran-cliente-bad-totaliva-1');
        $this->assertEquals(0, $doc->totalrecargo, 'albaran-cliente-bad-totalrecargo-1');
        $this->assertEquals(0, $doc->totalirpf, 'albaran-cliente-bad-totalirpf-1');
        $this->assertEquals(0, $doc->totalsuplidos, 'albaran-cliente-bad-totalsuplidos-1');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-albaran-cliente-1');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-cliente-1');
    }

    public function testCreateWithoutSubject(): void
    {
        $doc = new AlbaranCliente();
        $this->assertFalse($doc->save(), 'can-create-albaran-cliente-without-subject');
    }

    public function testCreateOneLine(): void
    {
        // creamos un cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer-2');

        // creamos un albarán
        $doc = new AlbaranCliente();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-albaran-cliente-2');

        // añadimos una línea
        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        $this->assertTrue($line->save(), 'can-not-save-line-2');
        $this->assertNotEmpty($line->idlinea, 'empty-line-id-2');
        $this->assertTrue($line->exists(), 'line-not-persist-2');

        // actualizamos los totales
        $lines = $doc->getLines();
        $this->assertTrue(Calculator::calculate($doc, $lines, true), 'can-not-update-albaran-cliente-2');

        // obtenemos el impuesto predeterminado
        $default_tax = Impuestos::default();
        $total_iva = (100 * $default_tax->iva / 100);
        $total = 100 + $total_iva;

        // comprobamos
        $this->assertEquals(100, $doc->neto, 'albaran-cliente-bad-neto-2');
        $this->assertEquals($total, $doc->total, 'albaran-cliente-bad-total-2');
        $this->assertEquals($total_iva, $doc->totaliva, 'albaran-cliente-bad-totaliva-2');
        $this->assertEquals(0, $doc->totalrecargo, 'albaran-cliente-bad-totalrecargo-2');
        $this->assertEquals(0, $doc->totalirpf, 'albaran-cliente-bad-totalirpf-2');
        $this->assertEquals(0, $doc->totalsuplidos, 'albaran-cliente-bad-totalsuplidos-2');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-albaran-cliente-2');
        $this->assertFalse($line->exists(), 'linea-albaran-cliente-still-exists');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-cliente-2');
    }

    public function testCreatePriceWithTax(): void
    {
        // si el país no es España, saltamos el test
        if (Tools::config('codpais') !== 'ESP') {
            $this->markTestSkipped('country-is-not-spain');
        }

        // creamos un cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer-2');

        // creamos un albarán
        $doc = new AlbaranCliente();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-albaran-cliente-2');

        // añadimos una línea con precio con IVA
        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->codimpuesto = 'IVA21';
        $line->setPriceWithTax(121);
        $this->assertTrue($line->save(), 'can-not-save-line-2');

        // actualizamos los totales
        $lines = $doc->getLines();
        $this->assertTrue(Calculator::calculate($doc, $lines, true), 'can-not-update-albaran-cliente-2');

        // comprobamos
        $this->assertEquals(100, $doc->neto, 'albaran-cliente-bad-neto-2');
        $this->assertEquals(121, $doc->total, 'albaran-cliente-bad-total-2');
        $this->assertEquals(21, $doc->totaliva, 'albaran-cliente-bad-totaliva-2');
        $this->assertEquals(0, $doc->totalrecargo, 'albaran-cliente-bad-totalrecargo-2');
        $this->assertEquals(0, $doc->totalirpf, 'albaran-cliente-bad-totalirpf-2');
        $this->assertEquals(0, $doc->totalsuplidos, 'albaran-cliente-bad-totalsuplidos-2');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-albaran-cliente-2');
        $this->assertFalse($line->exists(), 'linea-albaran-cliente-still-exists-2');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-cliente-2');
    }

    public function testCreateProductLine(): void
    {
        // creamos un cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer-2');

        // creamos un producto sin stock
        $product = $this->getRandomProduct();
        $product->ventasinstock = false;
        $this->assertTrue($product->save(), 'can-not-save-product-3');

        // modificamos el precio y coste del producto
        foreach ($product->getVariants() as $variant) {
            $variant->precio = 10;
            $variant->coste = 5;
            $this->assertTrue($variant->save(), 'can-not-save-variant-3');
        }

        // creamos un albarán
        $doc = new AlbaranCliente();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-albaran-cliente-2');

        // añadimos el producto sin stock
        $line = $doc->getNewProductLine($product->referencia);

        // comprobamos que precio y coste se han asignado correctamente
        $this->assertEquals(10, $line->pvpunitario, 'albaran-cliente-bad-pvpunitario-3');
        $this->assertEquals(5, $line->coste, 'albaran-cliente-bad-coste-3');
        $this->assertEquals(-1, $line->actualizastock, 'albaran-cliente-bad-actualizastock-3');
        $this->assertEquals(0, $line->servido, 'albaran-cliente-bad-servido-3');
        $this->assertEquals($product->referencia, $line->referencia, 'albaran-cliente-bad-referencia-3');

        // guardamos la línea
        $this->assertFalse($line->save(), 'can-add-product-without-stock');

        // añadimos stock
        $stock = new Stock();
        $stock->cantidad = 2;
        $stock->idproducto = $product->idproducto;
        $stock->referencia = $product->referencia;
        $stock->save();

        // ahora si debe añadir el producto al albarán
        $this->assertTrue($line->save(), 'can-not-save-line-3');

        // recargamos producto y comprobamos el stock
        $product->reload();
        $stock->reload();
        $this->assertEquals(1, $stock->cantidad, 'albaran-cliente-do-not-update-stock');
        $this->assertEquals(1, $stock->disponible, 'albaran-cliente-do-not-update-stock');
        $this->assertEquals(1, $product->stockfis, 'albaran-cliente-product-do-not-update-stock');

        // actualizamos los totales
        $lines = $doc->getLines();
        $this->assertTrue(Calculator::calculate($doc, $lines, true), 'can-not-update-albaran-cliente-3');

        // obtenemos el impuesto predeterminado
        $default_tax = Impuestos::default();
        $total_iva = (10 * $default_tax->iva / 100);
        $total = 10 + $total_iva;

        // comprobamos
        $this->assertEquals(10, $doc->neto, 'albaran-cliente-bad-neto-3');
        $this->assertEquals($total, $doc->total, 'albaran-cliente-bad-total-3');
        $this->assertEquals($total_iva, $doc->totaliva, 'albaran-cliente-bad-totaliva-3');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-albaran-cliente-3');
        $this->assertFalse($line->exists(), 'linea-albaran-cliente-still-exists-3');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-cliente-3');

        // recargamos producto y comprobamos el stock
        $product->reload();
        $stock->reload();
        $this->assertEquals(2, $stock->cantidad, 'albaran-cliente-do-not-update-stock');
        $this->assertEquals(2, $stock->disponible, 'albaran-cliente-do-not-update-stock');
        $this->assertEquals(2, $product->stockfis, 'albaran-cliente-product-do-not-update-stock');

        // eliminamos el producto
        $this->assertTrue($product->delete(), 'can-not-delete-product-3');
    }

    public function testPropertiesLength(): void
    {
        // creamos un cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer-1');

        // Definir los campos a validar: campo => [longitud_máxima, longitud_invalida]
        $campos = [
            'apartado' => [10, 11],
            'cifnif' => [30, 31],
            'ciudad' => [100, 101],
            'codpais' => [20, 21],
            'codpostal' => [10, 11],
            'direccion' => [200, 201],
            'nombrecliente' => [100, 101],
            'provincia' => [100, 101],
        ];

        foreach ($campos as $campo => [$valido, $invalido]) {
            // Creamos un nuevo albarán
            $doc = new AlbaranCliente();
            $doc->setSubject($subject);

            // Asignamos el valor inválido en el campo a probar
            $doc->{$campo} = Tools::randomString($invalido);
            $this->assertFalse($doc->save(), "can-save-albaranCliente-bad-{$campo}");

            // Corregimos el campo y comprobamos que ahora sí se puede guardar
            $doc->{$campo} = Tools::randomString($valido);
            $this->assertTrue($doc->save(), "cannot-save-albaranCliente-fixed-{$campo}");

            // Limpiar
            $this->assertTrue($doc->delete(), "cannot-delete-albaranCliente-{$campo}");
        }

        // Eliminamos el cliente
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'can-not-delete-contact');
        $this->assertTrue($subject->delete(), 'can-not-delete-customer');
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

        // creamos un albarán
        $doc = new AlbaranCliente();
        $doc->setSubject($subject);
        $doc->codalmacen = $warehouse->codalmacen;
        $this->assertTrue($doc->save(), 'albaran-cant-save');

        // añadimos una línea
        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        $this->assertTrue($line->save(), 'can-not-save-line-2');

        // aprobar
        foreach ($doc->getAvailableStatus() as $status) {
            if (empty($status->generadoc)) {
                continue;
            }

            // al cambiar el estado genera una nueva factura
            $doc->idestado = $status->idestado;
            $this->assertTrue($doc->save(), 'albaran-cant-save');

            // comprobamos que la factura se ha creado
            $children = $doc->childrenDocuments();
            $this->assertNotEmpty($children, 'facturas-no-creadas');
            foreach ($children as $child) {
                // comprobamos que tiene la misma empresa y el mismo almacén
                $this->assertEquals($warehouse->codalmacen, $child->codalmacen, 'factura-bad-idempresa');
                $this->assertEquals($company2->idempresa, $child->idempresa, 'factura-bad-idempresa');
            }
        }

        // eliminamos
        $children = $doc->childrenDocuments();
        $this->assertNotEmpty($children, 'facturas-no-creadas');
        foreach ($children as $child) {
            $this->assertTrue($child->delete(), 'factura-cant-delete');
        }
        $this->assertTrue($doc->delete(), 'albaran-cant-delete');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'cliente-cant-delete');
        $this->assertTrue($company2->delete(), 'empresa-cant-delete');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
