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
use FacturaScripts\Core\Lib\ExtendedController;
use FacturaScripts\Dinamic\Lib\IDFiscal;
use FacturaScripts\Dinamic\Lib\RegimenIVA;
use FacturaScripts\Dinamic\Model\TotalModel;

/**
 * Controller to edit a single item from the Cliente model
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Artex Trading sa             <jcuello@artextrading.com>
 * @author Fco. Antonio Moreno Pérez    <famphuelva@gmail.com>
 */
class EditCliente extends ExtendedController\EditController
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
     * Returns the sum of the client's total outstanding invoices.
     *
     * @return string
     */
    public function calcCustomerInvoicePending()
    {
        $where = [
            new DataBaseWhere('codcliente', $this->getViewModelValue('EditCliente', 'codcliente')),
            new DataBaseWhere('pagado', false)
        ];

        $totalModel = TotalModel::all('facturascli', $where, ['total' => 'SUM(total)'], '')[0];
        $divisaTools = new DivisaTools();
        return $divisaTools->format($totalModel->totals['total'], 2);
    }

    /**
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
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'customer';
        $pagedata['icon'] = 'fas fa-users';
        $pagedata['menu'] = 'sales';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Create views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->addListView('ListContacto', 'Contacto', 'addresses-and-contacts', 'fas fa-address-book');
        $this->addEditListView('EditCuentaBancoCliente', 'CuentaBancoCliente', 'customer-banking-accounts', 'fas fa-piggy-bank');
        $this->addListView('ListSubcuenta', 'Subcuenta', 'subaccounts', 'fas fa-book');
        $this->addListView('ListFacturaCliente', 'FacturaCliente', 'invoices', 'fas fa-copy');
        $this->addListView('ListLineaFacturaCliente', 'LineaFacturaCliente', 'products', 'fas fa-cubes');
        $this->addListView('ListAlbaranCliente', 'AlbaranCliente', 'delivery-notes', 'fas fa-copy');
        $this->addListView('ListPedidoCliente', 'PedidoCliente', 'orders', 'fas fa-copy');
        $this->addListView('ListPresupuestoCliente', 'PresupuestoCliente', 'estimations', 'fas fa-copy');
        $this->addListView('ListCliente', 'Cliente', 'same-group', 'fas fa-users');

        /// Disable columns
        $this->views['ListFacturaCliente']->disableColumn('customer', true);
        $this->views['ListAlbaranCliente']->disableColumn('customer', true);
        $this->views['ListPedidoCliente']->disableColumn('customer', true);
        $this->views['ListPresupuestoCliente']->disableColumn('customer', true);
        $this->views['ListLineaFacturaCliente']->disableColumn('order', true);

        /// Disable buttons
        $this->setSettings('ListCliente', 'btnNew', false);
        $this->setSettings('ListCliente', 'btnDelete', false);
        $this->setSettings('ListSubcuenta', 'btnNew', false);
    }

    /**
     * Load view data procedure
     *
     * @param string                      $viewName
     * @param ExtendedController\EditView $view
     */
    protected function loadData($viewName, $view)
    {
        $codcliente = $this->getViewModelValue('EditCliente', 'codcliente');
        switch ($viewName) {
            case 'EditCliente':
                parent::loadData($viewName, $view);
                $this->setCustomWidgetValues();
                break;

            case 'EditCuentaBancoCliente':
            case 'ListAlbaranCliente':
            case 'ListContacto':
            case 'ListFacturaCliente':
            case 'ListPedidoCliente':
            case 'ListPresupuestoCliente':
                $where = [new DataBaseWhere('codcliente', $codcliente)];
                $view->loadData('', $where);
                break;

            case 'ListCliente':
                $codgrupo = $this->getViewModelValue('EditCliente', 'codgrupo');
                if (!empty($codgrupo)) {
                    $where = [new DataBaseWhere('codgrupo', $codgrupo)];
                    $view->loadData('', $where);
                }
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

    protected function setCustomWidgetValues()
    {
        /// Search for client contacts
        $codcliente = $this->getViewModelValue('EditCliente', 'codcliente');
        $where = [new DataBaseWhere('codcliente', $codcliente)];
        $contacts = $this->codeModel->all('contactos', 'idcontacto', 'descripcion', false, $where);

        /// Load values option to default billing address from client contacts list
        $columnBilling = $this->views['EditCliente']->columnForName('billing-address');
        $columnBilling->widget->setValuesFromCodeModel($contacts);

        /// Load values option to default shipping address from client contacts list
        $columnShipping = $this->views['EditCliente']->columnForName('shipping-address');
        $columnShipping->widget->setValuesFromCodeModel($contacts);

        /// Load values option to Fiscal ID select input
        $columnFiscalID = $this->views['EditCliente']->columnForName('fiscal-id');
        $columnFiscalID->widget->setValuesFromArray(IDFiscal::all());

        /// Load values option to VAT Type select input
        $columnVATType = $this->views['EditCliente']->columnForName('vat-regime');
        $columnVATType->widget->setValuesFromArrayKeys(RegimenIVA::all());
    }
}
