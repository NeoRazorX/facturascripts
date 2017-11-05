<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Base;

use FacturaScripts\Core\Model;

/**
 * Gestiona el uso del menú de Facturascripts
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class MenuManager
{
    /**
     * Contiene la estructura del menú para el usuario.
     *
     * @var MenuItem[]
     */
    private static $menu;

    /**
     * Es true si es el menú activo, sino false
     *
     * @var bool
     */
    private static $menuActive;

    /**
     * Controlador asociado a la página
     *
     * @var Model\Page
     */
    private static $pageModel;

    /**
     * Usuario para quien se ha creado el menú.
     *
     * @var Model\User|false
     */
    private static $user = false;

    /**
     * Llamar solamente cuando se ha conectado a la base de datos.
     */
    public function init()
    {
        if (self::$pageModel === null) {
            self::$pageModel = new Model\Page();
        }

        if (self::$user !== false) {
            self::$menu = $this->loadUserMenu();
        }
    }

    /**
     * Asigna el usuario para cargar su menú.
     *
     * @param Model\User|false $user
     */
    public function setUser($user)
    {
        self::$user = $user;
        $this->init();
    }

    /**
     * Devuelve si la página debe ser guardada
     *
     * @param Model\Page $pageModel
     * @param array      $pageData
     *
     * @return bool
     */
    private function pageNeedSave($pageModel, $pageData)
    {
        return ($pageModel->menu != $pageData['menu']) || ($pageModel->title != $pageData['title']) || ($pageModel->icon != $pageData['icon']) || ($pageModel->showonmenu != $pageData['showonmenu']);
    }

    /**
     * Actualiza los datos en el modelo Model\Page en base los datos
     * del getPageData() del controlador
     *
     * @param array $pageData
     */
    public function selectPage($pageData)
    {
        $pageModel = self::$pageModel->get($pageData['name']);
        if ($pageModel === false) {
            $pageData['order'] = 100;
            $pageModel = new Model\Page($pageData);
            $pageModel->save();
        } elseif ($this->pageNeedSave($pageModel, $pageData)) {
            $pageModel->menu = $pageData['menu'];
            $pageModel->submenu = $pageData['submenu'];
            $pageModel->showonmenu = $pageData['showonmenu'];
            $pageModel->title = $pageData['title'];
            $pageModel->icon = $pageData['icon'];
            $pageModel->orden = $pageData['orden'];
            $pageModel->save();
        }

        if (self::$menu !== null && self::$menuActive !== true) {
            $this->setActiveMenu($pageModel);
            self::$menuActive = true;
        }
    }

    /**
     * Asignar menú activo
     *
     * @param Model\Page $pageModel
     */
    private function setActiveMenu($pageModel)
    {
        foreach (self::$menu as $key => $menuItem) {
            if ($menuItem->name == $pageModel->menu) {
                self::$menu[$key]->active = true;
                $this->setActiveMenuItem(self::$menu[$key]->menu, $pageModel);
                break;
            }
        }
    }

    /**
     * Asignar elemento de menú activo
     *
     * @param MenuItem[] $menu
     * @param Model\Page $pageModel
     */
    private function setActiveMenuItem(&$menu, $pageModel)
    {
        foreach ($menu as $key => $menuItem) {
            if ($menuItem->name == $pageModel->name) {
                $menu[$key]->active = true;
                break;
            }
        }
    }

    /**
     * Carga la estructura de menú para el usuario
     *
     * @return array
     */
    private function loadUserMenu()
    {
        $result = [];
        $menuValue = '';
        $submenuValue = null;
        $menuItem = null;
        $i18n = new Translator();

        /// Cargamos la lista de paginas para el usuario
        $pages = $this->loadPages();
        $sortMenu = [];
        foreach ($pages as $page) {
            if ($page->menu == '') {
                continue;
            }

            /// Control de ruptura de menu
            if ($menuValue !== $page->menu) {
                $menuValue = $page->menu;
                $submenuValue = null;
                $result[$menuValue] = new MenuItem($menuValue, $i18n->trans($menuValue), '#');
                $menuItem = &$result[$menuValue]->menu;
                $sortMenu[$menuValue][] = $result[$menuValue]->title;
            }

            /// Control de ruptura de submenu
            if ($submenuValue !== $page->submenu) {
                $submenuValue = $page->submenu;
                $menuItem = &$result[$menuValue]->menu;
                if ($submenuValue != null) {
                    $menuItem[$submenuValue] = new MenuItem($submenuValue, $i18n->trans($submenuValue), '#');
                    $menuItem = &$menuItem[$submenuValue]->menu;
                }
            }
            $menuItem[$page->name] = new MenuItem($page->name, $i18n->trans($page->title), $page->url(), $page->icon);
        }

        // Reorder menu by title
        array_multisort($sortMenu, SORT_ASC, $result);

        // Reorder submenu by title
        foreach ($result as $posM => $menu) {
            $sortSubMenu = [];
            foreach ($menu->menu as $submenu) {
                $sortSubMenu[$submenu->name] = $submenu->title;
            }
            array_multisort($sortSubMenu, SORT_ASC, $result[$posM]->menu);
        }

        return $result;
    }

    /**
     * Carga la lista de páginas para el usuario
     *
     * @return Model\Page[]
     */
    private function loadPages()
    {
        $where = [new DataBase\DataBaseWhere('showonmenu', true)];
        $order = [
            'lower(menu)' => 'ASC',
            'lower(submenu)' => 'ASC',
            'orden' => 'ASC',
            'title' => 'ASC',
        ];

        $pages = self::$pageModel->all($where, $order);
        if (self::$user && self::$user->admin) {
            return $pages;
        }

        $result = [];
        $pageRuleModel = new Model\PageRule();
        $pageRule_list = $pageRuleModel->all(['nick' => self::$user->nick]);
        foreach ($pages as $page) {
            foreach ($pageRule_list as $pageRule) {
                if ($page->name == $pageRule->pagename) {
                    $result[] = $page;
                    // TODO: Delete the added page from the rule set
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Devuelve el menú del usuario, el conjunto de páginas a las que tiene acceso.
     *
     * @return array
     */
    public function getMenu()
    {
        return self::$menu;
    }
}
