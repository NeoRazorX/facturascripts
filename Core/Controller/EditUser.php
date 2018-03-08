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
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController;
use FacturaScripts\Core\Model;

/**
 * Controller to edit a single item from the User model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class EditUser extends ExtendedController\PanelController
{

    /**
     * Load views
     */
    protected function createViews()
    {
        /// Add all views
        $this->addEditView('User', 'EditUser', 'user', 'fa-user');
        $this->addEditListView('RoleUser', 'EditRoleUser', 'roles', 'fa-address-card-o');

        /// Load values for input selects
        $this->loadHomepageValues();
        $this->loadLanguageValues();

        /// Disable column
        $this->views['EditRoleUser']->disableColumn('user', true);
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'user';
        $pagedata['icon'] = 'fa-user';
        $pagedata['menu'] = 'admin';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Load view data proedure
     *
     * @param string                      $keyView
     * @param ExtendedController\EditView $view
     */
    protected function loadData($keyView, $view)
    {
        switch ($keyView) {
            case 'EditUser':
                $code = $this->request->get('code');
                $view->loadData($code);
                break;

            case 'EditRoleUser':
                $nick = $this->getViewModelValue('EditUser', 'nick');
                $where = [new DataBaseWhere('nick', $nick)];
                $view->loadData(false, $where);
                break;
        }
    }

    /**
     * Load a list of pages where user has access that can be setted as homepage.
     */
    private function loadHomepageValues()
    {
        $user = new Model\User();
        $code = $this->request->get('code');

        $userPages = [
            ['value' => '---null---', 'title' => '------'],
        ];
        if ($user->loadFromCode($code)) {
            $userPages = $this->getUserPages($user);
        }

        $columnHomepage = $this->views['EditUser']->columnForName('homepage');
        $columnHomepage->widget->setValuesFromArray($userPages);
    }

    /**
     * Return a list of pages where user has access.
     *
     * @param Model\User $user
     *
     * @return array
     */
    private function getUserPages($user)
    {
        $pageList = [];
        if ($user->admin) {
            $pageModel = new Model\Page();
            foreach ($pageModel->all([], ['name' => 'ASC'], 0, 500) as $page) {
                if (!$page->showonmenu) {
                    continue;
                }

                $pageList[] = ['value' => $page->name, 'title' => $page->name];
            }

            return $pageList;
        }

        $roleUserModel = new Model\RoleUser();
        foreach ($roleUserModel->all([new DataBaseWhere('nick', $user->nick)]) as $roleUser) {
            foreach ($roleUser->getRoleAccess() as $roleAccess) {
                $pageList[] = ['value' => $roleAccess->pagename, 'title' => $roleAccess->pagename];
            }
        }

        return $pageList;
    }

    /**
     * Load the available language values from translator.
     */
    private function loadLanguageValues()
    {
        $columnLangCode = $this->views['EditUser']->columnForName('lang-code');
        $langs = [];
        foreach ($this->i18n->getAvailableLanguages() as $key => $value) {
            $langs[] = ['value' => $key, 'title' => $value];
        }

        /// sorting
        usort($langs, function ($objA, $objB) {
            return strcmp($objA['title'], $objB['title']);
        });

        $columnLangCode->widget->setValuesFromArray($langs);
    }
}
