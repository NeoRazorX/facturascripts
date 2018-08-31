<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Model\TotalModel;
use FacturaScripts\Core\Model\CodeModel;
use FacturaScripts\Core\Lib\IDFiscal;
use FacturaScripts\Core\Lib\RegimenIVA;

/**
 * Controller to edit a single item from the Cliente model
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 * @author Fco. Antonio Moreno Pérez <famphuelva@gmail.com>
 */
class EditCliente extends ExtendedController\PanelController
{

    /**
     * Returns the sum of the customer's total delivery notes.
     *
     * @param ExtendedController\EditView $view
     *
     * @return string
     */
    public function calcCustomerDeliveryNotes($view)
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
     * @param ExtendedController\EditView $view
     *
     * @return string
     */
    public function calcCustomerInvoicePending($view)
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
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'customer';
        $pagedata['icon'] = 'fa-users';
        $pagedata['menu'] = 'sales';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Create views
     */
    protected function createViews()
    {
        $this->createViewCustomer();

        $this->addListView('ListContacto', 'Contacto', 'addresses-and-contacts', 'fa-address-book');
        $this->addEditListView('EditCuentaBancoCliente', 'CuentaBancoCliente', 'customer-banking-accounts', 'fa-piggy-bank');
        $this->addListView('ListCliente', 'Cliente', 'same-group', 'fa-users');

        $this->addListView('ListFacturaCliente', 'FacturaCliente', 'invoices', 'fa-copy');
        $this->addListView('ListAlbaranCliente', 'AlbaranCliente', 'delivery-notes', 'fa-copy');
        $this->addListView('ListPedidoCliente', 'PedidoCliente', 'orders', 'fa-copy');
        $this->addListView('ListPresupuestoCliente', 'PresupuestoCliente', 'estimations', 'fa-copy');
        $this->addListView('ListLineaFacturaCliente', 'LineaFacturaCliente', 'products', 'fa-cubes');

        /// Disable columns
        $this->views['ListFacturaCliente']->disableColumn('customer', true);
        $this->views['ListAlbaranCliente']->disableColumn('customer', true);
        $this->views['ListPedidoCliente']->disableColumn('customer', true);
        $this->views['ListPresupuestoCliente']->disableColumn('customer', true);
        $this->views['ListLineaFacturaCliente']->disableColumn('order', true);
    }

    /**
     * Create and configure main view
     */
    protected function createViewCustomer()
    {
        $this->addEditView('EditCliente', 'Cliente', 'customer');

        /// Load values option to Fiscal ID select input
        $columnFiscalID = $this->views['EditCliente']->columnForName('fiscal-id');
        ///$columnFiscalID->widget->setValuesFromArray(IDFiscal::all());

        /// Load values option to VAT Type select input
        $columnVATType = $this->views['EditCliente']->columnForName('vat-regime');
        ///$columnVATType->widget->setValuesFromArray(RegimenIVA::all());
    }

    /**
     * Load view data procedure
     *
     * @param string                      $viewName
     * @param ExtendedController\EditView $view
     */
    protected function loadData($viewName, $view)
    {
        $limit = FS_ITEM_LIMIT;
        switch ($viewName) {
            case 'EditCliente':
                $codcliente = $this->request->get('code');
                $view->loadData($codcliente);

                /// Search for client contacts
                $where = [new DataBaseWhere('codcliente', $codcliente)];
                $contacts = CodeModel::all('contactos', 'idcontacto', 'descripcion', true, $where);

                /// Load values option to default billing address from client contacts list
                $columnBilling = $this->views['EditCliente']->columnForName('default-billing-address');
                ///$columnBilling->widget->setValuesFromCodeModel($contacts);

                /// Load values option to default shipping address from client contacts list
                $columnShipping = $this->views['EditCliente']->columnForName('default-shipping-address');
                ///$columnShipping->widget->setValuesFromCodeModel($contacts);
                break;

            case 'ListCliente':
                $codgrupo = $this->getViewModelValue('EditCliente', 'codgrupo');
                if (!empty($codgrupo)) {
                    $where = [new DataBaseWhere('codgrupo', $codgrupo)];
                    $view->loadData('', $where);
                }
                break;

            case 'EditCuentaBancoCliente':
            case 'ListContacto':
                $limit = 0;
            /// no break
            case 'ListFacturaCliente':
            case 'ListAlbaranCliente':
            case 'ListPedidoCliente':
            case 'ListPresupuestoCliente':
                $codcliente = $this->getViewModelValue('EditCliente', 'codcliente');
                $where = [new DataBaseWhere('codcliente', $codcliente)];
                $view->loadData('', $where, [], 0, $limit);
                break;

            case 'ListLineaFacturaCliente':
                $codcliente = $this->getViewModelValue('EditCliente', 'codcliente');
                $inSQL = 'SELECT idfactura FROM facturascli WHERE codcliente = ' . $this->dataBase->var2str($codcliente);
                $where = [new DataBaseWhere('idfactura', $inSQL, 'IN')];
                $view->loadData('', $where, [], 0, $limit);
                break;
        }
    }
}
