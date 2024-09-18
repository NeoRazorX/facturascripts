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

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\Vies;
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
        // creamos un cliente
        $customer = $this->getRandomCustomer('ContactoTest');
        $this->assertTrue($customer->save(), 'customer-cant-save');

        // comprobamos que el cliente tiene 1 dirección asociada
        $this->assertCount(1, $customer->getAddresses(), 'customer-address-cant-save');

        // creamos un contacto y lo asociamos al cliente
        $contact = new Contacto();
        $contact->codcliente = $customer->codcliente;
        $contact->direccion = 'Test';
        $this->assertTrue($contact->save(), 'customer-address-cant-save');

        // comprobamos que ahora el cliente tiene 2 direcciones asociadas
        $this->assertCount(2, $customer->getAddresses(), 'customer-address-cant-save');

        // eliminamos
        $this->assertTrue($contact->delete(), 'contact-cant-delete');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($customer->delete(), 'customer-cant-delete');
    }

    public function testCreateSupplierAddress(): void
    {
        // creamos un proveedor
        $supplier = $this->getRandomSupplier('ContactoTest');
        $this->assertTrue($supplier->save(), 'supplier-cant-save');

        // comprobamos que el proveedor tiene 1 dirección asociada
        $this->assertCount(1, $supplier->getAddresses(), 'supplier-address-cant-save');

        // creamos un contacto y lo asociamos al proveedor
        $contact = new Contacto();
        $contact->codproveedor = $supplier->codproveedor;
        $contact->direccion = 'Test';
        $this->assertTrue($contact->save(), 'supplier-address-cant-save');

        // comprobamos que ahora el proveedor tiene 2 direcciones asociadas
        $this->assertCount(2, $supplier->getAddresses(), 'supplier-address-cant-save');

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

        // comprobamos que apellidos, cargo, dirección, teléfonos, fax, email y observaciones no sean nulos
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

        $check1 = $contact->checkVies();
        if (Vies::getLastError() != '') {
            $this->markTestSkipped('Vies service error: ' . Vies::getLastError());
        }
        $this->assertFalse($check1);

        // asignamos un cif/nif incorrecto
        $contact->cifnif = '123456789';
        $check2 = $contact->checkVies();
        if (Vies::getLastError() != '') {
            $this->markTestSkipped('Vies service error: ' . Vies::getLastError());
        }
        $this->assertFalse($check2);

        // asignamos un cif/nif correcto
        $contact->cifnif = 'ESB01563311';
        $check3 = $contact->checkVies();
        if (Vies::getLastError() != '') {
            $this->markTestSkipped('Vies service error: ' . Vies::getLastError());
        }
        $this->assertTrue($check3);
    }

    public function testCodeModelSearch(): void
    {
        $contact1 = $this->getRandomContact();
        $contact1->save();

        $contact2 = $this->getRandomContact();
        $contact2->save();

        // Sin pasar ningún parámetro de búsqueda debe devolver todos los registros
        $query = '';
        $fieldCode = '';
        $results = (new Contacto())->codeModelSearch($query, $fieldCode, []);
        $this->assertCount(count((new Contacto())->all()), $results);

        // Pasando el nombre del primer contacto debe devolver solo un registro
        $query = $contact1->nombre;
        $fieldCode = '';
        $results = (new Contacto())->codeModelSearch($query, $fieldCode, []);
        $this->assertCount(1, $results);
        $this->assertEquals($contact1->descripcion, trim($results[0]->description));

        // Pasando un valor que no existe no devuelve ningún contacto
        $query = 'dummy-text';
        $fieldCode = '';
        $results = (new Contacto())->codeModelSearch($query, $fieldCode, []);
        $this->assertCount(0, $results);

        // Pasando una cláusula where devuelve el resultado de la consulta
        $query = '';
        $fieldCode = '';
        $where = [new DataBaseWhere('empresa', $contact2->empresa)];
        $results = (new Contacto())->codeModelSearch($query, $fieldCode, $where);
        $this->assertCount(1, $results);
        $this->assertEquals($contact2->descripcion, trim($results[0]->description));

        $contact1->delete();
        $contact2->delete();
    }

    public function testCountry(): void
    {
        $contacto = new Contacto();
        $contacto->codpais = 'ESP';

        $this->assertEquals('España', $contacto->country());

        $contacto->codpais = 'ABW';
        $this->assertEquals('Aruba', $contacto->country());

        $contacto->codpais = 'WRONG-COD-PAIS';
        $this->assertEquals('WRONG-COD-PAIS', $contacto->country());
    }

    public function testGetCustomer(): void
    {
        // creamos un contacto
        $contacto = $this->getRandomContact('ContactoTest');
        $this->assertTrue($contacto->save());

        // obtenemos el cliente, en este caso uno vacío
        $cliente0 = $contacto->getCustomer(false);
        $this->assertNull($cliente0->codcliente);
        $this->assertFalse($cliente0->exists());

        // obtenemos el cliente, en este caso uno nuevo
        $cliente1 = $contacto->getCustomer(true);
        $this->assertNotNull($cliente1->codcliente);
        $this->assertTrue($cliente1->exists());
        $this->assertEquals($cliente1->codcliente, $contacto->codcliente);

        // llamamos a crear un nuevo cliente, pero no se crea porque ya existe uno
        $cliente2 = $contacto->getCustomer(true);
        $this->assertEquals($cliente1->codcliente, $cliente2->codcliente);

        // creamos otro contacto
        $contacto2 = $this->getRandomContact('ContactoTest');
        $this->assertTrue($contacto2->save());

        // creamos un cliente nuevo
        $cliente3 = $this->getRandomCustomer('ContactoTest');
        $this->assertTrue($cliente3->save());

        // lo asociamos al contacto
        $contacto2->codcliente = $cliente3->codcliente;

        // obtenemos el cliente, en este caso el mismo
        $cliente4 = $contacto2->getCustomer(true);
        $this->assertEquals($cliente3->codcliente, $cliente4->codcliente);

        // eliminamos
        $this->assertTrue($contacto->delete());
        $this->assertTrue($cliente1->delete());
        $this->assertTrue($contacto2->delete());
        $this->assertTrue($cliente3->getDefaultAddress()->delete());
        $this->assertTrue($cliente3->delete());
    }

    public function testGetSupplier(): void
    {
        // creamos un contacto
        $contacto = $this->getRandomContact('ContactoTest');
        $this->assertTrue($contacto->save());

        // obtenemos el proveedor, en este caso uno vacío
        $proveedor0 = $contacto->getSupplier(false);
        $this->assertNull($proveedor0->codproveedor);
        $this->assertFalse($proveedor0->exists());

        // obtenemos el proveedor, en este caso uno nuevo
        $proveedor1 = $contacto->getSupplier(true);
        $this->assertNotNull($proveedor1->codproveedor);
        $this->assertTrue($proveedor1->exists());
        $this->assertEquals($proveedor1->codproveedor, $contacto->codproveedor);

        // llamamos a crear un nuevo proveedor, pero no se crea porque ya existe uno
        $proveedor2 = $contacto->getSupplier(true);
        $this->assertEquals($proveedor1->codproveedor, $proveedor2->codproveedor);

        // creamos otro contacto
        $contacto2 = $this->getRandomContact('ContactoTest');
        $this->assertTrue($contacto2->save());

        // creamos un proveedor nuevo
        $proveedor3 = $this->getRandomSupplier('ContactoTest');
        $this->assertTrue($proveedor3->save());

        // lo asociamos al contacto
        $contacto2->codproveedor = $proveedor3->codproveedor;

        // obtenemos el proveedor, en este caso el mismo
        $proveedor4 = $contacto2->getSupplier(true);
        $this->assertEquals($proveedor3->codproveedor, $proveedor4->codproveedor);

        // eliminamos
        $this->assertTrue($contacto->delete());
        $this->assertTrue($proveedor1->delete());
        $this->assertTrue($contacto2->delete());
        $this->assertTrue($proveedor3->getDefaultAddress()->delete());
        $this->assertTrue($proveedor3->delete());
    }

    public function testInstall(): void
    {
        $contacto = new Contacto();
        $result = $contacto->install();
        $this->assertEquals('', $result);
    }

    public function testPrimaryDescriptionColumn(): void
    {
        $contacto = new Contacto();

        $result = $contacto->primaryDescriptionColumn();

        $this->assertEquals('descripcion', $result);
    }

    public function testUrl(): void
    {
        $contacto = new Contacto();

        $result = $contacto->url();

        $this->assertEquals('ListCliente?activetab=ListContacto', $result);
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
