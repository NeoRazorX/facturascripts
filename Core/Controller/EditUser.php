<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Lib\ExtendedController;
use FacturaScripts\Core\Model;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Controller to edit a single item from the User model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class EditUser extends ExtendedController\PanelController
{

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
     * Load views
     */
    protected function createViews()
    {
        /// Add all views
        $this->addEditView('EditUser', 'User', 'user', 'fa-user');
        $this->addEditListView('EditRoleUser', 'RoleUser', 'roles', 'fa-address-card');

        /// Load values for input selects
        $this->loadHomepageValues();
        $this->loadLanguageValues();

        /// Disable column
        $this->views['EditRoleUser']->disableColumn('user', true);
    }

    protected function editAction()
    {
        parent::editAction();

        // Are we changing user language?
        if ($this->views['EditUser']->model->nick === $this->user->nick) {
            $this->i18n->setLangCode($this->views['EditUser']->model->nick);

            $expire = time() + FS_COOKIES_EXPIRE;
            $this->response->headers->setCookie(new Cookie('fsLang', $this->views['EditUser']->model->langcode, $expire));
        }
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
            case 'EditUser':
                $code = $this->request->get('code');
                $view->loadData($code);
                break;

            case 'EditRoleUser':
                $nick = $this->getViewModelValue('EditUser', 'nick');
                $where = [new DataBaseWhere('nick', $nick)];
                $view->loadData('', $where, [], 0, 0);
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
