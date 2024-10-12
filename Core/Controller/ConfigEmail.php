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

use FacturaScripts\Core\Lib\ExtendedController\PanelController;
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
        $pageData = parent::getPageData();
        $pageData['menu'] = 'admin';
        $pageData['title'] = 'email';
        $pageData['icon'] = 'fa-solid fa-envelope';
        return $pageData;
    }

    protected function createViews()
    {
        $this->setTemplate('EditSettings');
        $this->createViewsEmail();
        $this->createViewsEmailSent();
        $this->createViewsEmailNotification();
    }

    protected function createViewsEmail(string $viewName = 'ConfigEmail'): void
    {
        $this->addEditView($viewName, 'Settings', 'email', 'fa-solid fa-envelope');

        // desactivamos los botones nuevo y eliminar
        $this->tab($viewName)
            ->setSettings('btnNew', false)
            ->setSettings('btnDelete', false);
    }

    protected function createViewsEmailNotification(string $viewName = 'ListEmailNotification'): void
    {
        $this->addListView($viewName, 'EmailNotification', 'notifications', 'fa-solid fa-bell')
            ->addSearchFields(['body', 'name', 'subject'])
            ->addOrderBy(['date'], 'date')
            ->addOrderBy(['name'], 'name', 1);

        // filtros
        $this->listView($viewName)->addFilterCheckbox('enabled');

        // desactivamos el botón nuevo
        $this->tab($viewName)->setSettings('btnNew', false);

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
            'icon' => 'far fa-square',
            'label' => 'disable'
        ]);
    }

    protected function createViewsEmailSent(string $viewName = 'ListEmailSent'): void
    {
        $this->addListView($viewName, 'EmailSent', 'emails-sent', 'fa-solid fa-paper-plane')
            ->addSearchFields(['addressee', 'body', 'subject'])
            ->addOrderBy(['date'], 'date', 2);

        // filtros
        $users = $this->codeModel->all('users', 'nick', 'nick');
        $this->listView($viewName)->addFilterSelect('nick', 'user', 'nick', $users);

        $from = $this->codeModel->all('emails_sent', 'email_from', 'email_from');
        $this->listView($viewName)->addFilterSelect('from', 'from', 'email_from', $from);

        $this->listView($viewName)
            ->addFilterPeriod('date', 'period', 'date', true)
            ->addFilterCheckbox('opened')
            ->addFilterCheckbox('attachment', 'has-attachments');

        // desactivamos el botón nuevo
        $this->tab($viewName)->setSettings('btnNew', false);
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
            if (false === $notification->loadFromCode($code)) {
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
        }

        return parent::execPreviousAction($action);
    }

    protected function loadData($viewName, $view)
    {
        $this->hasData = true;

        switch ($viewName) {
            case 'ConfigEmail':
                $view->loadData('email');
                $view->model->name = 'email';
                if ($view->model->mailer === 'smtp') {
                    // añadimos el botón test
                    $this->addButton($viewName, [
                        'action' => 'testmail',
                        'color' => 'info',
                        'icon' => 'fa-solid fa-envelope',
                        'label' => 'test'
                    ]);
                }
                break;

            case 'ListEmailNotification':
            case 'ListEmailSent':
                $view->loadData();
                break;
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
}
