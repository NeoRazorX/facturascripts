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
use FacturaScripts\Core\Controller\EditSettings;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class EditSettingsTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
    }

    public function testApiKeysTabOnlyForAdmins(): void
    {
        $user = $this->getRandomUser();
        $user->admin = false;
        $this->assertTrue($user->save());

        try {
            // aunque un rol le dé todos los permisos sobre la página, no ve las claves de la API
            $controller = $this->runController($user);
            $this->assertArrayHasKey('SettingsDefault', $controller->views);
            $this->assertArrayNotHasKey('ListApiKey', $controller->views);

            // un administrador sí las ve
            $user->admin = true;
            $this->assertTrue($user->save());
            $controller = $this->runController($user);
            $this->assertArrayHasKey('ListApiKey', $controller->views);
        } finally {
            $user->delete();
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

    private function runController(User $user): EditSettings
    {
        $controller = new EditSettings('EditSettings', '/EditSettings');
        $controller->request = new Request();

        $permissions = new ControllerPermissions();
        $permissions->set(true, $user->admin ? 99 : 1, true, true);

        $response = new Response();
        $response->disableSend(true);
        $controller->privateCore($response, $user, $permissions);
        return $controller;
    }
}
