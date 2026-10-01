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

namespace FacturaScripts\Test\Core\Lib\ExtendedController;

use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Dinamic\Controller\EditCliente;
use FacturaScripts\Dinamic\Controller\ListCliente;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

/**
 * Una pestaña activa que no existe para el usuario (sin permiso para verla, de un plugin
 * desactivado o mal escrita en la URL) no debe romper la página: se usa la primera pestaña.
 */
final class ActiveTabTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    /** @var Cliente */
    private $customer;

    /** @var User */
    private $user;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
    }

    public function testEditTabWithoutPermissionFallsBackToMainTab(): void
    {
        // el usuario no tiene permiso para ver recibos, así que EditCliente no crea esa pestaña
        $this->assertFalse($this->user->can('EditReciboCliente'), 'user-should-not-see-receipts');

        $controller = $this->runController(EditCliente::class, [
            'activetab' => 'ListReciboCliente',
            'code' => $this->customer->codcliente,
        ]);
        $this->assertArrayNotHasKey('ListReciboCliente', $controller->views, 'receipts-tab-should-not-exist');
        $this->assertSame('EditCliente', $controller->active, 'should-fall-back-to-main-tab');
    }

    public function testListUnknownTabFallsBackToFirstTab(): void
    {
        $controller = $this->runController(ListCliente::class, ['activetab' => 'TabQueNoExiste']);
        $this->assertSame(array_key_first($controller->views), $controller->active, 'should-fall-back-to-first-tab');
    }

    public function testValidTabIsKept(): void
    {
        $controller = $this->runController(EditCliente::class, [
            'activetab' => 'EditDireccionContacto',
            'code' => $this->customer->codcliente,
        ]);
        $this->assertArrayHasKey('EditDireccionContacto', $controller->views, 'contacts-tab-should-exist');
        $this->assertSame('EditDireccionContacto', $controller->active, 'valid-tab-should-be-kept');
    }

    protected function setUp(): void
    {
        MiniLog::clear();

        $this->user = $this->getRandomUser();
        $this->assertTrue($this->user->save(), 'can-not-save-user');

        $this->customer = $this->getRandomCustomer('ActiveTabTest');
        $this->assertTrue($this->customer->save(), 'can-not-save-customer');
    }

    protected function tearDown(): void
    {
        $this->customer->getDefaultAddress()->delete();
        $this->customer->delete();
        $this->user->delete();

        $this->logErrors();
    }

    private function runController(string $controllerClass, array $query)
    {
        $pageName = substr(strrchr($controllerClass, '\\'), 1);
        $controller = new $controllerClass($pageName, '/' . $pageName);

        // el constructor lee la pestaña activa de la petición original
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $controller->request = new Request(['query' => $query]);
        $controller->active = $query['activetab'];

        // permisos completos sobre la página, pero no sobre las demás (como recibos)
        $permissions = new ControllerPermissions();
        $permissions->set(true, 1, true, true);

        $response = new Response();
        $response->disableSend(true);
        $controller->privateCore($response, $this->user, $permissions);

        return $controller;
    }
}
