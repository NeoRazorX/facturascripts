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

namespace FacturaScripts\Test\Core;

use FacturaScripts\Core\CrashReport;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class CrashReportTest extends TestCase
{
    use LogErrorsTrait;

    /** @var array */
    private $cookies;

    /** @var array */
    private $server;

    public function testDeployButtonsForAdminSession(): void
    {
        $user = new User();
        $user->nick = 'test_crash_' . mt_rand(1, 9999);
        $this->assertTrue($user->setPassword('test-crash-1234'), 'can-not-set-password');
        $user->admin = true;
        $logkey = $user->newLogkey('192.168.1.10');
        $this->assertTrue($user->save(), 'can-not-save-user');

        // un administrador con la sesión válida ve los botones
        $_COOKIE = ['fsNick' => $user->nick, 'fsLogkey' => $logkey];
        $this->assertTrue(CrashReport::canShowDeployButtons(), 'admin-session-without-buttons');

        // pero no con otra logkey
        $_COOKIE['fsLogkey'] = 'bad-logkey';
        $this->assertFalse(CrashReport::canShowDeployButtons(), 'bad-logkey-with-buttons');

        // ni si no es administrador
        $user->admin = false;
        $this->assertTrue($user->save(), 'can-not-save-user');
        $_COOKIE['fsLogkey'] = $logkey;
        $this->assertFalse(CrashReport::canShowDeployButtons(), 'non-admin-with-buttons');

        // ni si está desactivado
        $user->admin = true;
        $user->enabled = false;
        $this->assertTrue($user->save(), 'can-not-save-user');
        $this->assertFalse(CrashReport::canShowDeployButtons(), 'disabled-user-with-buttons');

        $this->assertTrue($user->delete(), 'can-not-delete-user');
    }

    public function testDeployButtonsForLocalRequest(): void
    {
        foreach (['127.0.0.1', '::1'] as $ip) {
            $_SERVER['REMOTE_ADDR'] = $ip;
            $this->assertTrue(CrashReport::canShowDeployButtons(), 'local-request-without-buttons: ' . $ip);
        }

        // si pasa por un proxy, la petición no es local aunque REMOTE_ADDR sea 127.0.0.1
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.7';
        $this->assertFalse(CrashReport::canShowDeployButtons(), 'proxied-request-with-buttons');
    }

    public function testNoDeployButtonsForAttacker(): void
    {
        // cookies de login inventadas
        $_COOKIE = ['fsNick' => 'admin', 'fsLogkey' => 'fake'];
        $this->assertFalse(CrashReport::canShowDeployButtons(), 'fake-cookies-with-buttons');

        // cabecera Host manipulada
        $_COOKIE = [];
        $_SERVER['HTTP_HOST'] = 'localhost';
        $this->assertFalse(CrashReport::canShowDeployButtons(), 'host-localhost-with-buttons');

        // cookies que no son texto
        $_COOKIE = ['fsNick' => ['admin'], 'fsLogkey' => ['fake']];
        $this->assertFalse(CrashReport::canShowDeployButtons(), 'array-cookies-with-buttons');
    }

    protected function setUp(): void
    {
        if (Tools::config('disable_deploy_actions', false)) {
            $this->markTestSkipped('deploy-actions-disabled');
        }

        $this->cookies = $_COOKIE;
        $this->server = $_SERVER;

        // partimos de una petición remota, sin cookies ni proxy
        $_COOKIE = [];
        $_SERVER['REMOTE_ADDR'] = '203.0.113.5';
        unset($_SERVER['HTTP_CF_CONNECTING_IP'], $_SERVER['HTTP_FORWARDED'], $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_X_REAL_IP']);
    }

    protected function tearDown(): void
    {
        $_COOKIE = $this->cookies ?? [];
        $_SERVER = $this->server ?? $_SERVER;

        $this->logErrors();
    }
}
