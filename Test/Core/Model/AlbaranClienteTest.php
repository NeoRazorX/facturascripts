<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021  Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\BusinessDocumentTools;
use FacturaScripts\Core\Model\AlbaranCliente;
use FacturaScripts\Core\Model\Stock;
use FacturaScripts\Test\Core\DefaultSettingsTrait;
use FacturaScripts\Test\Core\LogErrorsTrait;
use FacturaScripts\Test\Core\RandomDataTrait;
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

    public function testDefaultValues()
    {
        $doc = new AlbaranCliente();
        $this->assertNotEmpty($doc->codalmacen, 'empty-warehouse');
        $this->assertNotEmpty($doc->coddivisa, 'empty-currency');
        $this->assertNotEmpty($doc->codserie, 'empty-serie');
        $this->assertNotEmpty($doc->fecha, 'empty-date');
        $this->assertNotEmpty($doc->hora, 'empty-time');
    }

    public function testSetAuthor()
    {
        // create warehouse
        $warehouse = $this->getRandomWarehouse();
        $this->assertTrue($warehouse->save(), 'can-not-create-warehouse');

        // create user
        $user = $this->getRandomUser();
        $user->codalmacen = $warehouse->codalmacen;

        // asignamos el usuario
        $doc = new AlbaranCliente();
        $this->assertTrue($doc->setAuthor($user), 'can-not-set-user');
        $this->assertEquals($user->codalmacen, $doc->codalmacen, 'albaran-cliente-bad-warehouse');
        $this->assertEquals($user->nick, $doc->nick, 'albaran-cliente-bad-nick');

        // eliminamos
        $this->assertTrue($warehouse->delete(), 'can-not-delete-warehouse');
    }

    public function testCreateEmpty()
    {
        // creamos el cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer-1');

        // creamos el albarán
        $doc = new AlbaranCliente();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-albaran-cliente-1');

        // comprobamos valores
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
        $this->assertTrue($subject->delete(), 'can-not-delete-cliente-1');
    }

    public function testCreateWithoutSubject()
    {
        $doc = new AlbaranCliente();
        $this->assertFalse($doc->save(), 'can-create-albaran-cliente-without-subject');
    }

    public function testCreateOneLine()
    {
        // creamos el cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer-2');

        // creamos el albarán
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
        $tool = new BusinessDocumentTools();
        $tool->recalculate($doc);
        $this->assertTrue($doc->save(), 'can-not-update-albaran-cliente-2');

        // comprobamos
        $this->assertEquals(100, $doc->neto, 'albaran-cliente-bad-neto-2');
        $this->assertEquals(121, $doc->total, 'albaran-cliente-bad-total-2');
        $this->assertEquals(21, $doc->totaliva, 'albaran-cliente-bad-totaliva-2');
        $this->assertEquals(0, $doc->totalrecargo, 'albaran-cliente-bad-totalrecargo-2');
        $this->assertEquals(0, $doc->totalirpf, 'albaran-cliente-bad-totalirpf-2');
        $this->assertEquals(0, $doc->totalsuplidos, 'albaran-cliente-bad-totalsuplidos-2');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-albaran-cliente-2');
        $this->assertFalse($line->exists(), 'linea-albaran-cliente-still-exists');
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

        // creamos el albarán
        $doc = new AlbaranCliente();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-albaran-cliente-2');

        // añadimos el producto sin stock
        $line = $doc->getNewProductLine($product->referencia);
        $line->pvpunitario = 10;
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
        $product->loadFromCode($product->idproducto);
        $this->assertEquals(1, $product->stockfis, 'albaran-cliente-product-do-not-update-stock');

        // actualizamos los totales
        $tool = new BusinessDocumentTools();
        $tool->recalculate($doc);
        $this->assertTrue($doc->save(), 'can-not-update-albaran-cliente-3');

        // comprobamos
        $this->assertEquals(10, $doc->neto, 'albaran-cliente-bad-neto-3');
        $this->assertEquals(12.1, $doc->total, 'albaran-cliente-bad-total-3');
        $this->assertEquals(2.1, $doc->totaliva, 'albaran-cliente-bad-totaliva-3');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-albaran-cliente-3');
        $this->assertFalse($line->exists(), 'linea-albaran-cliente-still-exists-3');
        $this->assertTrue($subject->delete(), 'can-not-delete-cliente-3');

        // recargamos producto y comprobamos el stock
        $product->loadFromCode($product->idproducto);
        $this->assertEquals(2, $product->stockfis, 'albaran-cliente-product-do-not-update-stock');

        // eliminamos el producto
        $this->assertTrue($product->delete(), 'can-not-delete-product-3');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
