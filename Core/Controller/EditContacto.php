<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Lib\ExtendedController\DocFilesTrait;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Dinamic\Model\RoleAccess;

/**
 * Controller to edit a single item from the Contacto model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class EditContacto extends EditController
{
    use DocFilesTrait;

    public function getImageUrl(): string
    {
        $mvn = $this->getMainViewName();
        return $this->views[$mvn]->model->gravatar();
    }

    public function getModelClassName(): string
    {
        return 'Contacto';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'contact';
        $data['icon'] = 'fas fa-address-book';
        return $data;
    }

    protected function addConversionButtons(string $viewName, BaseView $view)
    {
        $accessClient = $this->getRolePermissions('EditCliente');
        if (empty($view->model->codcliente) && $accessClient['allowupdate']) {
            $this->addButton($viewName, [
                'action' => 'convert-into-customer',
                'color' => 'success',
                'icon' => 'fas fa-user-check',
                'label' => 'convert-into-customer'
            ]);
        }

        $accessSupplier = $this->getRolePermissions('EditProveedor');
        if (empty($view->model->codproveedor) && $accessSupplier['allowupdate']) {
            $this->addButton($viewName, [
                'action' => 'convert-into-supplier',
                'color' => 'success',
                'icon' => 'fas fa-user-cog',
                'label' => 'convert-into-supplier'
            ]);
        }
    }

    protected function checkViesAction(): bool
    {
        $model = $this->getModel();
        if (false === $model->loadFromCode($this->request->get('code'))) {
            return true;
        }

        $model->checkVies();
        return true;
    }

    protected function createCustomerAction()
    {
        $access = $this->getRolePermissions('EditCliente');
        if (false === $access['allowupdate']) {
            self::toolBox()::i18nLog()->warning('not-allowed-update');
            return;
        }

        $mvn = $this->getMainViewName();
        $customer = $this->views[$mvn]->model->getCustomer();
        if ($customer->exists()) {
            $this->toolBox()->i18nLog()->notice('record-updated-correctly');
            $this->redirect($customer->url() . '&action=save-ok');
            return;
        }

        $this->toolBox()->i18nLog()->error('record-save-error');
    }

    protected function createEmailsView(string $viewName = 'ListEmailSent')
    {
        $this->addListView($viewName, 'EmailSent', 'emails-sent', 'fas fa-envelope');
        $this->views[$viewName]->addOrderBy(['date'], 'date', 2);
        $this->views[$viewName]->addSearchFields(['addressee', 'body', 'subject']);

        // disable column
        $this->views[$viewName]->disableColumn('to');

        // disable buttons
        $this->setSettings($viewName, 'btnNew', false);
    }

    protected function createSupplierAction()
    {
        $access = $this->getRolePermissions('EditProveedor');
        if (false === $access['allowupdate']) {
            self::toolBox()::i18nLog()->warning('not-allowed-update');
            return;
        }

        $mvn = $this->getMainViewName();
        $supplier = $this->views[$mvn]->model->getSupplier();
        if ($supplier->exists()) {
            $this->toolBox()->i18nLog()->notice('record-updated-correctly');
            $this->redirect($supplier->url() . '&action=save-ok');
            return;
        }

        $this->toolBox()->i18nLog()->error('record-save-error');
    }

    /**
     * Create views.
     */
    protected function createViews()
    {
        parent::createViews();
        $this->createEmailsView();
        $this->createViewDocFiles();
    }

    /**
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
                $this->createCustomerAction();
                break;

            case 'convert-into-supplier':
                $this->createSupplierAction();
                break;

            default:
                parent::execAfterAction($action);
        }
    }

    /**
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action): bool
    {
        switch ($action) {
            case 'add-file':
                return $this->addFileAction();

            case 'check-vies':
                return $this->checkViesAction();

            case 'delete-file':
                return $this->deleteFileAction();

            case 'edit-file':
                return $this->editFileAction();

            case 'unlink-file':
                return $this->unlinkFileAction();
        }

        return parent::execPreviousAction($action);
    }

    protected function getRolePermissions(string $pageName): array
    {
        $access = [
            'allowdelete' => $this->user->admin,
            'allowupdate' => $this->user->admin,
            'onlyownerdata' => $this->user->admin
        ];
        foreach (RoleAccess::allFromUser($this->user->nick, $pageName) as $rolesPageUser) {
            if ($rolesPageUser->allowdelete) {
                $access['allowdelete'] = true;
            }
            if ($rolesPageUser->allowupdate) {
                $access['allowupdate'] = true;
            }
            if ($rolesPageUser->onlyownerdata) {
                $access['onlyownerdata'] = true;
            }
        }
        return $access;
    }

    /**
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $mainViewName = $this->getMainViewName();

        switch ($viewName) {
            case 'docfiles':
                $this->loadDataDocFiles($view, $this->getModelClassName(), $this->getModel()->primaryColumnValue());
                break;

            case 'ListEmailSent':
                $email = $this->getViewModelValue($mainViewName, 'email');
                $where = [new DataBaseWhere('addressee', $email)];
                $view->loadData('', $where);
                $this->setSettings($viewName, 'active', $view->count > 0);
                break;

            case $mainViewName:
                parent::loadData($viewName, $view);
                $this->loadLanguageValues($viewName);
                if (false === $view->model->exists()) {
                    break;
                }
                if ($this->permissions->allowUpdate) {
                    $this->addConversionButtons($viewName, $view);
                }
                $this->addButton($viewName, [
                    'action' => 'check-vies',
                    'color' => 'info',
                    'icon' => 'fas fa-check-double',
                    'label' => 'check-vies'
                ]);
                break;
        }
    }

    /**
     * Load the available language values from translator.
     */
    protected function loadLanguageValues(string $viewName)
    {
        $columnLangCode = $this->views[$viewName]->columnForName('language');
        if ($columnLangCode && $columnLangCode->widget->getType() === 'select') {
            $langs = [];
            foreach ($this->toolBox()->i18n()->getAvailableLanguages() as $key => $value) {
                $langs[] = ['value' => $key, 'title' => $value];
            }

            $columnLangCode->widget->setValuesFromArray($langs, false, true);
        }
    }

    /**
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
