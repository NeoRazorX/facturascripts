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
use FacturaScripts\Dinamic\Lib\IDFiscal;
use FacturaScripts\Dinamic\Lib\RegimenIVA;
use FacturaScripts\Dinamic\Model\TotalModel;
use FacturaScripts\Dinamic\Model\CodeModel;

/**
 * Controller to edit a single item from the Proveedor model
 *
 * @author Nazca Networks               <comercial@nazcanetworks.com>
 * @author Fco. Antonio Moreno Pérez    <famphuelva@gmail.com>
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 */
class EditProveedor extends ExtendedController\EditController
{

    /**
     * Returns the sum of the customer's total delivery notes.
     *
     * @return string
     */
    public function calcSupplierDeliveryNotes()
    {
        $where = [
            new DataBaseWhere('codproveedor', $this->getViewModelValue('EditProveedor', 'codproveedor')),
            new DataBaseWhere('editable', true)
        ];

        $totalModel = TotalModel::all('albaranesprov', $where, ['total' => 'SUM(total)'], '')[0];

        $divisaTools = new DivisaTools();
        return $divisaTools->format($totalModel->totals['total'], 2);
    }

    /**
     * Returns the sum of the client's total outstanding invoices.
     *
     * @return string
     */
    public function calcSupplierInvoicePending()
    {
        $where = [
            new DataBaseWhere('codproveedor', $this->getViewModelValue('EditProveedor', 'codproveedor')),
            new DataBaseWhere('pagado', false)
        ];

        $totalModel = TotalModel::all('facturasprov', $where, ['total' => 'SUM(total)'], '')[0];

        $divisaTools = new DivisaTools();
        return $divisaTools->format($totalModel->totals['total'], 2);
    }

    /**
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'Proveedor';
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
        $pagedata['icon'] = 'fas fa-users';
        $pagedata['menu'] = 'purchases';
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
        $this->addEditListView('EditCuentaBancoProveedor', 'CuentaBancoProveedor', 'bank-accounts', 'fas fa-piggy-bank');
        $this->addListView('ListFacturaProveedor', 'FacturaProveedor', 'invoices', 'fas fa-copy');
        $this->addListView('ListLineaFacturaProveedor', 'LineaFacturaCliente', 'products', 'fas fa-cubes');
        $this->addListView('ListAlbaranProveedor', 'AlbaranProveedor', 'delivery-notes', 'fas fa-copy');
        $this->addListView('ListPedidoProveedor', 'PedidoProveedor', 'orders', 'fas fa-copy');
        $this->addListView('ListPresupuestoProveedor', 'PresupuestoProveedor', 'estimations', 'fas fa-copy');

        /// Disable columns
        $this->views['ListFacturaProveedor']->disableColumn('supplier', true);
        $this->views['ListAlbaranProveedor']->disableColumn('supplier', true);
        $this->views['ListPedidoProveedor']->disableColumn('supplier', true);
        $this->views['ListPresupuestoProveedor']->disableColumn('supplier', true);
    }

    /**
     * Load view data
     *
     * @param string                      $viewName
     * @param ExtendedController\EditView $view
     */
    protected function loadData($viewName, $view)
    {
        $codproveedor = $this->getViewModelValue('EditProveedor', 'codproveedor');
        switch ($viewName) {
            case 'EditProveedor':
                parent::loadData($viewName, $view);
                $code = $this->getViewModelValue('EditProveedor', 'codproveedor');
                $this->setCustomWidgetValues($code);
                break;

            case 'ListContacto':
            case 'EditCuentaBancoProveedor':
            case 'ListFacturaProveedor':
            case 'ListAlbaranProveedor':
            case 'ListPedidoProveedor':
            case 'ListPresupuestoProveedor':
                $where = [new DataBaseWhere('codproveedor', $codproveedor)];
                $view->loadData('', $where);
                break;

            case 'ListLineaFacturaProveedor':
                $inSQL = 'SELECT idfactura FROM facturasprov WHERE codproveedor = ' . $this->dataBase->var2str($codproveedor);
                $where = [new DataBaseWhere('idfactura', $inSQL, 'IN')];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

    protected function setCustomWidgetValues($code)
    {
        /// Load values option to Fiscal ID select input
        $columnFiscalID = $this->views['EditProveedor']->columnForName('fiscal-id');
        $columnFiscalID->widget->setValuesFromArray(IDFiscal::all());

        /// Load values option to VAT Type select input
        $columnVATType = $this->views['EditProveedor']->columnForName('vat-regime');
        $columnVATType->widget->setValuesFromArray(RegimenIVA::all());

        /// Search for supplier contacts
        $where = [new DataBaseWhere('codproveedor', $code)];
        $contacts = CodeModel::all('contactos', 'idcontacto', 'descripcion', false, $where);

        /// Load values option to default contact
        $columnBilling = $this->views['EditProveedor']->columnForName('contact');
        $columnBilling->widget->setValuesFromCodeModel($contacts);
    }
}
