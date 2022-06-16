<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Contacto;
use FacturaScripts\Test\Core\LogErrorsTrait;
use FacturaScripts\Test\Core\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class ContactoTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    public function testCreate()
    {
        $contact = new Contacto();
        $contact->nombre = 'Test';
        $contact->apellidos = 'Contact';
        $this->assertTrue($contact->save(), 'contact-cant-save');
        $this->assertNotNull($contact->primaryColumnValue(), 'contact-not-stored');
        $this->assertTrue($contact->exists(), 'contact-cant-persist');
        $this->assertTrue($contact->delete(), 'contact-cant-delete');
    }

    public function testCreateEmail()
    {
        $contact = new Contacto();
        $contact->email = 'pepe@test.es';
        $this->assertTrue($contact->save(), 'contact-cant-save');

        // eliminamos
        $this->assertTrue($contact->delete(), 'contact-cant-delete');
    }

    public function testCreateCustomerAddress()
    {
        // creamos el cliente
        $customer = $this->getRandomCustomer();
        $customer->save();

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
        $this->assertTrue($customer->delete(), 'customer-cant-delete');
    }

    public function testCreateSupplierAddress()
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
        $this->assertTrue($supplier->delete(), 'supplier-cant-delete');
    }

    public function testCantCreateEmpty()
    {
        $contact = new Contacto();
        $contact->nombre = '';
        $contact->apellidos = '';
        $contact->email = '';
        $contact->descripcion = '';
        $contact->direccion = '';
        $this->assertFalse($contact->save(), 'contact-cant-save-empty');
    }

    public function testBadEmail()
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

    public function testHtmlOnFields()
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

    public function testNotNullFields()
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

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
