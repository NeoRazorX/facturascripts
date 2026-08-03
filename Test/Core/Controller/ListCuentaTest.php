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

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Controller\ListCuenta;
use FacturaScripts\Core\Request;
use FacturaScripts\Dinamic\Model\CuentaEspecial;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class ListCuentaTest extends TestCase
{
    use LogErrorsTrait;

    private $originalRequestMethod;

    public function testRestoreSpecialAccountsRequiresAdminPostAndToken(): void
    {
        $specialAccount = new CuentaEspecial();
        $this->assertTrue($specialAccount->load('BANCO'));
        $originalDescription = $specialAccount->descripcion;
        $changedDescription = 'Test modified special account';

        try {
            $this->changeDescription($specialAccount, $changedDescription);
            $this->runRestore(false, Request::METHOD_POST, true);
            $this->assertDescription($specialAccount, $changedDescription);

            $this->runRestore(true, Request::METHOD_GET, true);
            $this->assertDescription($specialAccount, $changedDescription);

            $this->runRestore(true, Request::METHOD_POST, false);
            $this->assertDescription($specialAccount, $changedDescription);

            $this->runRestore(true, Request::METHOD_POST, true);
            $this->assertNotSame($changedDescription, $this->loadDescription($specialAccount));
        } finally {
            $this->changeDescription($specialAccount, $originalDescription);
        }
    }

    protected function setUp(): void
    {
        MiniLog::clear();
        $this->originalRequestMethod = $_SERVER['REQUEST_METHOD'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->originalRequestMethod === null) {
            unset($_SERVER['REQUEST_METHOD']);
        } else {
            $_SERVER['REQUEST_METHOD'] = $this->originalRequestMethod;
        }

        $this->logErrors();
    }

    private function assertDescription(CuentaEspecial $specialAccount, string $expected): void
    {
        $this->assertSame($expected, $this->loadDescription($specialAccount));
    }

    private function changeDescription(CuentaEspecial $specialAccount, string $description): void
    {
        $specialAccount->descripcion = $description;
        $this->assertTrue($specialAccount->save());
    }

    private function loadDescription(CuentaEspecial $specialAccount): string
    {
        $this->assertTrue($specialAccount->load('BANCO'));
        return $specialAccount->descripcion;
    }

    private function runRestore(bool $admin, string $method, bool $validToken): void
    {
        $controller = new TestableListCuenta('ListCuenta', '/ListCuenta');

        $user = new User();
        $user->admin = $admin;
        $user->nick = 'test-list-cuenta';
        $controller->user = $user;

        $controller->multiRequestProtection->clearSeed();
        $controller->multiRequestProtection->addSeed($user->nick);
        $token = $validToken ? $controller->multiRequestProtection->newToken() : 'invalid-token';

        $_SERVER['REQUEST_METHOD'] = $method;
        $controller->request = new Request([
            'request' => [
                'action' => 'restore-special',
                'multireqtoken' => $token,
            ],
        ]);
        $controller->restoreSpecialAccounts();
    }
}

final class TestableListCuenta extends ListCuenta
{
    public function restoreSpecialAccounts(): void
    {
        $this->restoreSpecialAccountsAction();
    }
}
