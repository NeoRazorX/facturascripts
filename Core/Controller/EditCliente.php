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
use FacturaScripts\Core\Lib\ExtendedController\ComercialContactController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\CustomerRiskTools;
use FacturaScripts\Dinamic\Lib\RegimenIVA;

/**
 * Controller to edit a single item from the Cliente model
 *
 * @author       Carlos García Gómez           <carlos@facturascripts.com>
 * @author       Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author       Fco. Antonio Moreno Pérez     <famphuelva@gmail.com>
 * @collaborator Daniel Fernández Giménez      <hola@danielfg.es>
 */
class EditCliente extends ComercialContactController
{
    /**
     * Returns the customer's risk on pending delivery notes.
     *
     * @return string
     */
    public function getDeliveryNotesRisk(): string
    {
        $codcliente = $this->getViewModelValue('EditCliente', 'codcliente');
        $total = empty($codcliente) ? 0 : CustomerRiskTools::getDeliveryNotesRisk($codcliente);
        return Tools::money($total);
    }

    public function getImageUrl(): string
    {
        $mvn = $this->getMainViewName();
        return $this->views[$mvn]->model->gravatar();
    }

    /**
     * Returns the customer's risk on unpaid invoices.
     *
     * @return string
     */
    public function getInvoicesRisk(): string
    {
        $codcliente = $this->getViewModelValue('EditCliente', 'codcliente');
        $total = empty($codcliente) ? 0 : CustomerRiskTools::getInvoicesRisk($codcliente);
        return Tools::money($total);
    }

    public function getModelClassName(): string
    {
        return 'Cliente';
    }

    /**
     * Returns the customer's risk on pending orders.
     *
     * @return string
     */
    public function getOrdersRisk(): string
    {
        $codcliente = $this->getViewModelValue('EditCliente', 'codcliente');
        $total = empty($codcliente) ? 0 : CustomerRiskTools::getOrdersRisk($codcliente);
        return Tools::money($total);
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'customer';
        $data['icon'] = 'fa-solid fa-users';
        return $data;
    }

    protected function createDocumentView(string $viewName, string $model, string $label)
    {
        $this->createCustomerListView($viewName, $model, $label);

        // botones
        $this->setSettings($viewName, 'btnPrint', true);
        $this->addButtonGroupDocument($viewName);
        $this->addButtonApproveDocument($viewName);
    }

    protected function createInvoiceView(string $viewName)
    {
        $this->createCustomerListView($viewName, 'FacturaCliente', 'invoices');

        // botones
        $this->setSettings($viewName, 'btnPrint', true);
        $this->addButtonLockInvoice($viewName);
    }

    /**
     * Create views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->createContactsView();
        $this->addEditListView('EditCuentaBancoCliente', 'CuentaBancoCliente', 'customer-banking-accounts', 'fa-solid fa-piggy-bank');

        if ($this->user->can('EditSubcuenta')) {
            $this->createSubaccountsView();
        }

        $this->createEmailsView();
        $this->createViewDocFiles();

        if ($this->user->can('EditFacturaCliente')) {
            $this->createInvoiceView('ListFacturaCliente');
            $this->createLineView('ListLineaFacturaCliente', 'LineaFacturaCliente');
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
        if ($this->user->can('EditReciboCliente')) {
            $this->createReceiptView('ListReciboCliente', 'ReciboCliente');
        }
    }

    /**
     * @return bool
     */
    protected function editAction()
    {
        $return = parent::editAction();
        if ($return && $this->active === $this->getMainViewName()) {
            $this->checkSubaccountLength($this->getModel()->codsubcuenta);

            // update contact email and phones when customer email or phones are updated
            $this->updateContact($this->views[$this->active]->model);
        }

        return $return;
    }

    /**
     * @return bool
     */
    protected function insertAction()
    {
        if (false === parent::insertAction()) {
            return false;
        }

        // redirect to return_url if return is defined
        $return_url = $this->request->query->get('return');
        if (empty($return_url)) {
            return true;
        }

        $model = $this->views[$this->active]->model;
        if (strpos($return_url, '?') === false) {
            $this->redirect($return_url . '?' . $model->primaryColumn() . '=' . $model->primaryColumnValue());
        } else {
            $this->redirect($return_url . '&' . $model->primaryColumn() . '=' . $model->primaryColumnValue());
        }

        return true;
    }

    /**
     * Load view data procedure
     *
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $mainViewName = $this->getMainViewName();
        $codcliente = $this->getViewModelValue($mainViewName, 'codcliente');
        $where = [new DataBaseWhere('codcliente', $codcliente)];

        switch ($viewName) {
            case 'EditCuentaBancoCliente':
                $view->loadData('', $where, ['codcuenta' => 'DESC']);
                break;

            case 'EditDireccionContacto':
                $view->loadData('', $where, ['idcontacto' => 'DESC']);
                break;

            case 'ListFacturaCliente':
                $view->loadData('', $where);
                $this->addButtonGenerateAccountingInvoices($viewName, $codcliente);
                break;

            case 'ListAlbaranCliente':
            case 'ListPedidoCliente':
            case 'ListPresupuestoCliente':
            case 'ListReciboCliente':
                $view->loadData('', $where);
                break;

            case 'ListLineaFacturaCliente':
                $inSQL = 'SELECT idfactura FROM facturascli WHERE codcliente = ' . $this->dataBase->var2str($codcliente);
                $where = [new DataBaseWhere('idfactura', $inSQL, 'IN')];
                $view->loadData('', $where);
                break;

            case $mainViewName:
                parent::loadData($viewName, $view);
                $this->loadLanguageValues($viewName);
                $this->loadExceptionVat($viewName);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

    protected function loadExceptionVat(string $viewName): void
    {
        $column = $this->views[$viewName]->columnForName('vat-exception');
        if ($column && $column->widget->getType() === 'select') {
            $column->widget->setValuesFromArrayKeys(RegimenIVA::allExceptions(), true, true);
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
        // Load values option to VAT Type select input
        $columnVATType = $this->views[$viewName]->columnForName('vat-regime');
        if ($columnVATType && $columnVATType->widget->getType() === 'select') {
            $columnVATType->widget->setValuesFromArrayKeys(RegimenIVA::all(), true);
        }

        // Model exists?
        if (false === $this->views[$viewName]->model->exists()) {
            $this->views[$viewName]->disableColumn('billing-address');
            $this->views[$viewName]->disableColumn('shipping-address');
            return;
        }

        // Search for client contacts
        $codcliente = $this->getViewModelValue($viewName, 'codcliente');
        $where = [new DataBaseWhere('codcliente', $codcliente)];
        $contacts = $this->codeModel->all('contactos', 'idcontacto', 'descripcion', false, $where);

        // Load values option to default billing address from client contacts list
        $columnBilling = $this->views[$viewName]->columnForName('billing-address');
        if ($columnBilling && $columnBilling->widget->getType() === 'select') {
            $columnBilling->widget->setValuesFromCodeModel($contacts);
        }

        // Load values option to default shipping address from client contacts list
        $columnShipping = $this->views[$viewName]->columnForName('shipping-address');
        if ($columnShipping && $columnShipping->widget->getType() === 'select') {
            $contacts2 = $this->codeModel->all('contactos', 'idcontacto', 'descripcion', true, $where);
            $columnShipping->widget->setValuesFromCodeModel($contacts2);
        }
    }
}
