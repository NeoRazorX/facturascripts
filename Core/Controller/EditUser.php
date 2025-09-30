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

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Almacen;
use FacturaScripts\Dinamic\Model\Page;
use FacturaScripts\Dinamic\Model\RoleUser;
use FacturaScripts\Dinamic\Model\User;

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
        return $this->getModel()->gravatar();
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
        $data['icon'] = 'fa-solid fa-user-circle';
        return $data;
    }

    private function allowUpdate(): bool
    {
        // preload user data
        $user = new User();
        $code = $this->request->queryOrInput('code');
        if (false === $user->load($code)) {
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

        // add roles tab
        if ($this->user->admin) {
            $this->createViewsRole();
        }

        // add page options tab
        $this->createViewsPageOptions();

        // add emails tab
        $this->createViewsEmails();
    }

    protected function createViewsEmails(string $viewName = 'ListEmailSent'): void
    {
        $this->addListView($viewName, 'EmailSent', 'emails-sent', 'fa-solid fa-envelope')
            ->addOrderBy(['date'], 'date', 2)
            ->addSearchFields(['addressee', 'body', 'subject'])
            ->disableColumn('user')
            ->setSettings('btnNew', false)
            ->addFilterPeriod('period', 'date', 'date', true);
    }

    protected function createViewsPageOptions(string $viewName = 'ListPageOption'): void
    {
        $this->addListView($viewName, 'PageOption', 'options', 'fa-solid fa-wrench')
            ->addOrderBy(['name'], 'name', 1)
            ->addOrderBy(['last_update'], 'last-update')
            ->addSearchFields(['name'])
            ->setSettings('btnNew', false);
    }

    protected function createViewsRole(string $viewName = 'EditRoleUser'): void
    {
        $this->addEditListView($viewName, 'RoleUser', 'roles', 'fa-solid fa-address-card')
            ->setInLine(true)
            ->disableColumn('user', true);
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
        if ($this->request->input('code', '') === $this->user->nick) {
            if ($this->user->admin != (bool)$this->request->input('admin')) {
                // prevent user from becoming admin
                $this->permissions->allowUpdate = false;
            } elseif ($this->user->enabled != (bool)$this->request->input('enabled')) {
                // prevent user from disabling himself
                $this->permissions->allowUpdate = false;
            }
        }
        $result = parent::editAction();

        // Are we changing user language?
        if ($result && $this->tab('EditUser')->model->nick === $this->user->nick) {
            Tools::lang()->setLang($this->tab('EditUser')->model->langcode);

            $expire = time() + Tools::config('cookies_expire');
            $this->response->cookie('fsLang', $this->tab('EditUser')->model->langcode, $expire);
        }

        return $result;
    }

    protected function execAfterAction($action)
    {
        switch ($action) {
            case 'two-factor-enable':
                $this->twoFactorEnableAction();
                return;
        }

        parent::execAfterAction($action);
    }

    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'two-factor-disable':
                $this->twoFactorDisableAction();
                return true;

            case 'two-factor-verify':
                $this->twoFactorVerifyAction();
                return true;
        }

        return parent::execPreviousAction($action);
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
            foreach (Page::all([], ['name' => 'ASC']) as $page) {
                if (false === $page->showonmenu) {
                    continue;
                }

                $pageList[] = ['value' => $page->name, 'title' => $page->name];
            }

            return $pageList;
        }

        $where = [Where::eq('nick', $user->nick)];
        foreach (RoleUser::all($where) as $roleUser) {
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

    protected function insertAction(): bool
    {
        // only admin can create users
        $this->permissions->allowUpdate = $this->user->admin;

        return parent::insertAction();
    }

    /**
     *
     * Load view data procedure
     *
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $mvn = $this->getMainViewName();
        $nick = $this->getViewModelValue($mvn, 'nick');

        switch ($viewName) {
            case 'EditRoleUser':
                $where = [new DataBaseWhere('nick', $nick)];
                $view->loadData('', $where, ['id' => 'DESC']);
                break;

            case 'EditUser':
                parent::loadData($viewName, $view);

                if (false === $this->allowUpdate()) {
                    $this->setTemplate('Error/AccessDenied');
                    break;
                }

                $this->loadHomepageValues();
                $this->loadLanguageValues();

                // disable company column if there is only one company
                if ($this->empresa->count() < 2) {
                    $view->disableColumn('company');
                }

                // disable warehouse column if there is only one company
                $almacen = new Almacen();
                if ($almacen->count() < 2) {
                    $view->disableColumn('warehouse');
                }

                // disable options and print buttons
                $view->setSettings('btnOptions', false)
                    ->setSettings('btnPrint', false);

                if ($view->model->nick == $this->user->nick) {
                    // prevent user self-destruction
                    $view->setSettings('btnDelete', false);
                }

                // two-factor authentication
                if ($view->model->two_factor_enabled) {
                    $this->addButton($viewName, [
                        'action' => 'two-factor-disable',
                        'color' => 'warning',
                        'confirm' => true,
                        'icon' => 'fa-solid fa-shield-halved',
                        'label' => 'two-factor-auth-disable',
                    ]);
                } else {
                    $this->addButton($viewName, [
                        'action' => 'two-factor-enable',
                        'color' => 'info',
                        'icon' => 'fa-solid fa-shield-halved',
                        'label' => 'two-factor-auth-enable',
                    ]);
                }

                // if the user is admin, hide the EditRoleUser tab
                if ($view->model->admin && array_key_exists('EditRoleUser', $this->views)) {
                    $this->setSettings('EditRoleUser', 'active', false);
                }
                break;

            case 'ListEmailSent':
                $where = [new DataBaseWhere('nick', $nick)];
                $view->loadData('', $where);
                break;

            case 'ListPageOption':
                $where = [
                    new DataBaseWhere('nick', $nick),
                    new DataBaseWhere('nick', null, 'IS', 'OR'),
                ];
                $view->loadData('', $where);
                break;
        }
    }

    /**
     * Load a list of pages where user has access that can be set as homepage.
     */
    protected function loadHomepageValues(): void
    {
        if (false === $this->tab('EditUser')->model->exists()) {
            $this->tab('EditUser')->disableColumn('homepage');
            return;
        }

        $columnHomepage = $this->tab('EditUser')->columnForName('homepage');
        if ($columnHomepage && $columnHomepage->widget->getType() === 'select') {
            $userPages = $this->getUserPages($this->tab('EditUser')->model);
            $columnHomepage->widget->setValuesFromArray($userPages, false, true);
        }
    }

    /**
     * Load the available language values from translator.
     */
    protected function loadLanguageValues(): void
    {
        $columnLangCode = $this->tab('EditUser')->columnForName('language');
        if ($columnLangCode && $columnLangCode->widget->getType() === 'select') {
            $langs = [];
            foreach (Tools::lang()->getAvailableLanguages() as $key => $value) {
                $langs[] = ['value' => $key, 'title' => $value];
            }

            $columnLangCode->widget->setValuesFromArray($langs, false);
        }
    }

    protected function twoFactorDisableAction(): void
    {
        if (!$this->allowUpdate()) {
            Tools::log()->warning('not-allowed-update');
            return;
        } elseif (!$this->validateFormToken()) {
            return;
        }

        // load user from code
        $user = new User();
        $code = $this->request->input('code');
        if (false === $user->load($code)) {
            Tools::log()->error('record-not-found');
            return;
        }

        // disable two-factor authentication
        if (false === $user->disableTwoFactor()) {
            Tools::log()->error('record-save-error');
            return;
        }

        // save the user with two-factor disabled
        if (false === $user->save()) {
            Tools::log()->error('record-save-error');
            return;
        }

        Tools::log()->notice('two-factor-auth-disabled');
    }

    protected function twoFactorEnableAction(): void
    {
        if (!$this->allowUpdate()) {
            Tools::log()->warning('not-allowed-update');
            return;
        } elseif (!$this->validateFormToken()) {
            return;
        }

        $user = $this->getModel();
        if (false === $user->exists()) {
            Tools::log()->error('record-not-found');
            return;
        }

        if (empty($user->enableTwoFactor())) {
            Tools::log()->error('record-save-error');
            return;
        }

        // load the two-factor setup template
        $this->setTemplate('EditUserTwoFactor');
    }

    protected function twoFactorVerifyAction(): void
    {
        if (!$this->allowUpdate()) {
            Tools::log()->warning('not-allowed-update');
            return;
        } elseif (!$this->validateFormToken()) {
            return;
        }

        // load user from code
        $user = new User();
        $code = $this->request->queryOrInput('code');
        if (false === $user->load($code)) {
            Tools::log()->error('record-not-found');
            return;
        }

        // set the two-factor secret key
        $secretKey = $this->request->input('two_factor_secret_key', '');
        if (empty($user->enableTwoFactor($secretKey))) {
            Tools::log()->error('two-factor-secret-key-empty');
            return;
        }

        // verify the two-factor code
        $twoFactorCode = $this->request->input('two_factor_code', '');
        if (false === $user->verifyTwoFactorCode($twoFactorCode)) {
            Tools::log()->error('two-factor-code-invalid');
            return;
        }

        // save the user with two-factor enabled
        if (false === $user->save()) {
            Tools::log()->error('record-save-error');
            return;
        }

        Tools::log()->notice('two-factor-auth-enabled');
    }
}
