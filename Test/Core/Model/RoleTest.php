<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023  Carlos García Gómez <carlos@facturascripts.com>
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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Model\Page;
use FacturaScripts\Core\Model\Role;
use FacturaScripts\Core\Model\RoleAccess;
use FacturaScripts\Core\Model\User;
use PHPUnit\Framework\TestCase;

final class RoleTest extends TestCase
{

    public function testCreateRole()
    {
        $role = new Role();
        $role->codrole = 'test1';
        $role->descripcion = 'test1';
        $this->assertTrue($role->save());

        // comprobamos que se ha creado el grupo
        $this->assertTrue($role->exists());

        // eliminamos
        $this->assertTrue($role->delete());
    }

    public function testCreateRoleWithoutCode()
    {
        $role = new Role();
        $role->descripcion = 'test without code';
        $this->assertTrue($role->save());

        // comprobamos que se ha creado el grupo
        $this->assertTrue($role->exists());

        // eliminamos
        $this->assertTrue($role->delete());
    }

    public function testRoleAccessAfterDelete()
    {
        // crear role
        $role = new Role();
        $role->descripcion = 'test without code';
        $this->assertTrue($role->save());
        $this->assertTrue($role->exists());

        // crear page
        $page = new Page();
        $page->name = 'test5';
        $page->title = 'test5';
        $page->icon = 'fas fa-test';
        $page->menu = 'admin';
        $this->assertTrue($page->save());
        $this->assertTrue($page->exists());

        // crear role access
        $rolePage = new RoleAccess();
        $rolePage->codrole = $role->codrole;
        $rolePage->pagename = $page->name;
        $this->assertTrue($rolePage->save());
        $this->assertTrue($rolePage->exists());

        // borrarmos page
        $this->assertTrue($page->delete());

        // comprobamos que sigue existiendo roleaccess
        $this->assertTrue($rolePage->exists());

        // borramos roleacceess
        $this->assertTrue($rolePage->delete());

        // borramos role
        $this->assertTrue($role->delete());
    }
}
