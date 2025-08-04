<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib;

use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Translator;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Lib\MenuItem;
use FacturaScripts\Dinamic\Model\Page;

class MenuManager
{
    /**
     * Contains the structure of the menu for the user.
     *
     * @var MenuItem[]
     */
    private static $menu;

    /**
     * True when there is a menu active. Only for optimization purpose.
     *
     * @var bool
     */
    private static $menuActive;

    public function __construct()
    {
        if (Session::user()->nick) {
            $this->loadUserMenu(Session::user());
        } else {
            self::$menu = [];
        }
    }

    public function getMenu(): array
    {
        return self::$menu ?? [];
    }

    public static function init(): self
    {
        return new self();
    }

    public function selectPage(array $data): self
    {
        if (empty($data)) {
            return $this;
        }

        if (self::$menu !== null && self::$menuActive !== true) {
            $this->setActiveMenu($data);
            self::$menuActive = true;
        }

        return $this;
    }

    protected function getAllPages(): array
    {
        return Cache::remember('model-Page-Show-Menu', function () {
            $whereShowOnMenu = [Where::eq('showOnMenu', true)];
            $orderBy = [
                'lower(menu)' => 'ASC',
                'lower(submenu)' => 'ASC',
                'ordernum' => 'ASC',
                'lower(title)' => 'ASC'
            ];
            return Page::all($whereShowOnMenu, $orderBy);
        });
    }

    protected function getUserAccess(User $user): array
    {
        $cacheKey = 'model-RoleAccess-User-' . $user->id();
        return Cache::remember($cacheKey, function () use ($user) {
            $accessList = [];
            foreach ($user->getRoles() as $role) {
                foreach ($role->getAccesses() as $access) {
                    $accessList[] = $access->pagename;
                }
            }

            return array_unique($accessList);
        });
    }

    protected function loadUserMenu(User $user): void
    {
        // cargamos todas las páginas del menú
        $allPages = $this->getAllPages();

        if (!$user->admin) {
            // ahora quitamos las que no tiene acceso el usuario
            $userAccess = $this->getUserAccess($user);
            $allPages = array_filter($allPages, function (Page $page) use ($userAccess) {
                return in_array($page->name, $userAccess);
            });
        }

        // ahora agrupamos las páginas por menú y submenú
        self::$menu = [];
        $menuValue = null;
        $submenuValue = null;
        $menuItem = null;
        $i18n = new Translator();

        foreach ($allPages as $page) {
            if (empty($page->menu)) {
                continue;
            }

            // Menu break control
            if ($menuValue !== $page->menu) {
                $menuValue = $page->menu;
                $submenuValue = null;
                self::$menu[$menuValue] = new MenuItem($menuValue, $i18n->trans($menuValue), '#');
                $menuItem = &self::$menu[$menuValue]->menu;
            }

            // Submenu break control
            if ($submenuValue !== $page->submenu) {
                $submenuValue = $page->submenu;
                $menuItem = &self::$menu[$menuValue]->menu;
                if (!empty($submenuValue)) {
                    $menuItem[$submenuValue] = new MenuItem($submenuValue, $i18n->trans($submenuValue), '#');
                    $menuItem = &$menuItem[$submenuValue]->menu;
                }
            }
            $menuItem[$page->name] = new MenuItem($page->name, $i18n->trans($page->title), $page->url(), $page->icon);
        }

        // sort the menu
        $this->sortMenu(self::$menu);
    }

    /**
     * Set the active menu.
     *
     * @param array $data
     */
    protected function setActiveMenu(array $data): void
    {
        foreach (self::$menu as $key => $menuItem) {
            if ($menuItem->name === $data['menu']) {
                self::$menu[$key]->active = true;
                $this->setActiveMenuItem(self::$menu[$key]->menu, $data);
                break;
            }
        }
    }

    /**
     * Assign active menu item.
     *
     * @param MenuItem[] $menu
     * @param array $data
     */
    protected function setActiveMenuItem(array &$menu, array $data): void
    {
        foreach ($menu as $key => $menuItem) {
            if ($menuItem->name === $data['name']) {
                $menu[$key]->active = true;
                break;
            }

            if (!empty($data['submenu']) && !empty($menuItem->menu) && $menuItem->name === $data['submenu']) {
                $menu[$key]->active = true;
                $this->setActiveMenuItem($menu[$key]->menu, $data);
                break;
            }
        }
    }

    protected function sortMenu(array &$result): array
    {
        // sort this menu
        uasort($result, function ($menu1, $menu2) {
            return strcasecmp($menu1->title, $menu2->title);
        });

        // sort submenus
        foreach ($result as $key => $value) {
            if (!empty($value->menu)) {
                $result[$key]->menu = $this->sortMenu($value->menu);
            }
        }

        return $result;
    }
}
