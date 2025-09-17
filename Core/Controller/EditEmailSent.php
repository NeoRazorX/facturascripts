<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\Contacto;

/**
 * Controller to edit a single register of EmailSent
 *
 * @author Raul                     <raljopa@gmail.com>
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class EditEmailSent extends EditController
{
    public function getModelClassName(): string
    {
        return 'EmailSent';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'email-sent';
        $data['icon'] = 'fa-solid fa-envelope';
        return $data;
    }

    /**
     * Redirects to the contact page of this email.
     */
    protected function contactAction(): void
    {
        $contact = new Contacto();
        $email = $this->getViewModelValue($this->getMainViewName(), 'addressee');
        $where = [new DataBaseWhere('email', $email)];
        if ($contact->loadWhere($where)) {
            $this->redirect($contact->url());
            return;
        }

        Tools::log()->warning('record-not-found');
    }

    /**
     * Loads views.
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        $this->createViewHtml();
        $this->createViewAttachments();

        // buttons
        $mainView = $this->getMainViewName();
        $this->addButton($mainView, [
            'action' => 'contact',
            'color' => 'info',
            'icon' => 'fa-solid fa-address-book',
            'label' => 'contact',
            'type' => 'button'
        ]);

        // disable buttons
        $this->setSettings($mainView, 'btnNew', false);

        // other view
        $this->createViewOtherEmails();
    }

    protected function createViewAttachments(string $viewName = 'EmailSentAttachment'): void
    {
        $this->addHtmlView($viewName, 'Tab\EmailSentAttachment', 'EmailSent', 'attached-files', 'fa-solid fa-paperclip');
    }

    protected function createViewHtml(string $viewName = 'EmailSentHtml'): void
    {
        $this->addHtmlView($viewName, 'Tab\EmailSentHtml', 'EmailSent', 'html');
    }

    protected function createViewOtherEmails(string $viewName = 'ListEmailSent'): void
    {
        $this->addListView($viewName, 'EmailSent', 'emails', 'fa-solid fa-paper-plane')
            ->addOrderBy(['date'], 'date', 2)
            ->addSearchFields(['body', 'subject'])
            ->setSettings('btnNew', false);
    }

    /**
     * @param string $action
     */
    protected function execAfterAction($action)
    {
        switch ($action) {
            case 'contact':
                $this->contactAction();
                break;

            default:
                parent::execAfterAction($action);
        }
    }

    protected function execPreviousAction($action)
    {
        if ($action === 'getHtml') {
            $this->getHtmlAction();
            return false;
        }

        return parent::execPreviousAction($action);
    }

    protected function getHtmlAction(): void
    {
        $this->setTemplate(false);

        // cargamos el modelo
        $model = $this->getModel();
        if (false === $model->loadFromCode($this->request->queryOrInput('code', ''))) {
            $this->response->json(['getHtml' => false]);
            return;
        }

        $this->response->json([
            'getHtml' => true,
            'html' => empty($model->html) ?
                '<h1 style="text-align: center">' . Tools::trans('not-stored-content') . '</h1>' :
                Tools::fixHtml($model->html),
        ]);
    }

    /**
     * Load view data procedure
     *
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $mvn = $this->getMainViewName();

        switch ($viewName) {
            case 'EmailSentAttachment':
                $view->cursor = $this->views[$mvn]->model->getAttachments();
                $view->count = count($view->cursor);
                break;

            case 'ListEmailSent':
                $addressee = $this->getViewModelValue($mvn, 'addressee');
                $id = $this->getViewModelValue($mvn, 'id');
                $where = [
                    new DataBaseWhere('addressee', $addressee),
                    new DataBaseWhere('id', $id, '!=')
                ];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);

                // si no hay adjuntos ocultamos la pestaña
                if (false === $view->model->attachment) {
                    $this->setSettings('EmailSentAttachment', 'active', false);
                }
                break;
        }
    }
}
