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
use FacturaScripts\Core\Controller\AdminPlugins;
use FacturaScripts\Core\Controller\Updater;
use FacturaScripts\Core\Controller\Wizard;
use FacturaScripts\Core\KernelException;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class AdminOnlyControllersTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    /** @var User */
    private $user;

    public function controllerProvider(): array
    {
        return [
            'AdminPlugins' => [AdminPlugins::class],
            'Updater' => [Updater::class],
            'Wizard' => [Wizard::class],
        ];
    }

    /**
     * @dataProvider controllerProvider
     */
    public function testNonAdminIsDenied(string $controllerClass): void
    {
        $name = substr($controllerClass, strrpos($controllerClass, '\\') + 1);
        $controller = new $controllerClass($name, '/' . $name);
        $controller->request = new Request();

        // aunque un rol le dé todos los permisos sobre la página
        $permissions = new ControllerPermissions();
        $permissions->set(true, 99, true, true);

        $response = new Response();
        $response->disableSend(true);

        try {
            $controller->privateCore($response, $this->user, $permissions);
            $this->fail('non-admin-not-denied');
        } catch (KernelException $exception) {
            $this->assertEquals('AccessDenied', $exception->handler, 'bad-exception-handler');
        }
    }

    protected function setUp(): void
    {
        MiniLog::clear();

        $this->user = $this->getRandomUser();
        $this->user->admin = false;
        $this->assertTrue($this->user->save());
    }

    protected function tearDown(): void
    {
        $this->user->delete();
        $this->logErrors();
    }
}
