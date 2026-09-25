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
use FacturaScripts\Core\Controller\EditPageOption;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Dinamic\Model\Page;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class EditPageOptionTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    /** @var Page[] */
    private $pages = [];

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
    }

    public function testBackPageComesFromTheLink(): void
    {
        // la página de vuelta debe estar registrada, y en una base de datos nueva aún no lo está
        $page = Page::find('EditUser');
        if (null === $page) {
            $page = new Page();
            $page->name = 'EditUser';
            $page->title = 'user';
            $page->menu = 'admin';
            $this->assertTrue($page->save());
            $this->pages[] = $page;
        }

        $admin = $this->getAdmin();
        $url = 'EditUser?code=user1&activetab=ListPageOption';
        $controller = $this->runController($admin, ['code' => 'ListPais', 'url' => $url], []);
        $this->assertSame($url, $controller->backPage);

        // los formularios de la página reenvían el destino
        $controller = $this->runController($admin, [], ['code' => 'ListPais', 'url' => $url]);
        $this->assertSame($url, $controller->backPage);

        // si no es una página conocida, se vuelve a la vista
        $controller = $this->runController($admin, ['code' => 'ListPais', 'url' => 'https://example.com'], []);
        $this->assertSame('ListPais', $controller->backPage);
    }

    public function testSelectedUserComesFromQuery(): void
    {
        $admin = $this->getAdmin();
        $controller = $this->runController($admin, ['code' => 'ListPais', 'nick' => 'user1'], []);
        $this->assertSame('user1', $controller->selectedUser);
    }

    public function testUserSelectorOverridesQuery(): void
    {
        // el formulario del selector se envía a la URL actual, que puede incluir el nick de partida
        $admin = $this->getAdmin();
        $query = ['code' => 'ListPais', 'nick' => 'user1'];

        $controller = $this->runController($admin, $query, ['code' => 'ListPais', 'nick' => 'user2']);
        $this->assertSame('user2', $controller->selectedUser);

        // al elegir «Todos» se envía el nick vacío
        $controller = $this->runController($admin, $query, ['code' => 'ListPais', 'nick' => '']);
        $this->assertSame('', $controller->selectedUser);
    }

    public function testUserListIncludesAdminOnlyWhenSelected(): void
    {
        $this->assertNotNull(User::find('admin'));

        // si se abre para admin, el selector debe mostrarlo y no quedarse en «Todos»
        $admin = $this->getAdmin();
        $controller = $this->runController($admin, ['code' => 'ListPais', 'nick' => 'admin'], []);
        $this->assertArrayHasKey('admin', $controller->getUserList());

        $controller = $this->runController($admin, ['code' => 'ListPais'], []);
        $this->assertArrayNotHasKey('admin', $controller->getUserList());
    }

    public function testNonAdminAlwaysEditsOwnOptions(): void
    {
        $user = $this->getRandomUser();
        $query = ['code' => 'ListPais', 'nick' => 'user1'];

        $controller = $this->runController($user, $query, ['code' => 'ListPais', 'nick' => 'user2']);
        $this->assertSame($user->nick, $controller->selectedUser);
    }

    protected function tearDown(): void
    {
        foreach ($this->pages as $page) {
            $page->delete();
        }
        $this->pages = [];

        $this->logErrors();
    }

    private function getAdmin(): User
    {
        $admin = $this->getRandomUser();
        $admin->admin = true;
        return $admin;
    }

    private function runController(User $user, array $query, array $request): EditPageOption
    {
        $controller = new EditPageOption('EditPageOption', '/EditPageOption');
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
