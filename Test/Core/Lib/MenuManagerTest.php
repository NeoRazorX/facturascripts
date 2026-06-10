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

use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Lib\MenuManager;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Session;
use FacturaScripts\Dinamic\Model\Page;
use PHPUnit\Framework\TestCase;

/**
 * Description of MenuManagerTest
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
final class MenuManagerTest extends TestCase
{
    public function testInit(): void
    {
        $menuManager = MenuManager::init();
        $this->assertInstanceOf(MenuManager::class, $menuManager);
    }

    public function testGetMenuWithoutUser(): void
    {
        // Create a real user without nick
        $user = new User();
        $user->nick = '';

        Session::set('user', $user);

        $menuManager = new MenuManager();
        $menu = $menuManager->getMenu();

        $this->assertIsArray($menu);
        $this->assertEmpty($menu);
    }

    public function testSelectPageWithEmptyData(): void
    {
        $menuManager = MenuManager::init();
        $result = $menuManager->selectPage([]);

        $this->assertSame($menuManager, $result);
    }

    public function testSelectPageWithMenuData(): void
    {
        // Setup real user with permissions
        $user = new User();
        $user->nick = 'testuser';
        $user->admin = true;

        Session::set('user', $user);

        // Clear cache to ensure fresh data
        Cache::clear();

        // Mock some pages for testing
        $mockPage1 = new Page([
            'name' => 'TestPage1',
            'title' => 'Test Page 1',
            'menu' => 'TestMenu',
            'submenu' => '',
            'showOnMenu' => true,
            'icon' => 'fas fa-test',
            'ordernum' => 100
        ]);

        $mockPage2 = new Page([
            'name' => 'TestPage2',
            'title' => 'Test Page 2',
            'menu' => 'TestMenu',
            'submenu' => 'TestSubmenu',
            'showOnMenu' => true,
            'icon' => 'fas fa-test2',
            'ordernum' => 200
        ]);

        // Cache the mock pages
        Cache::set('model-Page-Show-Menu', [$mockPage1, $mockPage2]);

        $menuManager = new MenuManager();
        $menu = $menuManager->getMenu();

        $this->assertIsArray($menu);
        $this->assertNotEmpty($menu, print_r($menu, true));
        $this->assertArrayHasKey('TestMenu', $menu);

        // Test selectPage
        $pageData = [
            'name' => 'TestPage1',
            'menu' => 'TestMenu'
        ];

        $result = $menuManager->selectPage($pageData);
        $this->assertSame($menuManager, $result);

        // Verify menu is marked as active
        $updatedMenu = $menuManager->getMenu();
        $this->assertTrue($updatedMenu['TestMenu']->active ?? false);
    }

    public function testMenuStructureWithSubmenus(): void
    {
        // Setup admin user
        $user = new User();
        $user->nick = 'admin';
        $user->admin = true;

        Session::set('user', $user);

        // Clear cache
        Cache::clear();

        // Create pages with different menu structures
        $pages = [
            new Page([
                'name' => 'Page1',
                'title' => 'Page 1',
                'menu' => 'Menu1',
                'submenu' => '',
                'showOnMenu' => true,
                'icon' => 'fas fa-page1',
                'ordernum' => 100
            ]),
            new Page([
                'name' => 'Page2',
                'title' => 'Page 2',
                'menu' => 'Menu1',
                'submenu' => 'Submenu1',
                'showOnMenu' => true,
                'icon' => 'fas fa-page2',
                'ordernum' => 200
            ]),
            new Page([
                'name' => 'Page3',
                'title' => 'Page 3',
                'menu' => 'Menu2',
                'submenu' => '',
                'showOnMenu' => true,
                'icon' => 'fas fa-page3',
                'ordernum' => 300
            ])
        ];

        Cache::set('model-Page-Show-Menu', $pages);

        $menuManager = new MenuManager();
        $menu = $menuManager->getMenu();

        // Verify menu structure
        $this->assertArrayHasKey('Menu1', $menu);
        $this->assertArrayHasKey('Menu2', $menu);

        // Verify Menu1 has both direct pages and submenu
        $this->assertArrayHasKey('Page1', $menu['Menu1']->menu);
        $this->assertArrayHasKey('Submenu1', $menu['Menu1']->menu);

        // Verify submenu structure
        $this->assertArrayHasKey('Page2', $menu['Menu1']->menu['Submenu1']->menu);

        // Verify Menu2 has direct page
        $this->assertArrayHasKey('Page3', $menu['Menu2']->menu);
    }

    public function testNonAdminUserWithLimitedAccess(): void
    {
        // Setup non-admin user
        $user = new User();
        $user->nick = 'normaluser';
        $user->admin = false;

        Session::set('user', $user);

        // Clear cache
        Cache::clear();

        // Create pages
        $pages = [
            new Page([
                'name' => 'AllowedPage',
                'title' => 'Allowed Page',
                'menu' => 'AllowedMenu',
                'submenu' => '',
                'showOnMenu' => true,
                'icon' => 'fas fa-allowed',
                'ordernum' => 100
            ]),
            new Page([
                'name' => 'DeniedPage',
                'title' => 'Denied Page',
                'menu' => 'DeniedMenu',
                'submenu' => '',
                'showOnMenu' => true,
                'icon' => 'fas fa-denied',
                'ordernum' => 200
            ])
        ];

        Cache::set('model-Page-Show-Menu', $pages);

        // Mock user access - only allow access to 'AllowedPage'
        Cache::set('model-RoleAccess-User-normaluser', ['AllowedPage']);

        $menuManager = new MenuManager();
        $menu = $menuManager->getMenu();

        // Verify only allowed page is in menu
        $this->assertIsArray($menu);
        $this->assertArrayHasKey('AllowedMenu', $menu);
        $this->assertArrayNotHasKey('DeniedMenu', $menu);
        $this->assertArrayHasKey('AllowedPage', $menu['AllowedMenu']->menu);
    }

    protected function tearDown(): void
    {
        // Clean up cache after each test
        Cache::clear();
        Session::clear();
        parent::tearDown();
    }
}
