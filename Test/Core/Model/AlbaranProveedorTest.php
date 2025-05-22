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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Model\AlbaranProveedor;
use FacturaScripts\Core\Model\Almacen;
use FacturaScripts\Core\Model\Empresa;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class AlbaranProveedorTest extends TestCase
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
        // creamos un albarán
        $doc = new AlbaranProveedor();

        // comprobamos que tiene valores por defecto
        $this->assertNotEmpty($doc->codalmacen, 'empty-warehouse');
        $this->assertNotEmpty($doc->coddivisa, 'empty-currency');
        $this->assertNotEmpty($doc->codserie, 'empty-serie');
        $this->assertNotEmpty($doc->fecha, 'empty-date');
        $this->assertNotEmpty($doc->hora, 'empty-time');
    }

    public function testSetAuthor()
    {
        // creamos un almacén
        $warehouse = $this->getRandomWarehouse();
        $this->assertTrue($warehouse->save(), 'can-not-create-warehouse');

        // creamos un usuario
        $user = $this->getRandomUser();
        $user->codalmacen = $warehouse->codalmacen;

        // creamos un albarán y le asignamos el usuario
        $doc = new AlbaranProveedor();
        $this->assertTrue($doc->setAuthor($user), 'can-not-set-user');

        // comprobamos que se le han asignado el usuario y el almacén
        $this->assertEquals($user->codalmacen, $doc->codalmacen, 'albaran-proveedor-bad-warehouse');
        $this->assertEquals($user->nick, $doc->nick, 'albaran-proveedor-bad-nick');

        // eliminamos
        $this->assertTrue($warehouse->delete(), 'can-not-delete-warehouse');
    }

    public function testSetSubject()
    {
        // creamos un proveedor
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save(), 'can-not-save-supplier-1');

        // creamos un albarán y le asignamos el proveedor
        $doc = new AlbaranProveedor();
        $this->assertTrue($doc->setSubject($subject), 'can-not-set-subject-1');

        // comprobamos que se han asignado los datos del proveedor
        $this->assertEquals($subject->cifnif, $doc->cifnif, 'albaran-proveedor-bad-cifnif-1');
        $this->assertEquals($subject->codproveedor, $doc->codproveedor, 'albaran-proveedor-bad-codproveedor-1');
        $this->assertEquals($subject->razonsocial, $doc->nombre, 'albaran-proveedor-bad-nombre-1');

        // eliminamos
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-proveedor-1');
    }

    public function testCreateEmpty()
    {
        // creamos un proveedor
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save(), 'can-not-save-supplier-1');

        // creamos un albarán
        $doc = new AlbaranProveedor();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-albaran-proveedor-1');

        // comprobamos valores por defecto
        $this->assertEquals(date('d-m-Y'), $doc->fecha, 'albaran-proveedor-bad-date-1');
        $this->assertEquals(0, $doc->dtopor1, 'albaran-proveedor-bad-dtopor1-1');
        $this->assertEquals(0, $doc->dtopor2, 'albaran-proveedor-bad-dtopor2-1');
        $this->assertEquals(0, $doc->netosindto, 'albaran-proveedor-bad-netosindto-1');
        $this->assertEquals(0, $doc->neto, 'albaran-proveedor-bad-neto-1');
        $this->assertEquals(0, $doc->total, 'albaran-proveedor-bad-total-1');
        $this->assertEquals(0, $doc->totaliva, 'albaran-proveedor-bad-totaliva-1');
        $this->assertEquals(0, $doc->totalrecargo, 'albaran-proveedor-bad-totalrecargo-1');
        $this->assertEquals(0, $doc->totalirpf, 'albaran-proveedor-bad-totalirpf-1');
        $this->assertEquals(0, $doc->totalsuplidos, 'albaran-proveedor-bad-totalsuplidos-1');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-albaran-proveedor-1');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-proveedor-1');
    }

    public function testCreateWithoutSubject()
    {
        $doc = new AlbaranProveedor();
        $this->assertFalse($doc->save(), 'can-create-albaran-proveedor-without-subject');
    }

    public function testCreateOneLine()
    {
        // creamos un proveedor
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save(), 'can-not-save-supplier-2');

        // creamos un albarán
        $doc = new AlbaranProveedor();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-albaran-proveedor-2');

        // añadimos una línea
        $line = $doc->getNewLine();
        $line->pvpunitario = 100;
        $this->assertTrue($line->save(), 'can-not-save-line-2');
        $this->assertNotEmpty($line->idlinea, 'empty-line-id-2');
        $this->assertTrue($line->exists(), 'line-not-persist-2');

        // actualizamos los totales
        $lines = $doc->getLines();
        $this->assertTrue(Calculator::calculate($doc, $lines, true), 'can-not-update-albaran-proveedor-2');

        // comprobamos
        $this->assertEquals(100, $doc->neto, 'albaran-proveedor-bad-neto-2');
        $this->assertEquals(121, $doc->total, 'albaran-proveedor-bad-total-2');
        $this->assertEquals(21, $doc->totaliva, 'albaran-proveedor-bad-totaliva-2');
        $this->assertEquals(0, $doc->totalrecargo, 'albaran-proveedor-bad-totalrecargo-2');
        $this->assertEquals(0, $doc->totalirpf, 'albaran-proveedor-bad-totalirpf-2');
        $this->assertEquals(0, $doc->totalsuplidos, 'albaran-proveedor-bad-totalsuplidos-2');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-albaran-proveedor-2');
        $this->assertFalse($line->exists(), 'linea-albaran-proveedor-still-exists-2');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-proveedor-2');
    }

    public function testCreateProductLine()
    {
        // creamos un proveedor
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save(), 'can-not-save-supplier-3');

        // creamos un producto
        $product = $this->getRandomProduct();
        $this->assertTrue($product->save(), 'can-not-save-supplier-3');

        // creamos un albarán
        $doc = new AlbaranProveedor();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-albaran-proveedor-3');

        // añadimos el producto
        $line = $doc->getNewProductLine($product->referencia);
        $line->pvpunitario = 10;
        $this->assertTrue($line->save(), 'can-not-save-line-3');

        // recargamos producto y comprobamos el stock
        $product->loadFromCode($product->idproducto);
        $this->assertEquals(1, $product->stockfis, 'albaran-proveedor-product-do-not-update-stock');

        // actualizamos los totales
        $lines = $doc->getLines();
        $this->assertTrue(Calculator::calculate($doc, $lines, true), 'can-not-update-albaran-proveedor-3');

        // comprobamos
        $this->assertEquals(10, $doc->neto, 'albaran-proveedor-bad-neto-3');
        $this->assertEquals(12.1, $doc->total, 'albaran-proveedor-bad-total-3');
        $this->assertEquals(2.1, $doc->totaliva, 'albaran-proveedor-bad-totaliva-3');

        // modificamos la cantidad
        $line->cantidad = 10;
        $this->assertTrue($line->save(), 'can-not-update-line-3');

        // recargamos producto y comprobamos el stock
        $product->loadFromCode($product->idproducto);
        $this->assertEquals(10, $product->stockfis, 'albaran-proveedor-product-do-not-update-stock');

        // actualizamos los totales
        $lines = $doc->getLines();
        $this->assertTrue(Calculator::calculate($doc, $lines, true), 'can-not-update-albaran-proveedor-3');

        // comprobamos
        $this->assertEquals(100, $doc->neto, 'albaran-proveedor-bad-neto-3');
        $this->assertEquals(121, $doc->total, 'albaran-proveedor-bad-total-3');
        $this->assertEquals(21, $doc->totaliva, 'albaran-proveedor-bad-totaliva-3');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-albaran-proveedor-3');
        $this->assertFalse($line->exists(), 'linea-albaran-proveedor-still-exists-3');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-proveedor-3');

        // recargamos producto y comprobamos el stock
        $product->loadFromCode($product->idproducto);
        $this->assertEquals(0, $product->stockfis, 'albaran-proveedor-product-do-not-update-stock');

        // eliminamos el producto
        $this->assertTrue($product->delete(), 'can-not-delete-product-3');
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

        // creamos un cliente
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save(), 'can-not-save-customer-2');

        // creamos un albarán y le asignamos el proveedor y el almacén
        $doc = new AlbaranProveedor();
        $doc->setSubject($subject);
        $doc->codalmacen = $warehouse->codalmacen;
        $this->assertTrue($doc->save(), 'albaran-cant-save');

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

            // al cambiar el estado genera una nueva factura
            $doc->idestado = $status->idestado;
            $this->assertTrue($doc->save(), 'albaran-cant-save');

            // comprobamos que la factura se ha creado
            $children = $doc->childrenDocuments();
            $this->assertNotEmpty($children, 'facturas-no-creadas');
            foreach ($children as $child) {
                // comprobamos que la factura tiene el mismo almacén y la misma empresa
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
