<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2022-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Model;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Model\Page;
use FacturaScripts\Core\Model\Role;
use FacturaScripts\Core\Model\RoleAccess;
use FacturaScripts\Core\Model\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testDefaultUser()
    {
        // comprobamos que ya hay un usuario por defecto
        $user = new User();
        $this->assertEquals(1, $user->count());

        // no se puede eliminar
        foreach ($user->all() as $user) {
            $this->assertFalse($user->delete());
        }
    }

    public function testDefaultValues()
    {
        $user = new User();

        // comprobamos que los valores por defecto son correctos
        $this->assertFalse($user->admin);
        $this->assertTrue($user->enabled);
    }

    public function testCreateUser()
    {
        $user = new User();
        $user->nick = 'test1';
        $user->setPassword('test1');
        $this->assertTrue($user->save());

        // comprobamos que se ha creado el usuario
        $this->assertTrue($user->exists());

        // comprobamos la contraseña
        $this->assertNotEquals('test', $user->password);
        $this->assertTrue($user->verifyPassword('test1'));
        $this->assertFalse($user->verifyPassword('test2'));

        // eliminamos
        $this->assertTrue($user->delete());
    }

    public function testEscapeHtml()
    {
        // creamos un usuario con html en lastbrowser y lastip
        $user = new User();
        $user->nick = 'test1';
        $user->setPassword('test1');
        $user->lastbrowser = '<script>alert("test");</script>';
        $user->lastip = '<b>123456</b>';
        $this->assertTrue($user->save());

        // comprobamos que se han escapado los valores
        $this->assertEquals('&lt;script&gt;alert(&quot;test&quot;);&lt;/script&gt;', $user->lastbrowser);
        $this->assertEquals('&lt;b&gt;123456&lt;/b&gt;', $user->lastip);

        // eliminamos
        $this->assertTrue($user->delete());
    }

    public function testCantUseBadEmail()
    {
        // creamos un usuario con un email incorrecto
        $user = new User();
        $user->nick = 'test2';
        $user->setPassword('test2');
        $user->email = 'bademail';
        $this->assertFalse($user->save());
    }

    public function testCantUseBadNick()
    {
        // creamos un usuario con un nick incorrecto
        $user = new User();
        $user->nick = 'bad nick';
        $user->setPassword('password3');
        $this->assertFalse($user->save());
    }

    public function testCantUseBadAgent()
    {
        // creamos un usuario con un agente que no existe
        $user = new User();
        $user->nick = 'test4';
        $user->setPassword('password4');
        $user->codagente = 1234;
        $this->assertTrue($user->save());

        // comprobamos que no se ha asignado el agente
        $this->assertNull($user->codagente);

        // eliminamos
        $this->assertTrue($user->delete());
    }

    public function testDefaultRole()
    {
        // creamos un rol
        $role = new Role();
        $role->codrole = 'test1';
        $role->descripcion = 'test1';
        $this->assertTrue($role->save());

        // asignamos el rol por defecto
        $appSettings = new AppSettings();
        $appSettings->set('default', 'codrole', 'test1');
        $appSettings->save();

        // creamos un usuario
        $user = new User();
        $user->nick = 'test_role1';
        $user->setPassword('password1');
        $this->assertTrue($user->save());

        // comprobamos que se ha asignado el rol
        $roles = $user->getRoles();
        $this->assertCount(1, $roles);
        $this->assertEquals('test1', $roles[0]->codrole);

        // restauramos el rol por defecto
        $appSettings->set('default', 'codrole', null);
        $appSettings->save();

        // eliminamos
        $this->assertTrue($user->delete());
        $this->assertTrue($role->delete());
    }

    public function testAdminPermissions()
    {
        // añadimos una página al menú
        $page = new Page();
        $page->name = 'test5';
        $page->title = 'test5';
        $page->icon = 'fas fa-test';
        $page->menu = 'admin';
        $this->assertTrue($page->save());

        // creamos un usuario administrador
        $user = new User();
        $user->nick = 'test_admin';
        $user->setPassword('test_admin');
        $user->admin = true;
        $this->assertTrue($user->save());

        // comprobamos que tiene permisos sobre la página
        $this->assertTrue($user->can('test5'));
        $this->assertTrue($user->can('test5', 'delete'));
        $this->assertTrue($user->can('test5', 'export'));
        $this->assertTrue($user->can('test5', 'import'));
        $this->assertTrue($user->can('test5', 'update'));
        $this->assertFalse($user->can('test5', 'only-owner-data'));

        // eliminamos la página
        $this->assertTrue($page->delete());

        // comprobamos que ya no tiene permisos
        $this->assertFalse($user->can('test5'));

        // eliminamos
        $this->assertTrue($user->delete());
    }

    public function testPermissions()
    {
        // añadimos una página al menú
        $page = new Page();
        $page->name = 'test6';
        $page->title = 'test6';
        $page->icon = 'fas fa-test';
        $page->menu = 'admin';
        $this->assertTrue($page->save());

        // creamos un usuario
        $user = new User();
        $user->nick = 'test6';
        $user->setPassword('password6');
        $this->assertTrue($user->save());

        // comprobamos que no tiene roles
        $roles = $user->getRoles();
        $this->assertEmpty($roles);

        // comprobamos que no tiene permiso para la página
        $this->assertFalse($user->can('test6'));

        // creamos un rol
        $role = new Role();
        $role->codrole = 'test6';
        $role->descripcion = 'test6';
        $this->assertTrue($role->save());

        // añadimos la página al rol
        $access = new RoleAccess();
        $access->codrole = 'test6';
        $access->pagename = 'test6';
        $this->assertTrue($access->save());

        // asignamos el rol al usuario
        $this->assertTrue($user->addRole($role->codrole));

        // comprobamos que tiene permiso para la página
        $this->assertTrue($user->can('test6'));
        $this->assertTrue($user->can('test6', 'delete'));
        $this->assertTrue($user->can('test6', 'export'));
        $this->assertTrue($user->can('test6', 'import'));
        $this->assertTrue($user->can('test6', 'update'));
        $this->assertFalse($user->can('test6', 'only-owner-data'));

        // cambiamos los permisos de la página
        $access->allowdelete = false;
        $access->allowimport = false;
        $access->onlyownerdata = true;
        $this->assertTrue($access->save());

        // comprobamos que los permisos se han cambiado
        $this->assertTrue($user->can('test6'));
        $this->assertFalse($user->can('test6', 'delete'));
        $this->assertTrue($user->can('test6', 'export'));
        $this->assertFalse($user->can('test6', 'import'));
        $this->assertTrue($user->can('test6', 'update'));
        $this->assertTrue($user->can('test6', 'only-owner-data'));

        // eliminamos
        $this->assertTrue($user->delete());
        $this->assertTrue($role->delete());
    }

    public function testPermissionOnMultiRole()
    {
        // añadimos una página al menú
        $page = new Page();
        $page->name = 'test7';
        $page->title = 'test7';
        $page->icon = 'fas fa-test';
        $page->menu = 'admin';
        $this->assertTrue($page->save());

        // creamos un usuario
        $user = new User();
        $user->nick = 'test7';
        $user->setPassword('password7');
        $this->assertTrue($user->save());

        // creamos un rol
        $role1 = new Role();
        $role1->codrole = 'test7';
        $role1->descripcion = 'test7';
        $this->assertTrue($role1->save());

        // añadimos la página al rol sin permisos para eliminar ni actualizar
        $access = new RoleAccess();
        $access->codrole = 'test7';
        $access->pagename = 'test7';
        $access->allowdelete = false;
        $access->allowupdate = false;
        $this->assertTrue($access->save());

        // asignamos el rol al usuario
        $this->assertTrue($user->addRole($role1->codrole));

        // comprobamos que tiene permiso para la página
        $this->assertTrue($user->can('test7'));
        $this->assertFalse($user->can('test7', 'delete'));
        $this->assertTrue($user->can('test7', 'export'));
        $this->assertTrue($user->can('test7', 'import'));
        $this->assertFalse($user->can('test7', 'update'));
        $this->assertFalse($user->can('test7', 'only-owner-data'));

        // creamos otro rol
        $role2 = new Role();
        $role2->codrole = 'test72';
        $role2->descripcion = 'test72';
        $this->assertTrue($role2->save());

        // añadimos la página al rol con los permisos por defecto
        $this->assertTrue($role2->addPage($page->name));

        // asignamos el rol al usuario
        $this->assertTrue($user->addRole($role2->codrole));

        // comprobamos que tiene permiso para la página
        $this->assertTrue($user->can('test7'));
        $this->assertTrue($user->can('test7', 'delete'));
        $this->assertTrue($user->can('test7', 'export'));
        $this->assertTrue($user->can('test7', 'import'));
        $this->assertTrue($user->can('test7', 'update'));
        $this->assertFalse($user->can('test7', 'only-owner-data'));

        // eliminamos
        $this->assertTrue($user->delete());
        $this->assertTrue($role1->delete());
        $this->assertTrue($role2->delete());
        $this->assertTrue($page->delete());
    }
}
