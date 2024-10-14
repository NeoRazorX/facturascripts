<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Lib\TwoFactorManager;
use FacturaScripts\Core\Tools;
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
        $data['icon'] = 'fa-solid fa-user-circle';
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
        $mvn = $this->getMainViewName();
        if ($this->empresa->count() < 2) {
            $this->views[$mvn]->disableColumn('company');
        }

        // disable warehouse column if there is only one company
        $almacen = new Almacen();
        if ($almacen->count() < 2) {
            $this->views[$mvn]->disableColumn('warehouse');
        }

        // disable options and print buttons
        $this->setSettings($mvn, 'btnOptions', false);
        $this->setSettings($mvn, 'btnPrint', false);

        // add two factor authentication tab
        $this->createViewsTwofactor();

        // add roles tab
        if ($this->user->admin) {
            $this->createViewsRole();
        }

        // add page options tab
        $this->createViewsPageOptions();

        // add emails tab
        $this->createViewsEmails();
    }

    protected function createViewsTwofactor(string $viewName = 'UserTwoFactor'): void
    {
        $this->addHtmlView($viewName, 'Tab\UserTwoFactor', 'User', 'two-factor', 'fa-solid fa-key');
    }

    protected function createViewsEmails(string $viewName = 'ListEmailSent'): void
    {
        $this->addListView($viewName, 'EmailSent', 'emails-sent', 'fa-solid fa-envelope')
            ->addOrderBy(['date'], 'date', 2)
            ->addSearchFields(['addressee', 'body', 'subject']);

        // desactivamos la columna de destinatario
        $this->views[$viewName]->disableColumn('user');

        // desactivamos el botón nuevo
        $this->setSettings($viewName, 'btnNew', false);

        // filtros
        $this->listView($viewName)->addFilterPeriod('period', 'date', 'date', true);
    }

    protected function createViewsPageOptions(string $viewName = 'ListPageOption'): void
    {
        $this->addListView($viewName, 'PageOption', 'options', 'fa-solid fa-wrench')
            ->addOrderBy(['name'], 'name', 1)
            ->addOrderBy(['last_update'], 'last-update')
            ->addSearchFields(['name']);

        // desactivamos el botón nuevo
        $this->setSettings($viewName, 'btnNew', false);
    }

    protected function createViewsRole(string $viewName = 'EditRoleUser'): void
    {
        $this->addEditListView($viewName, 'RoleUser', 'roles', 'fa-solid fa-address-card');
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
            Tools::lang()->setLang($this->views['EditUser']->model->langcode);

            $expire = time() + FS_COOKIES_EXPIRE;
            $this->response->cookie('fsLang', $this->views['EditUser']->model->langcode, $expire);
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

    protected function execAfterAction($action)
    {
        if ($action === 'modal2fa') {
            // Obtener el código TOTP enviado en la solicitud
            $totpCode = $this->request->request->get('codetime');

            // Validar que el código no esté vacío
            if (empty($totpCode)) {
                Tools::log()->error('totp-code-not-received');
                return parent::execAfterAction($action);
            }

            // Obtener el modelo de usuario para validar el TOTP
            $userModel = $this->views['EditUser']->model;

            // Validar el código TOTP
            if ($this->validateTotpCode($userModel, $totpCode)) {
                // Activar la autenticación de dos factores y guardar el estado
                $userModel->two_factor_enabled = true;
                if ($userModel->save()) {
                    Tools::log()->info("totp-code-correct-two-step-authentication-has-been-activated-for-the-user", ['%nick%' => $userModel->nick]);
                } else {
                    Tools::log()->error("error-saving two-factor-status-for-user", ['%nick%' => $userModel->nick]);
                }
            } else {
                Tools::log()->error('incorrect-totp-code.');
            }
        }

        return parent::execAfterAction($action);
    }

    /**
     * Valida el código TOTP proporcionado por el usuario.
     *
     * @param Model $userModel El modelo del usuario.
     * @param string $totpCode El código TOTP introducido.
     * @return bool Verdadero si el código es válido, falso en caso contrario.
     */
    private function validateTotpCode($userModel, string $totpCode): bool
    {
        if (empty($userModel->two_factor_secret_key)) {
            Tools::log()->error("El usuario con ID {$userModel->id} no tiene una clave secreta de TOTP configurada.");
            return false;
        }

        return TwoFactorManager::verifyCode($userModel->two_factor_secret_key, $totpCode);
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
                $this->loadHomepageValues();
                $this->loadLanguageValues();

                // guarda el usuario si no tiene clave secreta de dos factores
                if (empty($view->model->two_factor_secret_key)) {
                    $view->model->save();
                }

                if (false === $this->allowUpdate()) {
                    $this->setTemplate('Error/AccessDenied');
                } elseif ($view->model->nick == $this->user->nick) {
                    // prevent user self-destruction
                    $this->setSettings($viewName, 'btnDelete', false);
                }
                // is the user is admin, hide the EditRoleUser tab
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
        if (false === $this->views['EditUser']->model->exists()) {
            $this->views['EditUser']->disableColumn('homepage');
            return;
        }

        $columnHomepage = $this->views['EditUser']->columnForName('homepage');
        if ($columnHomepage && $columnHomepage->widget->getType() === 'select') {
            $userPages = $this->getUserPages($this->views['EditUser']->model);
            $columnHomepage->widget->setValuesFromArray($userPages, false, true);
        }
    }

    /**
     * Load the available language values from translator.
     */
    protected function loadLanguageValues(): void
    {
        $columnLangCode = $this->views['EditUser']->columnForName('language');
        if ($columnLangCode && $columnLangCode->widget->getType() === 'select') {
            $langs = [];
            foreach (Tools::lang()->getAvailableLanguages() as $key => $value) {
                $langs[] = ['value' => $key, 'title' => $value];
            }

            $columnLangCode->widget->setValuesFromArray($langs, false);
        }
    }
}
