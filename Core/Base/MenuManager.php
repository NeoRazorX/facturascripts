<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
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
     * @var MenuItem[]
     */
    private static $menu;

    /**
     *
     * @var bool
     */
    private static $menuActive;

    /**
     *
     * @var Model\Page
     */
    private static $pageModel;

    /**
     * Usuario para quien se ha creado el menú.
     * @var Model\User
     */
    private static $user;

    /**
     * Llamar solamente cuando se ha conectado a la base de datos.
     */
    public function init()
    {
        if (self::$pageModel === null) {
            self::$pageModel = new Model\Page();
        }

        if (self::$user !== null) {
            self::$menu = $this->loadUserMenu();
        }
    }

    /**
     * Asigna el usuario para cargar su menú.
     * @param Model\User|null $user
     */
    public function setUser($user)
    {
        self::$user = $user;
        $this->init();
    }

    /**
     * Actualiza los datos en el modelo Model\Page en base los datos
     * del getPageData() del controlador
     * @param array $pageData
     */
    public function selectPage($pageData)
    {
        $pageModel = self::$pageModel->get($pageData['name']);
        if ($pageModel === false) {
            $pageData['order'] = 100;
            $pageModel = new Model\Page($pageData);
            $pageModel->save();
        } elseif ($pageModel->menu != $pageData['menu'] || $pageModel->title != $pageData['title'] || $pageModel->icon != $pageData['icon']) {
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
     * @return \FacturaScripts\Core\Base\MenuItem
     */
    private function loadUserMenu()
    {
        $result = [];
        $menuValue = '';
        $submenuValue = NULL;
        $menuItem = NULL;

        /// Cargamos la lista de paginas para el usuario
        $pages = $this->loadPages();
        foreach ($pages as $page) {
            if ($page->menu == '') {
                continue;
            }

            /// Control de ruptura de menu
            if ($menuValue !== $page->menu) {
                $menuValue = $page->menu;
                $submenuValue = NULL;
                $result[$menuValue] = new MenuItem($menuValue, $menuValue, '#');
                $menuItem = &$result[$menuValue]->menu;
            }

            /// Control de ruptura de submenu
            if ($submenuValue !== $page->submenu) {
                $submenuValue = $page->submenu;
                $menuItem = &$result[$menuValue]->menu;
                if ($submenuValue != NULL) {
                    $menuItem[$submenuValue] = new MenuItem($submenuValue, $submenuValue, '#');
                    $menuItem = &$menuItem[$submenuValue]->menu;
                }
            }

            $menuItem[$page->name] = new MenuItem($page->name, $page->title, $page->url(), $page->icon);
        }

        return $result;
    }

    /**
     * Carga la lista de páginas para el usuario
     * @return Model\Page[]
     */
    private function loadPages()
    {
        $result = [];

        $where = [];
        $where[] = new DataBase\DataBaseWhere('showonmenu', TRUE);

        $order = [
            'lower(menu)' => 'ASC',
            'lower(submenu)' => 'ASC',
            'orden' => 'ASC',
            'title' => 'ASC'
        ];

        $pages = self::$pageModel->all($where, $order);
        switch (TRUE) {
            case self::$user->admin:
                $result = $pages;
                break;

            default:
                $pageRuleModel = new Model\PageRule();
                $pageRule_list = $pageRuleModel->all(['nick' => self::$user]);
                foreach ($pages as $page) {
                    foreach ($pageRule_list as $pageRule) {
                        if ($page->name == $pageRule->pagename) {
                            $result[] = $page;
                            // TODO: Eliminar del array de Reglas la pagina añadida
                            break;
                        }
                    }
                }
                break;
        }

        return $result;
    }

    /**
     * Devuelve el menú del usuario, el conjunto de páginas a las que tiene acceso.
     * @return array
     */
    public function getMenu()
    {
        return self::$menu;
    }
}
