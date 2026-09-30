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
     */
    private function runController(Contacto $contact, string $action, bool $allowUpdate, ?bool $validToken): void
    {
        $controller = new EditContacto('EditContacto', '/EditContacto');
        $controller->multiRequestProtection->clearSeed();
        $controller->multiRequestProtection->addSeed($this->user->nick);
        $token = $controller->multiRequestProtection->newToken();
        $controller->multiRequestProtection->clearSeed();

        $query = ['code' => $contact->idcontacto];
        $request = [];
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
