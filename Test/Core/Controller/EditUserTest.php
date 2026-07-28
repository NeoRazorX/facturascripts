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
use FacturaScripts\Core\Controller\EditUser;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class EditUserTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
    }

    public function testUserCannotEditOrDisableTwoFactorForAnotherUser(): void
    {
        $user = $this->getRandomUser();
        $victim = $this->getRandomUser();
        $victim->enableTwoFactor('ABCDEFGHIJKLMNOP');
        $victimEmail = $victim->email;

        $this->assertTrue($user->save());
        $this->assertTrue($victim->save());

        try {
            $this->runController($user, ['code' => $user->nick], [
                'action' => 'edit',
                'code' => $victim->nick,
                'nick' => $victim->nick,
                'email' => 'modified@example.com',
                'newPassword' => 'modified-password-123',
                'newPassword2' => 'modified-password-123',
                'admin' => 'TRUE',
                'enabled' => 'TRUE',
                'level' => '99',
            ]);

            $victim->load($victim->nick);
            $this->assertSame($victimEmail, $victim->email);
            $this->assertFalse($victim->verifyPassword('modified-password-123'));

            $this->runController($user, ['code' => $user->nick], [
                'action' => 'two-factor-disable',
                'code' => $victim->nick,
            ]);

            $victim->load($victim->nick);
            $this->assertTrue($victim->two_factor_enabled);
            $this->assertSame('ABCDEFGHIJKLMNOP', $victim->two_factor_secret_key);
        } finally {
            $this->deleteUser($victim->nick);
            $this->deleteUser($user->nick);
        }
    }

    public function testUserCannotModifyRestrictedFields(): void
    {
        $user = $this->getRandomUser();
        $user->lastip = '10.0.0.1';
        $this->assertTrue($user->save());

        $originalLevel = $user->level;
        try {
            $this->runController($user, ['code' => $user->nick], [
                'action' => 'edit',
                'code' => $user->nick,
                'nick' => $user->nick,
                'email' => $user->email,
                'langcode' => $user->langcode,
                'enabled' => 'TRUE',
                'level' => '99',
                'lastip' => '203.0.113.10',
                'lastbrowser' => 'modified',
            ]);

            $user->load($user->nick);
            $this->assertSame($originalLevel, $user->level);
            $this->assertSame('10.0.0.1', $user->lastip);
            $this->assertNotSame('modified', $user->lastbrowser);
        } finally {
            $this->deleteUser($user->nick);
        }
    }

    public function testAdminCannotDeleteItself(): void
    {
        $admin = $this->getRandomUser();
        $admin->admin = true;
        $this->assertTrue($admin->save());

        try {
            $this->runController($admin, ['code' => $admin->nick], [
                'action' => 'delete',
                'code' => $admin->nick,
            ]);

            $admin->load($admin->nick);
            $this->assertTrue($admin->exists());
        } finally {
            $this->deleteUser($admin->nick);
        }
    }

    public function testCannotDisplayExistingTwoFactorSecret(): void
    {
        $user = $this->getRandomUser();
        $user->enableTwoFactor('ABCDEFGHIJKLMNOP');
        $this->assertTrue($user->save());

        try {
            $controller = $this->runController($user, ['code' => $user->nick], [
                'action' => 'two-factor-enable',
                'code' => $user->nick,
            ]);

            $this->assertNotSame('EditUserTwoFactor', $controller->getTemplate());
        } finally {
            $this->deleteUser($user->nick);
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

    private function deleteUser(string $nick): void
    {
        $user = new User();
        if ($user->load($nick)) {
            $this->assertTrue($user->delete());
        }
    }

    private function runController(User $user, array $query, array $request): EditUser
    {
        $controller = new EditUser('EditUser', '/EditUser');
        $controller->multiRequestProtection->clearSeed();
        $controller->multiRequestProtection->addSeed($user->nick);
        $request['multireqtoken'] = $controller->multiRequestProtection->newToken();
        $controller->multiRequestProtection->clearSeed();
        $controller->request = new Request([
            'query' => $query,
            'request' => $request,
        ]);

        $permissions = new ControllerPermissions();
        $permissions->set(true, $user->admin ? 99 : 1, $user->admin, true);

        $response = new Response();
        $controller->privateCore($response, $user, $permissions);
        return $controller;
    }
}
