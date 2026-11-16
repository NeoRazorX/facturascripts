<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Controller;

use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Controller\EditContacto;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class EditContactoTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    /** @var User */
    private $user;

    public function testConvertIntoCustomerRequiresUpdatePermissionAndToken(): void
    {
        $contact = $this->getRandomContact('EditContactoTest');
        $this->assertTrue($contact->save());

        try {
            // un enlace GET sin token no crea el cliente
            $this->runController($contact, 'convert-into-customer', true, null);
            $this->assertNull($this->reload($contact)->codcliente);

            // un POST con token inválido tampoco
            $this->runController($contact, 'convert-into-customer', true, false);
            $this->assertNull($this->reload($contact)->codcliente);

            // sin permiso de modificación en EditContacto tampoco
            $this->runController($contact, 'convert-into-customer', false, true);
            $this->assertNull($this->reload($contact)->codcliente);

            // con permiso y token se crea el cliente
            $this->runController($contact, 'convert-into-customer', true, true);
            $this->assertNotEmpty($this->reload($contact)->codcliente);
        } finally {
            $customer = new Cliente();
            if ($customer->load($this->reload($contact)->codcliente)) {
                $customer->delete();
            }
            $this->deleteContact($contact);
        }
    }

    public function testConvertIntoSupplierRequiresUpdatePermissionAndToken(): void
    {
        $contact = $this->getRandomContact('EditContactoTest');
        $this->assertTrue($contact->save());

        try {
            // un enlace GET sin token no crea el proveedor
            $this->runController($contact, 'convert-into-supplier', true, null);
            $this->assertNull($this->reload($contact)->codproveedor);

            // un POST con token inválido tampoco
            $this->runController($contact, 'convert-into-supplier', true, false);
            $this->assertNull($this->reload($contact)->codproveedor);

            // sin permiso de modificación en EditContacto tampoco
            $this->runController($contact, 'convert-into-supplier', false, true);
            $this->assertNull($this->reload($contact)->codproveedor);

            // con permiso y token se crea el proveedor
            $this->runController($contact, 'convert-into-supplier', true, true);
            $this->assertNotEmpty($this->reload($contact)->codproveedor);
        } finally {
            $supplier = new Proveedor();
            if ($supplier->load($this->reload($contact)->codproveedor)) {
                $supplier->delete();
            }
            $this->deleteContact($contact);
        }
    }

    public function testEditDefaultContactKeepsCustomerFax(): void
    {
        // creamos un cliente con fax
        $customer = new Cliente();
        $customer->nombre = 'EditContactoTest Fax';
        $customer->cifnif = '12345678A';
        $customer->email = 'old-fax-test@example.com';
        $customer->fax = '910000001';
        $this->assertTrue($customer->save());
        $contact = $customer->getDefaultAddress();

        try {
            // editamos el email de su contacto por defecto;
            // el formulario envía todos los campos del contacto
            $formData = array_merge($contact->toArray(), [
                'activetab' => 'EditContacto',
                'code' => $contact->idcontacto,
                'email' => 'new-fax-test@example.com',
            ]);
            $this->runController($contact, 'edit', true, true, $formData);
            $this->assertEquals('new-fax-test@example.com', $this->reload($contact)->email);

            // el cliente recibe el email y conserva el fax
            $this->assertTrue($customer->load($customer->id()));
            $this->assertEquals('new-fax-test@example.com', $customer->email, 'customer-email-not-updated');
            $this->assertEquals('910000001', $customer->fax, 'customer-fax-lost');
        } finally {
            $this->deleteContact($contact);
            $customer->delete();
        }
    }

    public function testEditDefaultContactKeepsSupplierFax(): void
    {
        // creamos un proveedor con fax
        $supplier = new Proveedor();
        $supplier->nombre = 'EditContactoTest Fax';
        $supplier->cifnif = '12345678A';
        $supplier->email = 'old-fax-test@example.com';
        $supplier->fax = '910000001';
        $this->assertTrue($supplier->save());
        $contact = $supplier->getDefaultAddress();

        try {
            // editamos el email de su contacto por defecto;
            // el formulario envía todos los campos del contacto
            $formData = array_merge($contact->toArray(), [
                'activetab' => 'EditContacto',
                'code' => $contact->idcontacto,
                'email' => 'new-fax-test@example.com',
            ]);
            $this->runController($contact, 'edit', true, true, $formData);
            $this->assertEquals('new-fax-test@example.com', $this->reload($contact)->email);

            // el proveedor recibe el email y conserva el fax
            $this->assertTrue($supplier->load($supplier->id()));
            $this->assertEquals('new-fax-test@example.com', $supplier->email, 'supplier-email-not-updated');
            $this->assertEquals('910000001', $supplier->fax, 'supplier-fax-lost');
        } finally {
            $this->deleteContact($contact);
            $supplier->delete();
        }
    }

    public function testEditOtherFieldsDoesNotUpdateCustomer(): void
    {
        $customer = new Cliente();
        $customer->nombre = 'EditContactoTest Sync';
        $customer->cifnif = '12345678A';
        $customer->email = 'customer-sync-test@example.com';
        $customer->telefono1 = '600000001';
        $this->assertTrue($customer->save());
        $contact = $customer->getDefaultAddress();

        try {
            // el contacto tiene un email distinto al del cliente
            $contact->email = 'contact-sync-test@example.com';
            $this->assertTrue($contact->save());

            // editamos otro campo del contacto: el cliente no cambia
            $formData = array_merge($contact->toArray(), [
                'activetab' => 'EditContacto',
                'code' => $contact->idcontacto,
                'cargo' => 'Test',
            ]);
            $this->runController($contact, 'edit', true, true, $formData);
            $this->assertEquals('Test', $this->reload($contact)->cargo);
            $this->assertTrue($customer->load($customer->id()));
            $this->assertEquals('customer-sync-test@example.com', $customer->email, 'customer-email-changed');

            // editamos un teléfono del contacto: el cliente recibe email y teléfonos
            $formData['telefono1'] = '611111111';
            $this->runController($contact, 'edit', true, true, $formData);
            $this->assertTrue($customer->load($customer->id()));
            $this->assertEquals('611111111', $customer->telefono1, 'customer-phone-not-updated');
            $this->assertEquals('contact-sync-test@example.com', $customer->email, 'customer-email-not-updated');
        } finally {
            $this->deleteContact($contact);
            $customer->delete();
        }
    }

    protected function setUp(): void
    {
        MiniLog::clear();

        $this->user = $this->getRandomUser();
        $this->user->admin = true;
        $this->assertTrue($this->user->save());
    }

    protected function tearDown(): void
    {
        $this->user->delete();
        $this->logErrors();
    }

    private function deleteContact(Contacto $contact): void
    {
        $reloaded = new Contacto();
        if ($reloaded->load($contact->idcontacto)) {
            $reloaded->delete();
        }
    }

    private function reload(Contacto $contact): Contacto
    {
        $reloaded = new Contacto();
        $this->assertTrue($reloaded->load($contact->idcontacto));
        return $reloaded;
    }

    /**
     * @param bool|null $validToken null envía la acción por GET sin token
     * @param array $formData campos adicionales del formulario
     */
    private function runController(
        Contacto $contact,
        string $action,
        bool $allowUpdate,
        ?bool $validToken,
        array $formData = []
    ): void {
        $controller = new EditContacto('EditContacto', '/EditContacto');
        $controller->multiRequestProtection->clearSeed();
        $controller->multiRequestProtection->addSeed($this->user->nick);
        $token = $controller->multiRequestProtection->newToken();
        $controller->multiRequestProtection->clearSeed();

        $query = ['code' => $contact->idcontacto];
        $request = $formData;
        if (null === $validToken) {
            $query['action'] = $action;
        } else {
            $request['action'] = $action;
            $request['multireqtoken'] = $validToken ? $token : 'invalid-token';
        }

        $controller->request = new Request(['query' => $query, 'request' => $request]);

        $permissions = new ControllerPermissions();
        $permissions->set(true, 99, true, $allowUpdate);

        $response = new Response();
        $controller->privateCore($response, $this->user, $permissions);
    }
}
