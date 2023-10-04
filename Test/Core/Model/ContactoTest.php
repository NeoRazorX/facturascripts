<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Contacto;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class ContactoTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $db = new DataBase();
        if (false === $db->connected()) {
            $db->connect();
        }
    }

    public function testCreate(): void
    {
        $contact = new Contacto();
        $contact->nombre = 'Test';
        $contact->apellidos = 'Contact';
        $this->assertTrue($contact->save(), 'contact-cant-save');
        $this->assertNotNull($contact->primaryColumnValue(), 'contact-not-stored');
        $this->assertTrue($contact->exists(), 'contact-cant-persist');
        $this->assertTrue($contact->delete(), 'contact-cant-delete');
    }

    public function testCreateEmail(): void
    {
        $contact = new Contacto();
        $contact->email = 'pepe@test.es';
        $this->assertTrue($contact->save(), 'contact-cant-save');

        // eliminamos
        $this->assertTrue($contact->delete(), 'contact-cant-delete');
    }

    public function testCreateCustomerAddress(): void
    {
        // creamos el cliente
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'customer-cant-save');

        // creamos el contacto
        $contact = new Contacto();
        $contact->codcliente = $customer->codcliente;
        $contact->direccion = 'Test';
        $this->assertTrue($contact->save(), 'customer-address-cant-save');

        // nombre y apellidos están vacíos
        $this->assertEquals('', $contact->nombre);
        $this->assertEquals('', $contact->apellidos);

        // eliminamos
        $this->assertTrue($contact->delete(), 'contact-cant-delete');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($customer->delete(), 'customer-cant-delete');
    }

    public function testCreateSupplierAddress(): void
    {
        // creamos el proveedor
        $supplier = $this->getRandomSupplier();
        $supplier->save();

        // creamos el contacto
        $contact = new Contacto();
        $contact->codproveedor = $supplier->codproveedor;
        $contact->direccion = 'Test';
        $this->assertTrue($contact->save(), 'supplier-address-cant-save');

        // nombre y apellidos están vacíos
        $this->assertEquals('', $contact->nombre);
        $this->assertEquals('', $contact->apellidos);

        // eliminamos
        $this->assertTrue($contact->delete(), 'contact-cant-delete');
        $this->assertTrue($supplier->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($supplier->delete(), 'supplier-cant-delete');
    }

    public function testCantCreateEmpty(): void
    {
        $contact = new Contacto();
        $contact->nombre = '';
        $contact->apellidos = '';
        $contact->email = '';
        $contact->descripcion = '';
        $contact->direccion = '';
        $this->assertFalse($contact->save(), 'contact-cant-save-empty');
    }

    public function testBadEmail(): void
    {
        $contact = new Contacto();
        $contact->email = 'pepe-mail';
        $this->assertFalse($contact->save(), 'contact-can-save-bad-email');

        // probamos un email correcto
        $contact->email = 'pepe@facturascripts.com';
        $this->assertTrue($contact->save(), 'contact-cant-save-good-email');

        // eliminamos
        $this->assertTrue($contact->delete(), 'contact-cant-delete');
    }

    public function testHtmlOnFields(): void
    {
        $contact = new Contacto();
        $contact->nombre = '<script>alert("test");</script>';
        $contact->apellidos = '<script>alert("test");</script>';
        $contact->direccion = '<script>alert("test");</script>';
        $contact->descripcion = '<script>alert("test");</script>';
        $contact->ciudad = '<script>alert("test");</script>';
        $contact->provincia = '<script>alert("test");</script>';
        $contact->empresa = '<script>alert("test");</script>';
        $contact->cifnif = '<test>';
        $contact->telefono1 = '<test>';
        $contact->telefono2 = '<test>';
        $contact->fax = '<test>';
        $contact->observaciones = '<script>alert("test");</script>';
        $this->assertTrue($contact->save(), 'contact-cant-save-html');

        // comprobamos que el html se ha escapado
        $this->assertEquals('&lt;script&gt;alert(&quot;test&quot;);&lt;/script&gt;', $contact->nombre);
        $this->assertEquals('&lt;script&gt;alert(&quot;test&quot;);&lt;/script&gt;', $contact->apellidos);
        $this->assertEquals('&lt;script&gt;alert(&quot;test&quot;);&lt;/script&gt;', $contact->direccion);
        $this->assertEquals('&lt;script&gt;alert(&quot;test&quot;);&lt;/script&gt;', $contact->descripcion);
        $this->assertEquals('&lt;script&gt;alert(&quot;test&quot;);&lt;/script&gt;', $contact->ciudad);
        $this->assertEquals('&lt;script&gt;alert(&quot;test&quot;);&lt;/script&gt;', $contact->provincia);
        $this->assertEquals('&lt;script&gt;alert(&quot;test&quot;);&lt;/script&gt;', $contact->empresa);
        $this->assertEquals('&lt;test&gt;', $contact->cifnif);
        $this->assertEquals('&lt;test&gt;', $contact->telefono1);
        $this->assertEquals('&lt;test&gt;', $contact->telefono2);
        $this->assertEquals('&lt;test&gt;', $contact->fax);
        $this->assertEquals('&lt;script&gt;alert(&quot;test&quot;);&lt;/script&gt;', $contact->observaciones);

        // eliminamos
        $this->assertTrue($contact->delete(), 'contact-cant-delete');
    }

    public function testNotNullFields(): void
    {
        // creamos un contacto
        $contact = new Contacto();
        $contact->nombre = 'Test';
        $this->assertTrue($contact->save(), 'contact-cant-save');

        // comprobamos que apellidos, cargo, direccion, teléfonos, fax, email y observaciones no sean nulos
        $this->assertNotNull($contact->apellidos, 'contact-apellidos-null');
        $this->assertNotNull($contact->cargo, 'contact-cargo-null');
        $this->assertNotNull($contact->empresa, 'contact-empresa-null');
        $this->assertNotNull($contact->ciudad, 'contact-ciudad-null');
        $this->assertNotNull($contact->direccion, 'contact-direccion-null');
        $this->assertNotNull($contact->provincia, 'contact-provincia-null');
        $this->assertNotNull($contact->telefono1, 'contact-telefono1-null');
        $this->assertNotNull($contact->telefono2, 'contact-telefono2-null');
        $this->assertNotNull($contact->fax, 'contact-fax-null');
        $this->assertNotNull($contact->email, 'contact-email-null');
        $this->assertNotNull($contact->observaciones, 'contact-observaciones-null');

        // eliminamos
        $this->assertTrue($contact->delete(), 'contact-cant-delete');
    }

    public function testVies(): void
    {
        // creamos un contacto sin cif/nif
        $contact = new Contacto();
        $contact->nombre = 'Test';
        $this->assertFalse($contact->checkVies());

        // asignamos un cif/nif incorrecto
        $contact->cifnif = '123456789';
        $this->assertFalse($contact->checkVies());

        // asignamos un cif/nif correcto
        $contact->cifnif = 'ESB01563311';
        $this->assertTrue($contact->checkVies());
    }

    public function testAlias()
    {
        $contacto = new Contacto();
        $contacto->idcontacto = 999;

        $result = $contacto->alias();

        static::assertEquals('999', $result);

        $contacto->email = 'noreply@example.com';
        $result = $contacto->alias();
        static::assertEquals('noreply_999', $result);

        $contacto->email = 'info@example.com';
        $result = $contacto->alias();
        static::assertEquals('example_999', $result);
    }

    public function testCodeModelSearch(): void
    {
        $contact1 = $this->getRandomContact();
        $contact1->save();

        $contact2 = $this->getRandomContact();
        $contact2->save();

        // Sin pasar ningún parametro de busqueda debe devolver todos los registros
        $query = '';
        $fieldCode = '';
        $results = (new Contacto())->codeModelSearch($query, $fieldCode, []);
        static::assertCount(count((new Contacto())->all()), $results);

        // Pasando el nombre del primer contacto debe devolver solo un registro
        $query = $contact1->nombre;
        $fieldCode = '';
        $results = (new Contacto())->codeModelSearch($query, $fieldCode, []);
        static::assertCount(1, $results);
        static::assertEquals($contact1->descripcion, trim($results[0]->description));

        // Pasando un valor que no existe no devuelve ningún contacto
        $query = 'dummy-text';
        $fieldCode = '';
        $results = (new Contacto())->codeModelSearch($query, $fieldCode, []);
        static::assertCount(0, $results);

        // Pasando una clausula where devuelve el resultado de la consulta
        $query = '';
        $fieldCode = '';
        $where = [new DataBaseWhere('empresa', $contact2->empresa)];
        $results = (new Contacto())->codeModelSearch($query, $fieldCode, $where);
        static::assertCount(1, $results);
        static::assertEquals($contact2->descripcion, trim($results[0]->description));

        $contact1->delete();
        $contact2->delete();
    }

    public function testCountry()
    {
        $contacto = new Contacto();
        $contacto->codpais = 'ESP';

        static::assertEquals('España', $contacto->country());

        $contacto->codpais = 'ABW';
        static::assertEquals('Aruba', $contacto->country());

        $contacto->codpais = 'WRONG-COD-PAIS';
        static::assertEquals('WRONG-COD-PAIS', $contacto->country());
    }

    public function testGetCustomer()
    {
        $contacto = $this->getRandomContact();

        // Como no existe Cliente asociado al contacto devuelve un Cliente vacío(todos los campos a null)
        // ya que indicamos por parametro que no cree ningún cliente
        $result = $contacto->getCustomer(false);
        static::assertNull($result->codcliente);

        // Crea un Cliente con los mismos campos del contacto ya que no tiene ningún Cliente asociado
        // y hemos indicado por parametro que se cree un Cliente
        $result = $contacto->getCustomer(true);
        static::assertEquals($result->codcliente, $contacto->codcliente);

        // Creamos un Cliente y lo asociamos al Contacto. Debe devolver el Cliente asociado
        $cliente = $this->getRandomCustomer();
        $cliente->save();

        $contacto->codcliente = $cliente->codcliente;
        $contacto->save();

        $result = $contacto->getCustomer(true);

        static::assertEquals($cliente->codcliente, $result->codcliente);
    }

    public function testGetSupplier()
    {
        $contacto = $this->getRandomContact();

        // Como no existe Proveedor asociado al contacto devuelve un Proveedor vacío(todos los campos a null)
        // ya que indicamos por parametro que no cree ningún cliente
        $result = $contacto->getSupplier(false);
        static::assertNull($result->codproveedor);

        // Crea un Proveedor con los mismos campos del contacto ya que no tiene ningún Proveedor asociado
        // y hemos indicado por parametro que se cree un Proveedor
        $result = $contacto->getSupplier(true);
        static::assertEquals($result->codproveedor, $contacto->codproveedor);

        // Creamos un Proveedor y lo asociamos al Contacto. Debe devolver el Proveedor asociado
        $cliente = $this->getRandomSupplier();
        $cliente->save();

        $contacto->codproveedor = $cliente->codproveedor;
        $contacto->save();

        $result = $contacto->getSupplier(true);

        static::assertEquals($cliente->codproveedor, $result->codproveedor);
    }

    public function testInstall()
    {
        $contacto = new Contacto();
        $result = $contacto->install();
        static::assertEquals('', $result);
    }

    public function testNewLogkey()
    {
        $fakeIP = '192.192.192.192';

        $contacto = new Contacto();

        $result = $contacto->newLogkey($fakeIP);

        static::assertEquals($fakeIP, $contacto->lastip);
        static::assertEquals($contacto->logkey, $result);
    }

    public function testPrimaryDescriptionColumn()
    {
        $contacto = new Contacto();

        $result = $contacto->primaryDescriptionColumn();

        static::assertEquals('descripcion', $result);
    }

    public function testUrl()
    {
        $contacto = new Contacto();

        $result = $contacto->url();

        static::assertEquals('ListCliente?activetab=ListContacto', $result);
    }

    public function testVerifyLogkey()
    {
        $contacto = new Contacto();
        $contacto->logkey = 'fake-logkey';

        $result = $contacto->verifyLogkey('fake-logkey');
        static::assertTrue($result);

        $result = $contacto->verifyLogkey('fake-logkey-2');
        static::assertFalse($result);
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
