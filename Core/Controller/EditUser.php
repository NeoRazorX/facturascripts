<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class EditUser extends EditController
{
    public function getImageUrl(): string
    {
        $mvn = $this->getMainViewName();
        return $this->views[$mvn]->model->gravatar();
    }

    public function getModelClassName(): string
    {
        return 'User';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'user';
        $data['icon'] = 'fas fa-user-circle';
        return $data;
    }

    private function allowUpdate(): bool
    {
        // preload user data
        $code = $this->request->request->get('code', $this->request->query->get('code'));
        $user = new User();
        if (false === $user->loadFromCode($code)) {
            // user not found, maybe it is a new user, so only admin can create it
            return $this->user->admin;
        }

        // admin can update all users
        if ($this->user->admin) {
            return true;
        }

        // non-admin users can only update their own data
        return $user->nick === $this->user->nick;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('top');

        // disable company column if there is only one company
        if ($this->empresa->count() < 2) {
            $this->views[$this->getMainViewName()]->disableColumn('company');
        }

        // disable warehouse column if there is only one company
        $almacen = new Almacen();
        if ($almacen->count() < 2) {
            $this->views[$this->getMainViewName()]->disableColumn('warehouse');
        }

        // disable print button
        $this->setSettings($this->getMainViewName(), 'btnPrint', false);

        // add roles tab
        if ($this->user->admin) {
            $this->createViewsRole();
        }
    }

    protected function createViewsRole(string $viewName = 'EditRoleUser')
    {
        $this->addEditListView($viewName, 'RoleUser', 'roles', 'fas fa-address-card');
        $this->views[$viewName]->setInLine('true');

        // Disable column
        $this->views[$viewName]->disableColumn('user', true);
    }

    protected function deleteAction(): bool
    {
        // only admin can delete users
        $this->permissions->allowDelete = $this->user->admin;
        return parent::deleteAction();
    }

    protected function editAction(): bool
    {
        $this->permissions->allowUpdate = $this->allowUpdate();

        // prevent some user changes
        if ($this->request->request->get('code', '') === $this->user->nick) {
            if ($this->user->admin != (bool)$this->request->request->get('admin')) {
                // prevent user from becoming admin
                $this->permissions->allowUpdate = false;
            } elseif ($this->user->enabled != (bool)$this->request->request->get('enabled')) {
                // prevent user from disabling himself
                $this->permissions->allowUpdate = false;
            }
        }
        $result = parent::editAction();

        // Are we changing user language?
        if ($result && $this->views['EditUser']->model->nick === $this->user->nick) {
            $this->toolBox()->i18n()->setLang($this->views['EditUser']->model->langcode);

            $expire = time() + FS_COOKIES_EXPIRE;
            $this->response->headers->setCookie(
                new Cookie('fsLang', $this->views['EditUser']->model->langcode, $expire, \FS_ROUTE)
            );
        }

        return $result;
    }

    protected function insertAction(): bool
    {
        // only admin can create users
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
    protected function getUserPages(User $user): array
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
                $page = $roleAccess->getPage();
                if (false === $page->exists() || false === $page->showonmenu) {
                    continue;
                }

                $pageList[$roleAccess->pagename] = ['value' => $roleAccess->pagename, 'title' => $roleAccess->pagename];
            }
        }

        return $pageList;
    }

    /**
     * Load view data procedure
     *
     * @param string $viewName
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
                    // prevent user self-destruction
                    $this->setSettings($viewName, 'btnDelete', false);
                }
                // is the user is admin, hide the EditRoleUser tab
                if ($view->model->admin) {
                    $this->setSettings('EditRoleUser', 'active', false);
                }
                break;
        }
    }

    /**
     * Load a list of pages where user has access that can be set as homepage.
     */
    protected function loadHomepageValues()
    {
        if (false === $this->views['EditUser']->model->exists()) {
            $this->views['EditUser']->disableColumn('homepage');
            return;
        }

        $columnHomepage = $this->views['EditUser']->columnForName('homepage');
        if ($columnHomepage && $columnHomepage->widget->getType() === 'select') {
            $userPages = $this->getUserPages($this->views['EditUser']->model);
            $columnHomepage->widget->setValuesFromArray($userPages);
        }
    }

    /**
     * Load the available language values from translator.
     */
    protected function loadLanguageValues()
    {
        $columnLangCode = $this->views['EditUser']->columnForName('language');
        if ($columnLangCode && $columnLangCode->widget->getType() === 'select') {
            $langs = [];
            foreach ($this->toolBox()->i18n()->getAvailableLanguages() as $key => $value) {
                $langs[] = ['value' => $key, 'title' => $value];
            }

            $columnLangCode->widget->setValuesFromArray($langs, false);
        }
    }
}
