<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Base;

use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Model\User;
use PHPUnit\Framework\TestCase;

final class ControllerPermissionsTest extends TestCase
{
    public function testEmpty()
    {
        $permissions = new ControllerPermissions();
        $this->assertEquals(1, $permissions->accessMode);
        $this->assertFalse($permissions->allowAccess);
        $this->assertFalse($permissions->allowDelete);
        $this->assertFalse($permissions->allowExport);
        $this->assertFalse($permissions->allowImport);
        $this->assertFalse($permissions->allowUpdate);
        $this->assertFalse($permissions->onlyOwnerData);
    }

    public function testSet()
    {
        $permissions = new ControllerPermissions();
        $permissions->set(true, 99, true, true, true);
        $this->assertEquals(99, $permissions->accessMode);
        $this->assertTrue($permissions->allowAccess);
        $this->assertTrue($permissions->allowDelete);
        $this->assertFalse($permissions->allowExport);
        $this->assertFalse($permissions->allowImport);
        $this->assertTrue($permissions->allowUpdate);
        $this->assertTrue($permissions->onlyOwnerData);
    }

    public function testSetParams()
    {
        $permissions = new ControllerPermissions();
        $permissions->setParams([
            'accessMode' => 99,
            'allowAccess' => true,
            'allowDelete' => true,
            'allowExport' => true,
            'allowImport' => true,
            'allowUpdate' => true,
            'onlyOwnerData' => true
        ]);
        $this->assertEquals(99, $permissions->accessMode);
        $this->assertTrue($permissions->allowAccess);
        $this->assertTrue($permissions->allowDelete);
        $this->assertTrue($permissions->allowExport);
        $this->assertTrue($permissions->allowImport);
        $this->assertTrue($permissions->allowUpdate);
        $this->assertTrue($permissions->onlyOwnerData);
    }

    public function testUser()
    {
        // cargamos un usuario normal
        $user = new User();
        $user->nick = 'test';

        // comprobamos que tiene los permisos por defecto
        $permissions = new ControllerPermissions($user, 'Test');
        $this->assertEquals(1, $permissions->accessMode);
        $this->assertFalse($permissions->allowAccess);
        $this->assertFalse($permissions->allowDelete);
        $this->assertFalse($permissions->allowExport);
        $this->assertFalse($permissions->allowImport);
        $this->assertFalse($permissions->allowUpdate);
        $this->assertFalse($permissions->onlyOwnerData);
    }

    public function testUserAdmin()
    {
        // cargamos un usuario administrador
        $user = new User();
        $user->nick = 'test';
        $user->admin = true;

        // comprobamos que tiene todos los permisos
        $permissions = new ControllerPermissions($user, 'Test');
        $this->assertEquals(99, $permissions->accessMode);
        $this->assertTrue($permissions->allowAccess);
        $this->assertTrue($permissions->allowDelete);
        $this->assertTrue($permissions->allowExport);
        $this->assertTrue($permissions->allowImport);
        $this->assertTrue($permissions->allowUpdate);
        $this->assertFalse($permissions->onlyOwnerData);
    }
}
