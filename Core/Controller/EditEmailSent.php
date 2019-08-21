<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\Contacto;

/**
 * Controller to edit a single registrer of EmailSent
 *
 * @author Raul                 <raljopa@gmail.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class EditEmailSent extends EditController
{

    /**
     *
     * @return string
     */
    public function getModelClassName()
    {
        return 'EmailSent';
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
        $data['title'] = 'email-sent';
        $data['icon'] = 'fas fa-envelope';
        return $data;
    }

    /**
     * Redirects to the contact page of this email.
     */
    protected function contactAction()
    {
        $contact = new Contacto();
        $email = $this->getViewModelValue($this->getMainViewName(), 'addressee');
        $where = [new DataBaseWhere('email', $email)];
        if ($contact->loadFromCode('', $where)) {
            $this->redirect($contact->url());
            return;
        }

        $this->toolBox()->i18nLog()->warning('record-not-found');
    }

    /**
     * Loads views.
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        /// buttons
        $mainView = $this->getMainViewName();
        $newButton = [
            'action' => 'contact',
            'color' => 'info',
            'icon' => 'fas fa-address-book',
            'label' => 'contact',
            'type' => 'button',
        ];
        $this->addButton($mainView, $newButton);

        /// settings
        $this->setSettings($mainView, 'btnNew', false);

        /// other view
        $this->createViewOtherEmails();
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewOtherEmails($viewName = 'ListEmailSent')
    {
        $this->addListView($viewName, 'EmailSent', 'emails', 'fas fa-paper-plane');
        $this->views[$viewName]->addOrderBy(['date'], 'date', 2);
        $this->views[$viewName]->searchFields = ['body', 'subject'];

        /// settings
        $this->setSettings($viewName, 'btnNew', false);
    }

    /**
     * 
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

    /**
     * Load view data procedure
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListEmailSent':
                $addressee = $this->getViewModelValue($this->getMainViewName(), 'addressee');
                $id = $this->getViewModelValue($this->getMainViewName(), 'id');
                $where = [
                    new DataBaseWhere('addressee', $addressee),
                    new DataBaseWhere('id', $id, '!=')
                ];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }
}
