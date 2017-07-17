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

use FacturaScripts\Core\Model as Models;

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
     * @var array 
     */
    private static $menu;

    /**
     *
     * @var Models\Page
     */
    private static $pageModel;

    /**
     * Usuario para quien se ha creado el menú.
     * @var Models\User
     */
    private static $user;

    public function setUser($user)
    {
        self::$user = $user;

        if (self::$pageModel === null) {
            self::$pageModel = new Models\Page();
        }

        if ($user !== null) {
            self::$menu = $this->loadUserMenu();
        }
    }

    public function selectPage($pageData)
    {
        $pageModel = self::$pageModel->get($pageData['name']);
        if ($pageModel === false) {
            $pageData['order'] = 100;
            $pageModel = new Models\Page($pageData);
            $pageModel->save();
        }

        if (!empty(self::$menu)) {
            /**
             * TODO: navegar por el menú y marcar como activa la página seleccionada:
             * $pageData['name']
             * @return \FacturaScripts\Core\Base\MenuItem
             */
        }
    }

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
                $result[$menuValue] = new MenuItem(ucfirst($menuValue), '#');
                $menuItem = &$result[$menuValue]->menu;
            }

            /// Control de ruptura de submenu
            if ($submenuValue !== $page->submenu) {
                $submenuValue = $page->submenu;
                $menuItem = &$result[$menuValue]->menu;
                if ($submenuValue != NULL) {
                    $menuItem[$submenuValue] = new MenuItem(ucfirst($submenuValue), '#');
                    $menuItem = &$menuItem[$submenuValue]->menu;
                }
            }

            $menuItem[$page->name] = new MenuItem($page->title, $page->url());
        }

        return $result;
    }

    /**
     * Carga la lista de páginas para el usuario
     * @param string $user
     * @return array
     */
    private function loadPages()
    {
        $result = [];
        
        $where = [
            'showonmenu' => TRUE
        ];

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
                $pageRuleModel = new Models\PageRule();
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
     * @param string $user
     * @param boolean $reload
     * @return array
     */
    public function getMenu()
    {
        return self::$menu;
    }

    /**
     * Solo para pruebas. Imprime la estructura de menú
     */
    public function printMenu()
    {
        foreach (self::$menu as $key => $value) {
            print $value->title . " (" . $value->url . ")<br />";
            foreach ($value->menu as $key2 => $value2) {
                print "--->" . $value2->title . " (" . $value2->url . ")<br />";
                foreach ($value2->menu as $key3 => $value3) {
                    print "-------->" . $value3->title . " (" . $value3->url . ")<br />";
                }
            }
        }
    }
}
