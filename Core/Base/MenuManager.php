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

use FacturaScripts\Core\Base\Model;

/**
 * Gestiona el uso del menú de Facturascripts
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class MenuManager {
    /**
     * Contiene la estructura del menú para el usuario
     * @var array 
     */
    private static $menu;
    
    /**
     * Usuario para quien se ha creado el menú
     * @var string
     */
    private static $user;
    
    /**
     * Prepara y carga el menú para el usuario
     * @param string $user
     */
    public function __construct($user) {
        if (!isset(self::$menu) || (self::$user !== $user)) {
            $this->getMenu($user);
        }        
    }
 
    /**
     * Carga la estructura de menú para el usuario indicado
     * @param string $user
     * @return array
     */
    private function loadMenu($user) {
        $result = [];
        $menuValue = '';
        $submenuValue = NULL;
        $menuItem = NULL;
        
        /// Cargamos la lista de paginas para el usuario
        $pages = $this->loadPages($user);
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
    private function loadPages($user) {
        $result = [];

        $userModel = new Model\User();
        $userModel->loadFromCode($user);
        
        $pageModel = new Model\Page();
        $where = [
            'showonmenu' => TRUE           
        ];
        
        $order = [
            'lower(menu)' => 'ASC',
            'lower(submenu)' => 'ASC',
            'orden' => 'ASC',
            'title' => 'ASC'
        ];
        
        $pages = $pageModel->all($where, $order);
        switch (TRUE) {
            case FS_DEMO:
            case $userModel->admin:
                $result = $pages;
                break;

            default:
                $pageRuleModel = new Model\PageRule();
                $pageRule_list = $pageRuleModel->all(['nick' => $user]);                
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
    public function getMenu($user, $reload = FALSE) {
        if ($reload || !isset(self::$menu) || (self::$user !== $user)) {
            self::$menu = $this->loadMenu($user);
            self::$user = $user;
        }
        
        return self::$menu;
    }    
}
