<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\DivisaTools;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\ComercialContactController;
use FacturaScripts\Dinamic\Lib\RegimenIVA;
use FacturaScripts\Dinamic\Model\TotalModel;

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
     * Returns the sum of the customer's total delivery notes.
     *
     * @return string
     */
    public function calcCustomerDeliveryNotes()
    {
        $where = [
            new DataBaseWhere('codcliente', $this->getViewModelValue('EditCliente', 'codcliente')),
            new DataBaseWhere('editable', true)
        ];

        $totalModel = TotalModel::all('albaranescli', $where, ['total' => 'SUM(total)'], '')[0];
        $divisaTools = new DivisaTools();
        return $divisaTools->format($totalModel->totals['total']);
    }

    /**
     * Returns the sum of the customer's total outstanding invoices.
     *
     * @return string
     */
    public function calcCustomerInvoicePending()
    {
        $where = [
            new DataBaseWhere('codcliente', $this->getViewModelValue('EditCliente', 'codcliente')),
            new DataBaseWhere('pagada', false)
        ];

        $totalModel = TotalModel::all('facturascli', $where, ['total' => 'SUM(total)'], '')[0];
        $divisaTools = new DivisaTools();
        return $divisaTools->format($totalModel->totals['total'], 2);
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
     * Create views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->createContactsView();
        $this->addEditListView('EditCuentaBancoCliente', 'CuentaBancoCliente', 'customer-banking-accounts', 'fas fa-piggy-bank');
        $this->createSubaccountsView();

        $this->createCustomerListView('ListFacturaCliente', 'FacturaCliente', 'invoices');
        $this->createLineView('ListLineaFacturaCliente', 'LineaFacturaCliente');
        $this->createCustomerListView('ListAlbaranCliente', 'AlbaranCliente', 'delivery-notes');
        $this->createCustomerListView('ListPedidoCliente', 'PedidoCliente', 'orders');
        $this->createCustomerListView('ListPresupuestoCliente', 'PresupuestoCliente', 'estimations');
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
            $this->updateContact($this->views[$this->active]->model);
        }

        return $return;
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
        switch ($viewName) {
            case 'EditCliente':
                parent::loadData($viewName, $view);
                $this->setCustomWidgetValues($viewName);
                break;

            case 'EditCuentaBancoCliente':
            case 'ListAlbaranCliente':
            case 'ListContacto':
            case 'ListFacturaCliente':
            case 'ListPedidoCliente':
            case 'ListPresupuestoCliente':
            case 'ListReciboCliente':
                $where = [new DataBaseWhere('codcliente', $codcliente)];
                $view->loadData('', $where);
                break;

            case 'ListLineaFacturaCliente':
                $inSQL = 'SELECT idfactura FROM facturascli WHERE codcliente = ' . $this->dataBase->var2str($codcliente);
                $where = [new DataBaseWhere('idfactura', $inSQL, 'IN')];
                $view->loadData('', $where);
                break;

            case 'ListSubcuenta':
                $codsubcuenta = $this->getViewModelValue('EditCliente', 'codsubcuenta');
                $where = [new DataBaseWhere('codsubcuenta', $codsubcuenta)];
                $view->loadData('', $where);
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
            $columnShipping->widget->setValuesFromCodeModel($contacts);
        }
    }
}
