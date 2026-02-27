<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2022-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Page;
use FacturaScripts\Core\Model\Role;
use FacturaScripts\Core\Model\RoleAccess;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    use LogErrorsTrait;

    public function testClear(): void
    {
        $user = new User();
        $this->assertFalse($user->admin);
        $this->assertNull($user->email);
        $this->assertTrue($user->enabled);
        $this->assertNull($user->lastactivity);
        $this->assertNull($user->lastbrowser);
        $this->assertNull($user->lastip);
        $this->assertNull($user->logkey);
        $this->assertNull($user->nick);
        $this->assertNull($user->password);
        $this->assertFalse($user->two_factor_enabled);
        $this->assertNull($user->two_factor_secret_key);
    }

    public function testDefaultUser(): void
    {
        // comprobamos que ya hay un usuario por defecto
        $user = new User();
        $this->assertEquals(1, $user->count());

        // no se puede eliminar
        foreach ($user->all() as $user) {
            $this->assertFalse($user->delete());
        }
    }

    public function testDefaultValues(): void
    {
        $user = new User();

        // comprobamos que los valores por defecto son correctos
        $this->assertFalse($user->admin);
        $this->assertTrue($user->enabled);
    }

    public function testCreateUser(): void
    {
        $user = new User();
        $user->nick = 'test1';
        $user->setPassword('test9876');
        $this->assertTrue($user->save());

        // comprobamos que se ha creado el usuario
        $this->assertTrue($user->exists());

        // comprobamos la contraseña
        $this->assertNotEquals('test', $user->password);
        $this->assertTrue($user->verifyPassword('test9876'));
        $this->assertFalse($user->verifyPassword('test6789'));

        // eliminamos
        $this->assertTrue($user->delete());
    }

    public function testEscapeHtml(): void
    {
        // creamos un usuario con html en lastbrowser y lastip
        $user = new User();
        $user->nick = 'test1';
        $user->setPassword('test1010');
        $user->lastbrowser = '<script>alert("test");</script>';
        $user->lastip = '<b>123456</b>';
        $this->assertTrue($user->save());

        // comprobamos que se han escapado los valores
        $this->assertEquals('&lt;script&gt;alert(&quot;test&quot;);&lt;/script&gt;', $user->lastbrowser);
        $this->assertEquals('&lt;b&gt;123456&lt;/b&gt;', $user->lastip);

        // eliminamos
        $this->assertTrue($user->delete());
    }

    public function testCantUseBadEmail(): void
    {
        // creamos un usuario con un email incorrecto
        $user = new User();
        $user->nick = 'test2';
        $user->setPassword('test2345');
        $user->email = 'bademail';
        $this->assertFalse($user->save());
    }

    public function testCantUseBadNick(): void
    {
        // creamos un usuario con un nick incorrecto
        $user = new User();
        $user->nick = 'bad nick';
        $user->setPassword('password3456');
        $this->assertFalse($user->save());
    }

    public function testCantUseBadAgent(): void
    {
        // creamos un usuario con un agente que no existe
        $user = new User();
        $user->nick = 'test4';
        $user->setPassword('password4567');
        $user->codagente = 1234;
        $this->assertTrue($user->save());

        // comprobamos que no se ha asignado el agente
        $this->assertNull($user->codagente);

        // eliminamos
        $this->assertTrue($user->delete());
    }

    public function testPassword(): void
    {
        // creamos un usuario
        $user = new User();
        $user->nick = 'test_password';
        $this->assertTrue($user->setPassword('password5678'));
        $this->assertTrue($user->save());

        // comprobamos que se ha encriptado la contraseña
        $this->assertNotEquals('password5678', $user->password);

        // validamos la contraseña
        $this->assertTrue($user->verifyPassword('password5678'));
        $this->assertFalse($user->verifyPassword('password6789'));

        // cambiamos la contraseña
        $this->assertTrue($user->setPassword('password-789'));
        $this->assertTrue($user->save());

        // validamos la nueva contraseña
        $this->assertTrue($user->verifyPassword('password-789'));
        $this->assertFalse($user->verifyPassword('password8'));

        // intentamos poner una contraseña débil
        $this->assertFalse($user->setPassword('pass'));
        $this->assertFalse($user->setPassword('password'));
        $this->assertFalse($user->setPassword('password-test'));
        $this->assertFalse($user->setPassword('123'));
        $this->assertFalse($user->setPassword('12345678'));

        // comprobamos que la contraseña no ha cambiado
        $this->assertTrue($user->verifyPassword('password-789'));

        // eliminamos
        $this->assertTrue($user->delete());
    }

    public function testNewPassword(): void
    {
        // creamos un usuario
        $user = new User();
        $user->nick = 'test_new_password';
        $user->setPassword('password-012');
        $this->assertTrue($user->save());

        // probamos 2 contraseñas mal
        $user->newPassword = 'password1234';
        $user->newPassword2 = 'password2345';
        $this->assertFalse($user->save());

        // probamos 2 contraseñas iguales
        $user->newPassword = 'password-8765';
        $user->newPassword2 = 'password-8765';
        $this->assertTrue($user->save());

        // comprobamos que se ha encriptado la contraseña
        $this->assertNotEquals('password-8765', $user->password);

        // validamos la contraseña
        $this->assertTrue($user->verifyPassword('password-8765'));
        $this->assertFalse($user->verifyPassword('password-9999'));

        // eliminamos
        $this->assertTrue($user->delete());
    }

    public function testLogKey(): void
    {
        // creamos un usuario
        $user = new User();
        $user->nick = 'test_log_key';
        $user->setPassword('password9876');
        $this->assertTrue($user->save());

        // guardamos la clave
        $logKey = $user->logkey;

        // registramos la actividad
        $newLogKey = $user->newLogkey('12.34.56.78', 'Mozilla/5.0');

        // comprobamos que se ha guardado la clave
        $this->assertNotNull($user->logkey);
        $this->assertNotEmpty($user->logkey);
        $this->assertNotEquals($logKey, $user->logkey);
        $this->assertEquals($newLogKey, $user->logkey);
        $this->assertEquals('12.34.56.78', $user->lastip);
        $this->assertEquals('Mozilla/5.0', $user->lastbrowser);

        // verificamos la clave
        $this->assertTrue($user->verifyLogkey($newLogKey));
        $this->assertFalse($user->verifyLogkey('1234'));

        // eliminamos
        $this->assertTrue($user->delete());
    }

    public function testDefaultRole(): void
    {
        // creamos un rol
        $role = new Role();
        $role->codrole = 'test1';
        $role->descripcion = 'test1';
        $this->assertTrue($role->save());

        // asignamos el rol por defecto
        Tools::settingsSet('default', 'codrole', 'test1');
        Tools::settingsSave();

        // creamos un usuario
        $user = new User();
        $user->nick = 'test_role1';
        $user->setPassword('password101');
        $this->assertTrue($user->save());

        // comprobamos que se ha asignado el rol
        $roles = $user->getRoles();
        $this->assertCount(1, $roles);
        $this->assertEquals('test1', $roles[0]->codrole);

        // restauramos el rol por defecto
        Tools::settingsSet('default', 'codrole', null);
        Tools::settingsSave();

        // eliminamos
        $this->assertTrue($user->delete());
        $this->assertTrue($role->delete());
    }

    public function testAdminPermissions(): void
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
        $user->setPassword('test_admin2');
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

    public function testPermissions(): void
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
        $user->setPassword('password678');
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

    public function testPermissionOnMultiRole(): void
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
        $user->setPassword('password789');
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

    public function testAddRole(): void
    {
        // creamos un rol
        $role = new Role();
        $role->codrole = 'test_add_role';
        $role->descripcion = 'Test Add Role';
        $this->assertTrue($role->save());

        // creamos un usuario
        $user = new User();
        $user->nick = 'test_add_user';
        $user->setPassword('password123');
        $this->assertTrue($user->save());

        // comprobamos que el usuario no tiene roles inicialmente
        $this->assertEmpty($user->getRoles());

        // añadimos el rol al usuario
        $this->assertTrue($user->addRole($role->codrole));

        // comprobamos que el usuario ahora tiene el rol
        $roles = $user->getRoles();
        $this->assertCount(1, $roles);
        $this->assertEquals('test_add_role', $roles[0]->codrole);

        // intentamos añadir el mismo rol de nuevo (debe retornar true pero no duplicar)
        $this->assertTrue($user->addRole($role->codrole));
        $roles = $user->getRoles();
        $this->assertCount(1, $roles);

        // intentamos añadir un rol que no existe
        $this->assertFalse($user->addRole('nonexistent_role'));

        // intentamos añadir un rol vacío
        $this->assertFalse($user->addRole(''));
        $this->assertFalse($user->addRole(null));

        // eliminamos
        $this->assertTrue($user->delete());
        $this->assertTrue($role->delete());
    }

    public function testRemoveRole(): void
    {
        // creamos dos roles
        $role1 = new Role();
        $role1->codrole = 'test_remove_role1';
        $role1->descripcion = 'Test Remove Role 1';
        $this->assertTrue($role1->save());

        $role2 = new Role();
        $role2->codrole = 'test_remove_role2';
        $role2->descripcion = 'Test Remove Role 2';
        $this->assertTrue($role2->save());

        // creamos un usuario
        $user = new User();
        $user->nick = 'test_remove_user';
        $user->setPassword('password456');
        $this->assertTrue($user->save());

        // añadimos ambos roles al usuario
        $this->assertTrue($user->addRole($role1->codrole));
        $this->assertTrue($user->addRole($role2->codrole));

        // comprobamos que el usuario tiene ambos roles
        $roles = $user->getRoles();
        $this->assertCount(2, $roles);

        // eliminamos un rol
        $this->assertTrue($user->removeRole($role1->codrole));

        // comprobamos que solo queda un rol
        $roles = $user->getRoles();
        $this->assertCount(1, $roles);
        $this->assertEquals('test_remove_role2', $roles[0]->codrole);

        // intentamos eliminar un rol que el usuario no tiene
        $this->assertFalse($user->removeRole($role1->codrole));

        // intentamos eliminar un rol que no existe
        $this->assertFalse($user->removeRole('nonexistent_role'));

        // intentamos eliminar un rol vacío
        $this->assertFalse($user->removeRole(''));
        $this->assertFalse($user->removeRole(null));

        // eliminamos el rol restante
        $this->assertTrue($user->removeRole($role2->codrole));

        // comprobamos que el usuario no tiene roles
        $this->assertEmpty($user->getRoles());

        // eliminamos
        $this->assertTrue($user->delete());
        $this->assertTrue($role1->delete());
        $this->assertTrue($role2->delete());
    }

    public function testAddRoleUpdatesHomepage(): void
    {
        // creamos una página
        $page = new Page();
        $page->name = 'ListTest';
        $page->title = 'List Test';
        $page->icon = 'fas fa-test';
        $page->menu = 'admin';
        $this->assertTrue($page->save());

        // creamos un rol
        $role = new Role();
        $role->codrole = 'test_homepage_role';
        $role->descripcion = 'Test Homepage Role';
        $this->assertTrue($role->save());

        // añadimos la página al rol
        $this->assertTrue($role->addPage($page->name));

        // creamos un usuario sin homepage
        $user = new User();
        $user->nick = 'test_homepage_user';
        $user->setPassword('password789');
        $user->homepage = null;
        $this->assertTrue($user->save());

        // comprobamos que no tiene homepage
        $this->assertEmpty($user->homepage);

        // añadimos el rol al usuario
        $this->assertTrue($user->addRole($role->codrole));

        // recargamos el usuario para obtener los cambios
        $user->loadFromCode($user->nick);

        // comprobamos que se ha establecido la homepage
        $this->assertEquals('ListTest', $user->homepage);

        // eliminamos
        $this->assertTrue($user->delete());
        $this->assertTrue($role->delete());
        $this->assertTrue($page->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
