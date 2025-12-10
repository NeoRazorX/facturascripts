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

use FacturaScripts\Core\Lib\Email\MicrosoftGraphClient;
use FacturaScripts\Core\Lib\ExtendedController\PanelController;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\Email\NewMail;
use FacturaScripts\Dinamic\Model\EmailNotification;

/**
 * Controller to edit main settings
 *
 * @author Daniel Fernández Giménez  <hola@danielfg.es>
 * @author Carlos Garcia Gomez       <carlos@facturascripts.com>
 */
class ConfigEmail extends PanelController
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'email';
        $data['icon'] = 'fa-solid fa-envelope';
        return $data;
    }

    protected function createViews(): void
    {
        $this->setTemplate('EditSettings');

        $this->createViewsEmail();
        $this->createViewsEmailSent();
        $this->createViewsEmailNotification();
    }

    protected function createViewsEmail(string $viewName = 'ConfigEmail'): void
    {
        $this->addEditView($viewName, 'Settings', 'email', 'fa-solid fa-envelope')
            ->setSettings('btnNew', false)
            ->setSettings('btnDelete', false);
    }

    protected function createViewsEmailNotification(string $viewName = 'ListEmailNotification'): void
    {
        $this->addListView($viewName, 'EmailNotification', 'notifications', 'fa-solid fa-bell')
            ->addSearchFields(['body', 'name', 'subject'])
            ->addOrderBy(['date'], 'date')
            ->addOrderBy(['name'], 'name', 1)
            ->addFilterCheckbox('enabled')
            ->setSettings('btnNew', false);

        // añadimos los botones de activar y desactivar
        $this->addButton($viewName, [
            'action' => 'enable-notification',
            'color' => 'success',
            'icon' => 'fa-solid fa-check-square',
            'label' => 'enable'
        ]);

        $this->addButton($viewName, [
            'action' => 'disable-notification',
            'color' => 'warning',
            'icon' => 'fa-regular fa-square',
            'label' => 'disable'
        ]);
    }

    protected function createViewsEmailSent(string $viewName = 'ListEmailSent'): void
    {
        $this->addListView($viewName, 'EmailSent', 'emails-sent', 'fa-solid fa-paper-plane')
            ->addSearchFields(['addressee', 'body', 'subject'])
            ->addOrderBy(['date'], 'date', 2)
            ->setSettings('btnNew', false);

        $users = $this->codeModel->all('users', 'nick', 'nick');
        $from = $this->codeModel->all('emails_sent', 'email_from', 'email_from');

        // filtros
        $this->listView($viewName)
            ->addFilterSelect('nick', 'user', 'nick', $users)
            ->addFilterSelect('from', 'from', 'email_from', $from)
            ->addFilterPeriod('date', 'period', 'date', true)
            ->addFilterCheckbox('opened')
            ->addFilterCheckbox('attachment', 'has-attachments');
    }

    protected function editAction(): bool
    {
        // Asegúrate de que la acción de edición es válida
        if (false === parent::editAction()) {
            return false;
        }

        // comprobar si hay logo y la instalación es offline
        $idLogo = Tools::settings('email', 'idlogo');
        if (!empty($idLogo) && $this->isOfflineInstallation()) {
            Tools::log()->warning('email-logo-offline-warning');
        }

        return true;
    }

    protected function enableNotificationAction(bool $value): void
    {
        if (false === $this->validateFormToken()) {
            return;
        } elseif (false === $this->user->can('EditEmailNotification', 'update')) {
            Tools::log()->warning('not-allowed-modify');
            return;
        }

        $codes = $this->request->request->getArray('codes');
        if (false === is_array($codes)) {
            return;
        }

        foreach ($codes as $code) {
            $notification = new EmailNotification();
            if (false === $notification->load($code)) {
                continue;
            }

            $notification->enabled = $value;
            if (false === $notification->save()) {
                Tools::log()->warning('record-save-error');
                return;
            }
        }

        Tools::log()->notice('record-updated-correctly');
    }

    /**
     * Run the controller after actions
     *
     * @param string $action
     */
    protected function execAfterAction($action)
    {
        if ($action === 'testmail') {
            $this->testMailAction();
        }

        parent::execAfterAction($action);
    }

    /**
     * @param string $action
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'disable-notification':
                $this->enableNotificationAction(false);
                break;

            case 'enable-notification':
                $this->enableNotificationAction(true);
                break;

            case 'msgraph-auth':
                $this->msgraphAuthAction();
                return false;

            case 'msgraph-callback':
                $this->msgraphCallbackAction();
                return false;
        }

        return parent::execPreviousAction($action);
    }

    private function isOfflineInstallation(): bool
    {
        $siteUrl = Tools::siteUrl();
        $host = (string)parse_url($siteUrl, PHP_URL_HOST);

        // si termina en localhost, local o empieza con 127
        if (str_ends_with($host, 'localhost')
            || str_ends_with($host, 'local')
            || str_starts_with($host, '127.')
        ) {
            return true;
        }

        return false;
    }

    protected function loadData($viewName, $view)
    {
        $this->hasData = true;

        switch ($viewName) {
            case 'ConfigEmail':
                $view->loadData('email');
                $view->model->name = 'email';
                $this->loadMailerValues($viewName);
                $graphClient = new MicrosoftGraphClient();
                $view->model->msgraph_redirect_uri = $graphClient->getRedirectUri();
                if (empty($view->model->msgraph_scopes)) {
                    $view->model->msgraph_scopes = Tools::settings('email', 'msgraph_scopes', 'offline_access https://graph.microsoft.com/Mail.Send');
                }
                $tokenMode = strtolower((string)$view->model->msgraph_token_mode);
                if (empty($tokenMode)) {
                    $tokenMode = strtolower((string)Tools::settings('email', 'msgraph_token_mode', 'authorization_code'));
                }
                if (!in_array($tokenMode, ['authorization_code', 'password'], true)) {
                    $tokenMode = 'authorization_code';
                }
                $view->model->msgraph_token_mode = $tokenMode;
                if ($view->model->msgraph_save_to_sent === null) {
                    $view->model->msgraph_save_to_sent = Tools::settings('email', 'msgraph_save_to_sent', '1');
                }
                if ($view->model->msgraph_username === null) {
                    $view->model->msgraph_username = Tools::settings('email', 'msgraph_username', '');
                }
                if ($view->model->msgraph_password === null) {
                    $view->model->msgraph_password = Tools::settings('email', 'msgraph_password', '');
                }
                if ($view->model->mailer === 'smtp' || $view->model->mailer === 'SMTP') {
                    // añadimos el botón test
                    $this->addButton($viewName, [
                        'action' => 'testmail',
                        'color' => 'info',
                        'icon' => 'fa-solid fa-envelope',
                        'label' => 'test'
                    ]);
                } elseif ($view->model->mailer === 'msgraph' && strtolower((string)$view->model->msgraph_token_mode) !== 'password') {
                    $this->addButton($viewName, [
                        'action' => 'msgraph-auth',
                        'color' => 'primary',
                        'icon' => 'fa-brands fa-microsoft',
                        'label' => 'msgraph-authorize'
                    ]);
                }
                break;

            case 'ListEmailNotification':
            case 'ListEmailSent':
                $view->loadData();
                break;
        }
    }

    protected function loadMailerValues(string $viewName): void
    {
        $column = $this->views[$viewName]->columnForName('mailer');
        if ($column && $column->widget->getType() === 'select') {
            $column->widget->setValuesFromArrayKeys(NewMail::getMailer());
        }
    }

    protected function testMailAction(): void
    {
        // guardamos los datos del formulario primero
        if (false === $this->editAction()) {
            return;
        }

        $email = new NewMail();
        if ($email->test()) {
            Tools::log()->notice('mail-test-ok');
            return;
        }

        Tools::log()->warning('mail-test-error');
    }

    protected function msgraphAuthAction(): void
    {
        if (false === $this->editAction()) {
            return;
        }

        $tokenMode = strtolower((string)Tools::settings('email', 'msgraph_token_mode', 'authorization_code'));
        if ($tokenMode === 'password') {
            Tools::log()->warning('msgraph-auth-disabled');
            return;
        }

        if (Tools::settings('email', 'mailer') !== 'msgraph') {
            Tools::log()->warning('msgraph-auth-missing-config');
            return;
        }

        $graphClient = new MicrosoftGraphClient();
        if (false === $graphClient->hasClientConfiguration()) {
            Tools::log()->warning('msgraph-auth-missing-config');
            return;
        }

        $state = Tools::randomString(40);
        Session::set('msgraph_state', $state);
        Session::set('msgraph_state_time', time());

        $authUrl = $graphClient->getAuthorizationUrl($state);
        if (empty($authUrl)) {
            $error = $this->formatGraphError($graphClient->getLastError());
            Tools::log()->warning('msgraph-auth-error', ['%error%' => $error]);
            return;
        }

        $this->redirect($authUrl);
    }

    protected function msgraphCallbackAction(): void
    {
        $tokenMode = strtolower((string)Tools::settings('email', 'msgraph_token_mode', 'authorization_code'));
        if ($tokenMode === 'password') {
            Tools::log()->warning('msgraph-auth-disabled');
            $this->redirect($this->url());
            return;
        }

        $state = (string)$this->request->query->get('state', '');
        $storedState = (string)Session::get('msgraph_state');
        Session::set('msgraph_state', null);
        Session::set('msgraph_state_time', null);

        if (empty($state) || $state !== $storedState) {
            Tools::log()->warning('msgraph-state-mismatch');
            $this->redirect($this->url());
            return;
        }

        $error = (string)$this->request->query->get('error', '');
        if (!empty($error)) {
            $description = (string)$this->request->query->get('error_description', $error);
            Tools::log()->warning('msgraph-auth-error', ['%error%' => $description]);
            $this->redirect($this->url());
            return;
        }

        $code = (string)$this->request->query->get('code', '');
        if (empty($code)) {
            Tools::log()->warning('msgraph-state-mismatch');
            $this->redirect($this->url());
            return;
        }

        $graphClient = new MicrosoftGraphClient();
        if ($graphClient->exchangeCode($code)) {
            Tools::log()->notice('msgraph-auth-success');
        } else {
            $errorMessage = $this->formatGraphError($graphClient->getLastError());
            Tools::log()->error('msgraph-auth-error', ['%error%' => $errorMessage]);
        }

        $this->redirect($this->url());
    }

    protected function formatGraphError(?string $error): string
    {
        if (empty($error)) {
            return Tools::trans('unknown-error');
        }

        switch ($error) {
            case 'missing-configuration':
                return Tools::trans('msgraph-auth-missing-config');

            case 'missing-refresh-token':
                return Tools::trans('msgraph-token-missing');

            case 'missing-password-credentials':
                return Tools::trans('msgraph-password-missing');

            case 'authorization-disabled':
                return Tools::trans('msgraph-auth-disabled');

            default:
                return $error;
        }
    }
}