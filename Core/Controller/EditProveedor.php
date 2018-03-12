<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController;
use FacturaScripts\Core\Model;
use FacturaScripts\Core\Lib\IDFiscal;
use FacturaScripts\Core\Lib\RegimenIVA;

/**
 * Controller to edit a single item from the Proveedor model
 *
 * @author Nazca Networks <comercial@nazcanetworks.com>
 * @author Fco. Antonio Moreno Pérez <famphuelva@gmail.com>
 */
class EditProveedor extends ExtendedController\PanelController
{

    /**
     * Create and configure main view
     */
    private function addMainView()
    {
        $this->addEditView('Proveedor', 'EditProveedor', 'supplier');

        /// Load values option to Fiscal ID select input
        $columnFiscalID = $this->views['EditProveedor']->columnForName('fiscal-id');
        $columnFiscalID->widget->setValuesFromArray(IDFiscal::all());

        /// Load values option to VAT Type select input
        $columnVATType = $this->views['EditProveedor']->columnForName('vat-regime');
        $columnVATType->widget->setValuesFromArray(RegimenIVA::all());
    }

    /**
     * Create views
     */
    protected function createViews()
    {
        $this->addMainView();

        $this->addEditListView('DireccionProveedor', 'EditDireccionProveedor', 'addresses', 'fa-road');
        $this->addEditListView('CuentaBancoProveedor', 'EditCuentaBancoProveedor', 'bank-accounts', 'fa-university');
        $this->addListView('ArticuloProveedor', 'ListArticuloProveedor', 'products', 'fa-cubes');
        $this->addListView('FacturaProveedor', 'ListFacturaProveedor', 'invoices', 'fa-files-o');
        $this->addListView('AlbaranProveedor', 'ListAlbaranProveedor', 'delivery-notes', 'fa-files-o');
        $this->addListView('PedidoProveedor', 'ListPedidoProveedor', 'orders', 'fa-files-o');
        $this->addListView('PresupuestoProveedor', 'ListPresupuestoProveedor', 'estimations', 'fa-files-o');

        /// Disable columns
        $this->views['ListArticuloProveedor']->disableColumn('supplier', true);
        $this->views['ListFacturaProveedor']->disableColumn('supplier', true);
        $this->views['ListAlbaranProveedor']->disableColumn('supplier', true);
        $this->views['ListPedidoProveedor']->disableColumn('supplier', true);
        $this->views['ListPresupuestoProveedor']->disableColumn('supplier', true);
    }

    /**
     * Load view data
     *
     * @param string                      $keyView
     * @param ExtendedController\EditView $view
     */
    protected function loadData($keyView, $view)
    {
        switch ($keyView) {
            case 'EditProveedor':
                $code = $this->request->get('code');
                $view->loadData($code);
                break;

            case 'EditDireccionProveedor':
            case 'EditCuentaBancoProveedor':
            case 'ListArticuloProveedor':
            case 'ListFacturaProveedor':
            case 'ListAlbaranProveedor':
            case 'ListPedidoProveedor':
            case 'ListPresupuestoProveedor':
                $codproveedor = $this->getViewModelValue('EditProveedor', 'codproveedor');
                $where = [new DataBaseWhere('codproveedor', $codproveedor)];
                $view->loadData(false, $where);
                break;
        }
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'supplier';
        $pagedata['icon'] = 'fa-users';
        $pagedata['menu'] = 'purchases';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Returns the sum of the customer's total delivery notes.
     *
     * @param ExtendedController\EditView $view
     *
     * @return string
     */
    public function calcSupplierDeliveryNotes($view)
    {
        $where = [];
        $where[] = new DataBaseWhere('codproveedor', $this->getViewModelValue('EditProveedor', 'codproveedor'));
        $where[] = new DataBaseWhere('ptefactura', true);

        $totalModel = Model\TotalModel::all('albaranesprov', $where, ['total' => 'SUM(total)'], '')[0];

        return $this->divisaTools->format($totalModel->totals['total'], 2);
    }

    /**
     * Returns the sum of the client's total outstanding invoices.
     *
     * @param ExtendedController\EditView $view
     *
     * @return string
     */
    public function calcSupplierInvoicePending($view)
    {
        $where = [];
        $where[] = new DataBaseWhere('codproveedor', $this->getViewModelValue('EditProveedor', 'codproveedor'));
        $where[] = new DataBaseWhere('estado', 'Pagado', '<>');

        $totalModel = Model\TotalModel::all('recibosprov', $where, ['total' => 'SUM(importe)'], '')[0];

        return $this->divisaTools->format($totalModel->totals['total'], 2);
    }
}
