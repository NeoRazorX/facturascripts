<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Lib;

use FacturaScripts\Core\Lib\ControllerPermissions;
use FacturaScripts\Core\Model\User;
use PHPUnit\Framework\TestCase;

final class ControllerPermissionsTest extends TestCase
{
    public function testDefaultConstructor(): void
    {
        $permissions = new ControllerPermissions();

        $this->assertEquals(1, $permissions->accessMode, 'Default access mode should be 1');
        $this->assertFalse($permissions->allowAccess, 'Default allowAccess should be false');
        $this->assertFalse($permissions->allowDelete, 'Default allowDelete should be false');
        $this->assertFalse($permissions->allowExport, 'Default allowExport should be false');
        $this->assertFalse($permissions->allowImport, 'Default allowImport should be false');
        $this->assertFalse($permissions->allowUpdate, 'Default allowUpdate should be false');
        $this->assertFalse($permissions->onlyOwnerData, 'Default onlyOwnerData should be false');
    }

    public function testConstructorWithNullParameters(): void
    {
        $permissions = new ControllerPermissions(null, null);

        $this->assertEquals(1, $permissions->accessMode);
        $this->assertFalse($permissions->allowAccess);
        $this->assertFalse($permissions->allowDelete);
    }

    public function testConstructorWithEmptyPageName(): void
    {
        $user = new User();
        $user->nick = 'test';

        $permissions = new ControllerPermissions($user, '');

        $this->assertEquals(1, $permissions->accessMode);
        $this->assertFalse($permissions->allowAccess);
    }

    public function testSetMethod(): void
    {
        $permissions = new ControllerPermissions();

        $permissions->set(true, 99, true, true, true);

        $this->assertEquals(99, $permissions->accessMode, 'Access mode should be set to 99');
        $this->assertTrue($permissions->allowAccess, 'Access should be allowed');
        $this->assertTrue($permissions->allowDelete, 'Delete should be allowed');
        $this->assertFalse($permissions->allowExport, 'Export should remain false');
        $this->assertFalse($permissions->allowImport, 'Import should remain false');
        $this->assertTrue($permissions->allowUpdate, 'Update should be allowed');
        $this->assertTrue($permissions->onlyOwnerData, 'Only owner data should be true');
    }

    public function testSetMethodWithDefaultOnlyOwner(): void
    {
        $permissions = new ControllerPermissions();

        $permissions->set(true, 50, false, true);

        $this->assertEquals(50, $permissions->accessMode);
        $this->assertTrue($permissions->allowAccess);
        $this->assertFalse($permissions->allowDelete);
        $this->assertTrue($permissions->allowUpdate);
        $this->assertFalse($permissions->onlyOwnerData, 'Only owner data should be false by default');
    }

    public function testSetParamsWithAllProperties(): void
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

    public function testSetParamsWithPartialProperties(): void
    {
        $permissions = new ControllerPermissions();

        $permissions->setParams([
            'allowAccess' => true,
            'allowUpdate' => true
        ]);

        $this->assertEquals(1, $permissions->accessMode, 'Access mode should remain default');
        $this->assertTrue($permissions->allowAccess);
        $this->assertFalse($permissions->allowDelete, 'Delete should remain false');
        $this->assertTrue($permissions->allowUpdate);
    }

    public function testSetParamsWithInvalidProperties(): void
    {
        $permissions = new ControllerPermissions();

        $permissions->setParams([
            'invalidProperty' => true,
            'allowAccess' => true,
            'anotherInvalid' => 'value'
        ]);

        $this->assertTrue($permissions->allowAccess, 'Valid properties should be set');
        $this->assertFalse($permissions->allowDelete, 'Other properties should remain unchanged');
    }

    public function testSetParamsWithStringAccessMode(): void
    {
        $permissions = new ControllerPermissions();

        $permissions->setParams([
            'accessMode' => '50',
            'allowAccess' => 'true',
            'allowDelete' => '1'
        ]);

        $this->assertEquals(50, $permissions->accessMode, 'String accessMode should be cast to int');
        $this->assertTrue($permissions->allowAccess, 'String boolean should be cast properly');
        $this->assertTrue($permissions->allowDelete, 'String "1" should be cast to true');
    }

    public function testNormalUserWithoutRoles(): void
    {
        $user = new User();
        $user->nick = 'test';
        $user->admin = false;

        $permissions = new ControllerPermissions($user, 'TestPage');

        $this->assertEquals(1, $permissions->accessMode, 'Normal user should have default access mode');
        $this->assertFalse($permissions->allowAccess, 'User without roles should not have access');
        $this->assertFalse($permissions->allowDelete);
        $this->assertFalse($permissions->allowExport);
        $this->assertFalse($permissions->allowImport);
        $this->assertFalse($permissions->allowUpdate);
        $this->assertFalse($permissions->onlyOwnerData);
    }

    public function testAdminUserPermissions(): void
    {
        $user = new User();
        $user->nick = 'admin';
        $user->admin = true;

        $permissions = new ControllerPermissions($user, 'AnyPage');

        $this->assertEquals(99, $permissions->accessMode, 'Admin should have access mode 99');
        $this->assertTrue($permissions->allowAccess, 'Admin should have access');
        $this->assertTrue($permissions->allowDelete, 'Admin should be able to delete');
        $this->assertTrue($permissions->allowExport, 'Admin should be able to export');
        $this->assertTrue($permissions->allowImport, 'Admin should be able to import');
        $this->assertTrue($permissions->allowUpdate, 'Admin should be able to update');
        $this->assertFalse($permissions->onlyOwnerData, 'Admin should see all data, not just owner data');
    }

    public function testAdminUserWithDifferentPages(): void
    {
        $user = new User();
        $user->nick = 'admin';
        $user->admin = true;

        $pages = ['Dashboard', 'Settings', 'Users', 'Reports'];

        foreach ($pages as $page) {
            $permissions = new ControllerPermissions($user, $page);

            $this->assertEquals(99, $permissions->accessMode, "Admin should have full access to {$page}");
            $this->assertTrue($permissions->allowAccess, "Admin should access {$page}");
            $this->assertTrue($permissions->allowDelete);
            $this->assertTrue($permissions->allowExport);
            $this->assertTrue($permissions->allowImport);
            $this->assertTrue($permissions->allowUpdate);
            $this->assertFalse($permissions->onlyOwnerData);
        }
    }

    public function testPermissionsCombination(): void
    {
        $permissions = new ControllerPermissions();

        // Set initial permissions
        $permissions->set(true, 10, false, true, false);

        $this->assertEquals(10, $permissions->accessMode);
        $this->assertTrue($permissions->allowAccess);
        $this->assertFalse($permissions->allowDelete);
        $this->assertTrue($permissions->allowUpdate);

        // Override with setParams
        $permissions->setParams([
            'allowDelete' => true,
            'allowExport' => true,
            'accessMode' => 20
        ]);

        $this->assertEquals(20, $permissions->accessMode, 'Access mode should be updated');
        $this->assertTrue($permissions->allowAccess, 'Access should remain true');
        $this->assertTrue($permissions->allowDelete, 'Delete should now be true');
        $this->assertTrue($permissions->allowExport, 'Export should now be true');
        $this->assertTrue($permissions->allowUpdate, 'Update should remain true');
    }

    public function testSetParamsWithZeroValues(): void
    {
        $permissions = new ControllerPermissions();
        $permissions->set(true, 99, true, true, true);

        // Test that zero/false values properly override existing true values
        $permissions->setParams([
            'allowDelete' => 0,
            'allowUpdate' => false,
            'allowAccess' => '0'
        ]);

        $this->assertFalse($permissions->allowDelete, 'Zero should be cast to false');
        $this->assertFalse($permissions->allowUpdate, 'False should remain false');
        $this->assertFalse($permissions->allowAccess, 'String "0" should be cast to false');
        $this->assertEquals(99, $permissions->accessMode, 'Access mode should remain unchanged');
    }

    public function testConstantsExist(): void
    {
        // Use reflection to check if constants are defined
        $reflection = new \ReflectionClass(ControllerPermissions::class);
        $constants = $reflection->getConstants();

        $this->assertArrayHasKey('DEFAULT_ACCESS_MODE', $constants, 'DEFAULT_ACCESS_MODE constant should exist');
        $this->assertArrayHasKey('ADMIN_ACCESS_MODE', $constants, 'ADMIN_ACCESS_MODE constant should exist');
        $this->assertEquals(1, $constants['DEFAULT_ACCESS_MODE']);
        $this->assertEquals(99, $constants['ADMIN_ACCESS_MODE']);
    }
}
