<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos García Gómez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Test\Core\Model;

use FacturaScripts\Core\Model\Page;
use FacturaScripts\Core\Model\Role;
use FacturaScripts\Core\Model\RoleAccess;
use FacturaScripts\Core\Model\RoleUser;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class RoleTest extends TestCase
{
    use LogErrorsTrait;

    public function testCreateRole(): void
    {
        $role = new Role();
        $role->codrole = 'test1';
        $role->descripcion = 'test1';
        $this->assertTrue($role->save());

        // comprobamos que se ha creado el grupo
        $this->assertTrue($role->exists());

        // eliminamos
        $this->assertTrue($role->delete());

        // comprobamos que ya no existe
        $this->assertFalse($role->exists());
    }

    public function testCreateRoleWithoutCode(): void
    {
        $role = new Role();
        $role->descripcion = 'test without code';
        $this->assertTrue($role->save());

        // comprobamos que se ha creado el grupo
        $this->assertTrue($role->exists());

        // eliminamos
        $this->assertTrue($role->delete());

        // comprobamos que ya no existe
        $this->assertFalse($role->exists());
    }

    public function testRoleAccessAfterDelete(): void
    {
        // crear role
        $role = new Role();
        $role->descripcion = 'test without code';
        $this->assertTrue($role->save());

        // crear page
        $page = new Page();
        $page->name = 'test5';
        $page->title = 'test5';
        $page->icon = 'fas fa-test';
        $page->menu = 'admin';
        $this->assertTrue($page->save());

        // crear role access
        $rolePage = new RoleAccess();
        $rolePage->codrole = $role->codrole;
        $rolePage->pagename = $page->name;
        $this->assertTrue($rolePage->save());
        $this->assertTrue($rolePage->exists());

        // borramos page
        $this->assertTrue($page->delete());

        // comprobamos que sigue existiendo rolePage
        $this->assertTrue($rolePage->exists());

        // borramos role
        $this->assertTrue($role->delete());

        // comprobamos que ya no existe rolePage
        $this->assertFalse($rolePage->exists());
    }

    public function testRoleUser(): void
    {
        // crear role
        $role = new Role();
        $role->descripcion = 'test without code';
        $this->assertTrue($role->save());

        // crear user
        $user = new User();
        $user->nick = 'test5';
        $user->setPassword('test5');
        $this->assertTrue($user->save());

        // asignamos role al user
        $roleUser = new RoleUser();
        $roleUser->codrole = $role->codrole;
        $roleUser->nick = $user->nick;
        $this->assertTrue($roleUser->save());
        $this->assertTrue($roleUser->exists());

        // borramos role
        $this->assertTrue($role->delete());

        // comprobamos que ya no existe roleUser
        $this->assertFalse($roleUser->exists());

        // borramos user
        $this->assertTrue($user->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
