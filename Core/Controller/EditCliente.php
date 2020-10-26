<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Lib\CustomerRiskTools;
use FacturaScripts\Dinamic\Lib\RegimenIVA;

/**
 * Controller to edit a single item from the Cliente model
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Artex Trading sa             <jcuello@artextrading.com>
 * @author Fco. Antonio Moreno Pérez    <famphuelva@gmail.com>
 */
class EditCliente extends ComercialContactController
{

    /**
     * Returns the customer's risk on pending delivery notes.
     *
     * @return string
     */
    public function getDeliveryNotesRisk()
    {
        $codcliente = $this->getViewModelValue('EditCliente', 'codcliente');
        $total = CustomerRiskTools::getDeliveryNotesRisk($codcliente);
        return $this->toolBox()->coins()->format($total);
    }

    /**
     * Returns the customer's risk on unpaid invoices.
     *
     * @return string
     */
    public function getInvoicesRisk()
    {
        $codcliente = $this->getViewModelValue('EditCliente', 'codcliente');
        $total = CustomerRiskTools::getInvoicesRisk($codcliente);
        return $this->toolBox()->coins()->format($total);
    }

    /**
     * Returns the class name of the model to use.
     *
     * @return string
     */
    public function getModelClassName()
    {
        return 'Cliente';
    }

    /**
     * Returns the customer's risk on pending orders.
     *
     * @return string
     */
    public function getOrdersRisk()
    {
        $codcliente = $this->getViewModelValue('EditCliente', 'codcliente');
        $total = CustomerRiskTools::getOrdersRisk($codcliente);
        return $this->toolBox()->coins()->format($total);
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
        $data['title'] = 'customer';
        $data['icon'] = 'fas fa-users';
        return $data;
    }

    /**
     *
     * @param string $viewName
     * @param string $model
     * @param string $label
     */
    protected function createDocumentView($viewName, $model, $label)
    {
        $this->createCustomerListView($viewName, $model, $label);
        $this->addButtonGroupDocument($viewName);
        $this->addButtonApproveDocument($viewName);
    }

    /**
     *
     * @param string $viewName
     */
    protected function createInvoiceView($viewName)
    {
        $this->createCustomerListView($viewName, 'FacturaCliente', 'invoices');
        $this->addButtonLockInvoice($viewName);
    }

    /**
     * Create views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->createContactsView();
        $this->addEditListView('EditCuentaBancoCliente', 'CuentaBancoCliente', 'customer-banking-accounts', 'fas fa-piggy-bank');
        $this->createSubaccountsView();
        $this->createEmailsView();

        $this->createInvoiceView('ListFacturaCliente');
        $this->createLineView('ListLineaFacturaCliente', 'LineaFacturaCliente');
        $this->createDocumentView('ListAlbaranCliente', 'AlbaranCliente', 'delivery-notes');
        $this->createDocumentView('ListPedidoCliente', 'PedidoCliente', 'orders');
        $this->createDocumentView('ListPresupuestoCliente', 'PresupuestoCliente', 'estimations');
        $this->createReceiptView('ListReciboCliente', 'ReciboCliente');
    }

    /**
     *
     * @return bool
     */
    protected function editAction()
    {
        $return = parent::editAction();
        if ($return && $this->active === $this->getMainViewName()) {
            /// update contact emal and phones when customer email or phones are updated
            $this->updateContact($this->views[$this->active]->model);
        }

        return $return;
    }

    /**
     *
     * @return bool
     */
    protected function insertAction()
    {
        if (parent::insertAction()) {
            /// redirect to returnUrl if return is defined
            $returnUrl = $this->request->query->get('return');
            if (!empty($returnUrl)) {
                $model = $this->views[$this->active]->model;
                $this->redirect($returnUrl . '?' . $model->primaryColumn() . '=' . $model->primaryColumnValue());
            }

            return true;
        }

        return false;
    }

    /**
     * Load view data procedure
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $codcliente = $this->getViewModelValue('EditCliente', 'codcliente');
        $where = [new DataBaseWhere('codcliente', $codcliente)];

        switch ($viewName) {
            case 'EditCuentaBancoCliente':
                $view->loadData('', $where, ['codcuenta' => 'DESC']);
                break;

            case 'EditDireccionContacto':
                $view->loadData('', $where, ['idcontacto' => 'DESC']);
                break;

            case 'ListAlbaranCliente':
            case 'ListFacturaCliente':
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

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

    /**
     *
     * @param string $viewName
     */
    protected function setCustomWidgetValues($viewName)
    {
        /// Load values option to VAT Type select input
        $columnVATType = $this->views[$viewName]->columnForName('vat-regime');
        if ($columnVATType) {
            $columnVATType->widget->setValuesFromArrayKeys(RegimenIVA::all());
        }

        /// Model exists?
        if (!$this->views[$viewName]->model->exists()) {
            $this->views[$viewName]->disableColumn('billing-address');
            $this->views[$viewName]->disableColumn('shipping-address');
            return;
        }

        /// Search for client contacts
        $codcliente = $this->getViewModelValue($viewName, 'codcliente');
        $where = [new DataBaseWhere('codcliente', $codcliente)];
        $contacts = $this->codeModel->all('contactos', 'idcontacto', 'descripcion', false, $where);

        /// Load values option to default billing address from client contacts list
        $columnBilling = $this->views[$viewName]->columnForName('billing-address');
        if ($columnBilling) {
            $columnBilling->widget->setValuesFromCodeModel($contacts);
        }

        /// Load values option to default shipping address from client contacts list
        $columnShipping = $this->views[$viewName]->columnForName('shipping-address');
        if ($columnShipping) {
            $contacts2 = $this->codeModel->all('contactos', 'idcontacto', 'descripcion', true, $where);
            $columnShipping->widget->setValuesFromCodeModel($contacts2);
        }
    }
}
