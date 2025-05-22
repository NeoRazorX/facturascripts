<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Lib\ExtendedController\ComercialContactController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Agente;
use FacturaScripts\Dinamic\Model\TotalModel;

/**
 * Controller to edit a single item from the Agente model
 *
 * @author Carlos Garcia Gomez            <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal  <yopli2000@gmail.com>
 * @collaborator Daniel Fernández Giménez <hola@danielfg.es>
 */
class EditAgente extends ComercialContactController
{
    /**
     * Returns the sum of the agent's total outstanding invoices.
     *
     * @return string
     */
    public function calcAgentInvoicePending(): string
    {
        $where = [
            new DataBaseWhere('codagente', $this->getViewModelValue($this->getMainViewName(), 'codagente')),
            new DataBaseWhere('pagada', false)
        ];

        $totalModel = TotalModel::all('facturascli', $where, ['total' => 'SUM(total)'], '')[0];
        return Tools::money($totalModel->totals['total'], 2);
    }

    public function getModelClassName(): string
    {
        return 'Agente';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'agent';
        $data['icon'] = 'fa-solid fa-user-tie';
        return $data;
    }

    protected function createContactView(string $viewName = 'EditContacto'): void
    {
        $this->addEditView($viewName, 'Contacto', 'contact', 'fa fa-address-book')
            ->disableColumn('agent')
            ->disableColumn('company')
            ->disableColumn('fiscal-id')
            ->disableColumn('fiscal-number')
            ->disableColumn('position');

        // disable delete button
        $this->tab($viewName)->setSettings('btnDelete', false);
    }

    protected function createCustomerView(string $viewName = 'ListCliente'): void
    {
        $this->addListView($viewName, 'Cliente', 'customers', 'fa-solid fa-users')
            ->addSearchFields(['cifnif', 'codcliente', 'email', 'nombre', 'observaciones', 'razonsocial', 'telefono1', 'telefono2'])
            ->addOrderBy(['codcliente'], 'code')
            ->addOrderBy(['nombre'], 'name', 1);

        // disable buttons
        $this->tab($viewName)
            ->setSettings('btnDelete', false)
            ->setSettings('btnNew', false);
    }

    protected function createDocumentView(string $viewName, string $model, string $label): void
    {
        $this->createCustomerListView($viewName, $model, $label);

        // botones
        $this->tab($viewName)->setSettings('btnPrint', true);
        $this->addButtonGroupDocument($viewName);
        $this->addButtonApproveDocument($viewName);
    }

    protected function createEmailsView(string $viewName = 'ListEmailSent'): void
    {
        $this->addListView($viewName, 'EmailSent', 'emails-sent', 'fa-solid fa-envelope')
            ->addSearchFields(['addressee', 'body', 'subject'])
            ->addOrderBy(['date'], 'date', 2);


        // disable column
        $this->tab($viewName)->disableColumn('to');

        // disable buttons
        $this->tab($viewName)->setSettings('btnNew', false);
    }

    protected function createInvoiceView(string $viewName): void
    {
        $this->createCustomerListView($viewName, 'FacturaCliente', 'invoices');

        // botones
        $this->tab($viewName)->setSettings('btnPrint', true);
        $this->addButtonLockInvoice($viewName);
    }

    /**
     * Load Views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->createContactView();
        $this->createCustomerView();
        $this->createEmailsView();

        if ($this->user->can('EditFacturaCliente')) {
            $this->createInvoiceView('ListFacturaCliente');
        }
        if ($this->user->can('EditAlbaranCliente')) {
            $this->createDocumentView('ListAlbaranCliente', 'AlbaranCliente', 'delivery-notes');
        }
        if ($this->user->can('EditPedidoCliente')) {
            $this->createDocumentView('ListPedidoCliente', 'PedidoCliente', 'orders');
        }
        if ($this->user->can('EditPresupuestoCliente')) {
            $this->createDocumentView('ListPresupuestoCliente', 'PresupuestoCliente', 'estimations');
        }
    }

    protected function editAction(): bool
    {
        $return = parent::editAction();
        if ($return && $this->active == 'EditContacto') {
            // update agent data when contact data is updated
            $agente = new Agente();
            $where = [new DataBaseWhere('idcontacto', $this->views[$this->active]->model->idcontacto)];
            if ($agente->loadFromCode('', $where)) {
                $agente->email = $this->views[$this->active]->model->email;
                $agente->telefono1 = $this->views[$this->active]->model->telefono1;
                $agente->telefono2 = $this->views[$this->active]->model->telefono2;
                $agente->save();
            }
        }

        return $return;
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
            case 'EditContacto':
                $idcontacto = $this->getViewModelValue($mvn, 'idcontacto');
                if (empty($idcontacto)) {
                    $view->setSettings('active', false);
                    break;
                }
                $where = [new DataBaseWhere('idcontacto', $idcontacto)];
                $view->loadData('', $where);
                $this->loadLanguageValues($viewName);
                break;

            case 'ListAlbaranCliente':
            case 'ListCliente':
            case 'ListFacturaCliente':
            case 'ListPedidoCliente':
            case 'ListPresupuestoCliente':
                $codagente = $this->getViewModelValue($mvn, 'codagente');
                $where = [new DataBaseWhere('codagente', $codagente)];
                $view->loadData('', $where);
                break;

            case 'ListEmailSent':
                $email = $this->getViewModelValue($mvn, 'email');
                if (empty($email)) {
                    $view->setSettings('active', false);
                    break;
                }

                $where = [new DataBaseWhere('addressee', $email)];
                $view->loadData('', $where);

                // añadimos un botón para enviar un nuevo email
                $this->addButton($viewName, [
                    'action' => 'SendMail?email=' . $email,
                    'color' => 'success',
                    'icon' => 'fa-solid fa-envelope',
                    'label' => 'send',
                    'type' => 'link'
                ]);
                break;

            case $mvn:
                parent::loadData($viewName, $view);
                if (false === $view->model->exists()) {
                    $view->disableColumn('contact');
                }
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
            foreach (Tools::lang()->getAvailableLanguages() as $key => $value) {
                $langs[] = ['value' => $key, 'title' => $value];
            }

            $columnLangCode->widget->setValuesFromArray($langs, false, true);
        }
    }

    protected function setCustomWidgetValues(string $viewName)
    {
        ;
    }
}
