<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Dinamic\Model\Almacen;
use FacturaScripts\Dinamic\Model\Page;
use FacturaScripts\Dinamic\Model\RoleUser;
use FacturaScripts\Dinamic\Model\User;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Controller to edit a single item from the User model
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class EditUser extends EditController
{

    /**
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'User';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'user';
        $data['icon'] = 'fas fa-user-circle';
        return $data;
    }

    /**
     * 
     * @return bool
     */
    private function allowUpdate()
    {
        if ($this->request->request->get('code', '') === $this->user->nick) {
            /**
             * Prevent the user from deactivating or becoming an administrator.
             */
            if ($this->user->admin != (bool) $this->request->request->get('admin')) {
                return false;
            } elseif ($this->user->enabled != (bool) $this->request->request->get('enabled')) {
                return false;
            }

            return true;
        }

        return $this->user->admin || $this->user->nick === $this->request->get('code', '');
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();

        /// disable company column if there is only one company
        if ($this->empresa->count() < 2) {
            $this->views[$this->getMainViewName()]->disableColumn('company');
        }

        /// disable warehouse column if there is only one company
        $almacen = new Almacen();
        if ($almacen->count() < 2) {
            $this->views[$this->getMainViewName()]->disableColumn('warehouse');
        }

        $this->setTabsPosition('bottom');
        if ($this->user->admin) {
            $this->createViewsRole();
        }
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewsRole(string $viewName = 'EditRoleUser')
    {
        $this->addEditListView($viewName, 'RoleUser', 'roles', 'fas fa-address-card');
        $this->views[$viewName]->setInLine('true');

        /// Disable column
        $this->views[$viewName]->disableColumn('user', true);
    }

    /**
     * Action to delete data.
     *
     * @return bool
     */
    protected function deleteAction()
    {
        $this->permissions->allowDelete = $this->user->admin;
        return parent::deleteAction();
    }

    /**
     * Runs the data edit action.
     *
     * @return bool
     */
    protected function editAction()
    {
        $this->permissions->allowUpdate = $this->allowUpdate();
        $result = parent::editAction();

        // Are we changing user language?
        if ($result && $this->views['EditUser']->model->nick === $this->user->nick) {
            $this->toolBox()->i18n()->setLang($this->views['EditUser']->model->langcode);

            $expire = \time() + \FS_COOKIES_EXPIRE;
            $this->response->headers->setCookie(
                new Cookie('fsLang', $this->views['EditUser']->model->langcode, $expire, \FS_ROUTE)
            );
        }

        return $result;
    }

    /**
     * Runs data insert action.
     * 
     * @return bool
     */
    protected function insertAction()
    {
        $this->permissions->allowUpdate = $this->user->admin;
        return parent::insertAction();
    }

    /**
     * Return a list of pages where user has access.
     *
     * @param User $user
     *
     * @return array
     */
    protected function getUserPages($user)
    {
        $pageList = [];
        if ($user->admin) {
            $pageModel = new Page();
            foreach ($pageModel->all([], ['name' => 'ASC'], 0, 0) as $page) {
                if (false === $page->showonmenu) {
                    continue;
                }

                $pageList[] = ['value' => $page->name, 'title' => $page->name];
            }

            return $pageList;
        }

        $roleUserModel = new RoleUser();
        foreach ($roleUserModel->all([new DataBaseWhere('nick', $user->nick)]) as $roleUser) {
            foreach ($roleUser->getRoleAccess() as $roleAccess) {
                if (false === $roleAccess->getPage()->showonmenu) {
                    continue;
                }

                $pageList[$roleAccess->pagename] = ['value' => $roleAccess->pagename, 'title' => $roleAccess->pagename];
            }
        }

        return $pageList;
    }

    /**
     * Load view data proedure
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'EditRoleUser':
                $nick = $this->getViewModelValue('EditUser', 'nick');
                $where = [new DataBaseWhere('nick', $nick)];
                $view->loadData('', $where, ['id' => 'DESC']);
                break;

            case 'EditUser':
                parent::loadData($viewName, $view);
                $this->loadHomepageValues();
                $this->loadLanguageValues();
                if (false === $this->allowUpdate()) {
                    $this->setTemplate('Error/AccessDenied');
                } elseif ($view->model->nick == $this->user->nick) {
                    /// prevent user self-destruction
                    $this->setSettings($viewName, 'btnDelete', false);
                }
                break;
        }
    }

    /**
     * Load a list of pages where user has access that can be setted as homepage.
     */
    protected function loadHomepageValues()
    {
        if (false === $this->views['EditUser']->model->exists()) {
            $this->views['EditUser']->disableColumn('homepage');
            return;
        }

        $columnHomepage = $this->views['EditUser']->columnForName('homepage');
        $userPages = $this->getUserPages($this->views['EditUser']->model);
        $columnHomepage->widget->setValuesFromArray($userPages);
    }

    /**
     * Load the available language values from translator.
     */
    protected function loadLanguageValues()
    {
        $columnLangCode = $this->views['EditUser']->columnForName('language');
        if ($columnLangCode) {
            $langs = [];
            foreach ($this->toolBox()->i18n()->getAvailableLanguages() as $key => $value) {
                $langs[] = ['value' => $key, 'title' => $value];
            }

            /// sorting
            \usort($langs, function ($objA, $objB) {
                return \strcmp($objA['title'], $objB['title']);
            });

            $columnLangCode->widget->setValuesFromArray($langs, false);
        }
    }
}
