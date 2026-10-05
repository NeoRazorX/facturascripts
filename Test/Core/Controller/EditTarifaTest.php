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
use FacturaScripts\Core\Controller\EditTarifa;
use FacturaScripts\Core\Request;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\GrupoClientes;
use FacturaScripts\Dinamic\Model\Tarifa;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class EditTarifaTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    public function testCustomerRateActionsRequireUpdatePermissionAndToken(): void
    {
        $rate = $this->createRate();
        $customer = $this->getRandomCustomer('EditTarifaTest');
        $this->assertTrue($customer->save());

        try {
            $setRequest = ['setcustomerrate' => $customer->codcliente];
            $unsetRequest = ['codes' => [$customer->codcliente]];

            // sin permiso de actualización no se asigna la tarifa
            $this->runAction('setCustomerRate', $rate->codtarifa, $setRequest, false, true);
            $this->assertNull($this->reloadCustomer($customer)->codtarifa);

            // con permiso, pero sin token válido, tampoco
            $this->runAction('setCustomerRate', $rate->codtarifa, $setRequest, true, false);
            $this->assertNull($this->reloadCustomer($customer)->codtarifa);

            // con permiso y token se asigna
            $this->runAction('setCustomerRate', $rate->codtarifa, $setRequest, true, true);
            $this->assertSame($rate->codtarifa, $this->reloadCustomer($customer)->codtarifa);

            // sin permiso de actualización no se desasigna
            $this->runAction('unsetCustomerRate', $rate->codtarifa, $unsetRequest, false, true);
            $this->assertSame($rate->codtarifa, $this->reloadCustomer($customer)->codtarifa);

            // con permiso, pero sin token válido, tampoco
            $this->runAction('unsetCustomerRate', $rate->codtarifa, $unsetRequest, true, false);
            $this->assertSame($rate->codtarifa, $this->reloadCustomer($customer)->codtarifa);

            // con permiso y token se desasigna
            $this->runAction('unsetCustomerRate', $rate->codtarifa, $unsetRequest, true, true);
            $this->assertNull($this->reloadCustomer($customer)->codtarifa);
        } finally {
            $customer->getDefaultAddress()->delete();
            $customer->delete();
            $rate->delete();
        }
    }

    public function testGroupRateActionsRequireUpdatePermissionAndToken(): void
    {
        $rate = $this->createRate();
        $group = new GrupoClientes();
        $group->codgrupo = 'TET' . mt_rand(100, 999);
        $group->nombre = 'Test EditTarifa';
        $this->assertTrue($group->save());

        try {
            $setRequest = ['setgrouprate' => $group->codgrupo];
            $unsetRequest = ['codes' => [$group->codgrupo]];

            // sin permiso de actualización no se asigna la tarifa
            $this->runAction('setGroupRate', $rate->codtarifa, $setRequest, false, true);
            $this->assertNull($this->reloadGroup($group)->codtarifa);

            // con permiso, pero sin token válido, tampoco
            $this->runAction('setGroupRate', $rate->codtarifa, $setRequest, true, false);
            $this->assertNull($this->reloadGroup($group)->codtarifa);

            // con permiso y token se asigna
            $this->runAction('setGroupRate', $rate->codtarifa, $setRequest, true, true);
            $this->assertSame($rate->codtarifa, $this->reloadGroup($group)->codtarifa);

            // sin permiso de actualización no se desasigna
            $this->runAction('unsetGroupRate', $rate->codtarifa, $unsetRequest, false, true);
            $this->assertSame($rate->codtarifa, $this->reloadGroup($group)->codtarifa);

            // con permiso, pero sin token válido, tampoco
            $this->runAction('unsetGroupRate', $rate->codtarifa, $unsetRequest, true, false);
            $this->assertSame($rate->codtarifa, $this->reloadGroup($group)->codtarifa);

            // con permiso y token se desasigna
            $this->runAction('unsetGroupRate', $rate->codtarifa, $unsetRequest, true, true);
            $this->assertNull($this->reloadGroup($group)->codtarifa);
        } finally {
            $group->delete();
            $rate->delete();
        }
    }

    protected function setUp(): void
    {
        MiniLog::clear();
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }

    private function createRate(): Tarifa
    {
        $rate = new Tarifa();
        $rate->codtarifa = 'T' . mt_rand(100, 999);
        $rate->nombre = 'Test EditTarifa';
        $this->assertTrue($rate->save());
        return $rate;
    }

    private function reloadCustomer(Cliente $customer): Cliente
    {
        $reloaded = new Cliente();
        $this->assertTrue($reloaded->load($customer->codcliente));
        return $reloaded;
    }

    private function reloadGroup(GrupoClientes $group): GrupoClientes
    {
        $reloaded = new GrupoClientes();
        $this->assertTrue($reloaded->load($group->codgrupo));
        return $reloaded;
    }

    private function runAction(string $method, string $code, array $data, bool $allowUpdate, bool $validToken): void
    {
        $controller = new TestableEditTarifa('EditTarifa', '/EditTarifa');

        $user = new User();
        $user->nick = 'test-edit-tarifa';
        $controller->user = $user;

        $controller->permissions = new ControllerPermissions();
        $controller->permissions->set(true, 1, false, $allowUpdate);

        $controller->multiRequestProtection->clearSeed();
        $controller->multiRequestProtection->addSeed($user->nick);
        $data['multireqtoken'] = $validToken ? $controller->multiRequestProtection->newToken() : 'invalid-token';

        $controller->request = new Request([
            'query' => ['code' => $code],
            'request' => $data,
        ]);
        $controller->runAction($method);
    }
}

final class TestableEditTarifa extends EditTarifa
{
    public function runAction(string $method): void
    {
        $this->{$method}();
    }
}
