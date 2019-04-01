<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Lib\ExtendedController;
use FacturaScripts\Dinamic\Model;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Controller to edit a single item from the User model
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class EditUser extends ExtendedController\EditController
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
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'user';
        $pagedata['icon'] = 'fas fa-user-tie';
        $pagedata['menu'] = 'admin';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('top');

        $this->addEditListView('EditRoleUser', 'RoleUser', 'roles', 'fas fa-address-card');

        /// Disable column
        $this->views['EditRoleUser']->disableColumn('user', true);
    }

    /**
     * 
     * @return bool
     */
    protected function editAction()
    {
        $result = parent::editAction();

        // Are we changing user language?
        if ($result && $this->views['EditUser']->model->nick === $this->user->nick) {
            $this->i18n->setLangCode($this->views['EditUser']->model->nick);

            $expire = time() + FS_COOKIES_EXPIRE;
            $this->response->headers->setCookie(new Cookie('fsLang', $this->views['EditUser']->model->langcode, $expire));
        }

        return $result;
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
            foreach ($pageModel->all([], ['name' => 'ASC'], 0, 0) as $page) {
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
     * Load view data proedure
     *
     * @param string                      $viewName
     * @param ExtendedController\EditView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'EditRoleUser':
                $nick = $this->getViewModelValue('EditUser', 'nick');
                $where = [new DataBaseWhere('nick', $nick)];
                $view->loadData('', $where, [], 0, 0);
                break;

            case 'EditUser':
                parent::loadData($viewName, $view);
                $this->loadHomepageValues();
                $this->loadLanguageValues();
                break;

            default:
                parent::loadData($viewName, $view);
        }
    }

    /**
     * Load a list of pages where user has access that can be setted as homepage.
     */
    private function loadHomepageValues()
    {
        $columnHomepage = $this->views['EditUser']->columnForName('homepage');
        $userPages = $this->getUserPages($this->views['EditUser']->model);
        $columnHomepage->widget->setValuesFromArray($userPages);
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

        $columnLangCode->widget->setValuesFromArray($langs, false);
    }
}
