<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Controller to edit a single item from the Contacto model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class EditContacto extends EditController
{

    /**
     * 
     * @return string
     */
    public function getImageUrl()
    {
        return $this->views['EditContacto']->model->gravatar();
    }

    /**
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'Contacto';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'contact';
        $data['icon'] = 'fas fa-address-book';
        return $data;
    }

    /**
     * 
     * @param string $viewName
     */
    protected function addConversionButtons($viewName)
    {
        if (empty($this->views[$viewName]->model->codcliente)) {
            $this->addButton($viewName, [
                'action' => 'convert-into-customer',
                'color' => 'success',
                'icon' => 'fas fa-user-check',
                'label' => 'convert-into-customer'
            ]);
        }

        if (empty($this->views[$viewName]->model->codproveedor)) {
            $this->addButton($viewName, [
                'action' => 'convert-into-supplier',
                'color' => 'success',
                'icon' => 'fas fa-user-cog',
                'label' => 'convert-into-supplier'
            ]);
        }
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createEmailsView($viewName = 'ListEmailSent')
    {
        $this->addListView($viewName, 'EmailSent', 'emails-sent', 'fas fa-envelope');
        $this->views[$viewName]->addOrderBy(['date'], 'date', 2);
        $this->views[$viewName]->addSearchFields(['addressee', 'body', 'subject']);

        /// disable column
        $this->views[$viewName]->disableColumn('to');

        /// disable buttons
        $this->setSettings($viewName, 'btnNew', false);
    }

    /**
     * Create views.
     */
    protected function createViews()
    {
        parent::createViews();
        $this->createEmailsView();
    }

    /**
     * 
     * @return bool
     */
    protected function editAction()
    {
        $return = parent::editAction();
        if ($return && $this->active === $this->getMainViewName()) {
            $this->updateRelations($this->views[$this->active]->model);
        }

        return $return;
    }

    /**
     * Run the controller after actions
     *
     * @param string $action
     */
    protected function execAfterAction($action)
    {
        switch ($action) {
            case 'convert-into-customer':
                $customer = $this->views['EditContacto']->model->getCustomer();
                if ($customer->exists()) {
                    $this->toolBox()->i18nLog()->notice('record-updated-correctly');
                    $this->redirect($customer->url() . '&action=save-ok');
                    break;
                }

                $this->toolBox()->i18nLog()->error('record-save-error');
                break;

            case 'convert-into-supplier':
                $supplier = $this->views['EditContacto']->model->getSupplier();
                if ($supplier->exists()) {
                    $this->toolBox()->i18nLog()->notice('record-updated-correctly');
                    $this->redirect($supplier->url() . '&action=save-ok');
                    break;
                }

                $this->toolBox()->i18nLog()->error('record-save-error');
                break;

            default:
                parent::execAfterAction($action);
        }
    }

    /**
     * 
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $mainViewName = $this->getMainViewName();

        switch ($viewName) {
            case 'ListEmailSent':
                $email = $this->getViewModelValue($mainViewName, 'email');
                $where = [new DataBaseWhere('addressee', $email)];
                $view->loadData('', $where);
                break;

            case $mainViewName:
                parent::loadData($viewName, $view);
                if ($view->model->exists()) {
                    $this->addConversionButtons($viewName);
                }
                break;
        }
    }

    /**
     * 
     * @param Contacto $contact
     */
    protected function updateRelations($contact)
    {
        $customer = $contact->getCustomer(false);
        if ($customer->idcontactofact == $contact->idcontacto && $customer->exists()) {
            $customer->email = $contact->email;
            $customer->fax = $contact->fax;
            $customer->telefono1 = $contact->telefono1;
            $customer->telefono2 = $contact->telefono2;
            $customer->save();
        }

        $supplier = $contact->getSupplier(false);
        if ($supplier->idcontacto == $contact->idcontacto && $supplier->exists()) {
            $supplier->email = $contact->email;
            $supplier->fax = $contact->fax;
            $supplier->telefono1 = $contact->telefono1;
            $supplier->telefono2 = $contact->telefono2;
            $supplier->save();
        }
    }
}
