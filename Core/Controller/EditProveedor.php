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
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Dinamic\Lib\RegimenIVA;
use FacturaScripts\Dinamic\Model\TotalModel;

/**
 * Controller to edit a single item from the Proveedor model
 *
 * @author Nazca Networks               <comercial@nazcanetworks.com>
 * @author Fco. Antonio Moreno Pérez    <famphuelva@gmail.com>
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 */
class EditProveedor extends EditController
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
     * Returns the sum of the supplier's total outstanding invoices.
     *
     * @return string
     */
    public function calcSupplierInvoicePending()
    {
        $where = [
            new DataBaseWhere('codproveedor', $this->getViewModelValue('EditProveedor', 'codproveedor')),
            new DataBaseWhere('pagada', false)
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
        $data = parent::getPageData();
        $data['menu'] = 'purchases';
        $data['title'] = 'supplier';
        $data['icon'] = 'fas fa-users';
        return $data;
    }

    /**
     * 
     * @param string $viewName
     * @param string $model
     * @param string $label
     * @param string $icon
     */
    protected function createContactsView($viewName, $model, $label, $icon)
    {
        $this->addListView($viewName, $model, $label, $icon);

        /// sort options
        $this->views[$viewName]->addOrderBy(['fechaalta'], 'date');
        $this->views[$viewName]->addOrderBy(['descripcion'], 'descripcion', 2);

        /// search columns
        $this->views[$viewName]->searchFields[] = 'apellidos';
        $this->views[$viewName]->searchFields[] = 'descripcion';
        $this->views[$viewName]->searchFields[] = 'direccion';
        $this->views[$viewName]->searchFields[] = 'email';
        $this->views[$viewName]->searchFields[] = 'nombre';

        /// Disable buttons
        $this->setSettings($viewName, 'btnDelete', false);
    }

    /**
     * 
     * @param string $viewName
     * @param string $model
     * @param string $label
     */
    protected function createLineView($viewName, $model, $label)
    {
        $this->addListView($viewName, $model, $label, 'fas fa-cubes');

        /// sort options
        $this->views[$viewName]->addOrderBy(['idlinea'], 'code', 2);
        $this->views[$viewName]->addOrderBy(['cantidad'], 'quantity');
        $this->views[$viewName]->addOrderBy(['pvptotal'], 'amount');

        /// search columns
        $this->views[$viewName]->searchFields[] = 'referencia';
        $this->views[$viewName]->searchFields[] = 'descripcion';

        /// Disable buttons
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
    }

    /**
     * 
     * @param string $viewName
     * @param string $model
     * @param string $label
     */
    protected function createListView($viewName, $model, $label)
    {
        $this->addListView($viewName, $model, $label, 'fas fa-copy');

        /// sort options
        $this->views[$viewName]->addOrderBy(['codigo'], 'code');
        $this->views[$viewName]->addOrderBy(['fecha', 'hora'], 'date', 2);
        $this->views[$viewName]->addOrderBy(['numero'], 'number');
        $this->views[$viewName]->addOrderBy(['numproveedor'], 'numsupplier');
        $this->views[$viewName]->addOrderBy(['total'], 'amount');

        /// search columns
        $this->views[$viewName]->searchFields[] = 'numproveedor';
        $this->views[$viewName]->searchFields[] = 'observaciones';

        /// Disable columns
        $this->views[$viewName]->disableColumn('customer', true);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createReceiptView($viewName = 'ListReciboProveedor')
    {
        $this->addListView($viewName, 'ReciboProveedor', 'receipts', 'fas fa-piggy-bank');
        $this->views[$viewName]->addOrderBy(['fecha'], 'date', 2);
        $this->views[$viewName]->searchFields[] = 'observaciones';

        /// settings
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'btnDelete', false);
    }

    /**
     * 
     * @param string $viewName
     * @param string $model
     * @param string $label
     * @param string $icon
     */
    protected function createSubaccountsView($viewName, $model, $label, $icon)
    {
        $this->addListView($viewName, $model, $label, $icon);

        /// sort options
        $this->views[$viewName]->addOrderBy(['codigo'], 'code');
        $this->views[$viewName]->addOrderBy(['codejercicio'], 'exercise', 2);
        $this->views[$viewName]->addOrderBy(['descripcion'], 'descripcion');
        $this->views[$viewName]->addOrderBy(['saldo'], 'balance');

        /// search columns
        $this->views[$viewName]->searchFields[] = 'codigo';
        $this->views[$viewName]->searchFields[] = 'description';

        /// Disable buttons
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
    }

    /**
     * Create views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->createContactsView('ListContacto', 'Contacto', 'addresses-and-contacts', 'fas fa-address-book');
        $this->addEditListView('EditCuentaBancoProveedor', 'CuentaBancoProveedor', 'bank-accounts', 'fas fa-piggy-bank');
        $this->createSubaccountsView('ListSubcuenta', 'Subcuenta', 'subaccounts', 'fas fa-book');

        $this->createListView('ListFacturaProveedor', 'FacturaProveedor', 'invoices');
        $this->createLineView('ListLineaFacturaProveedor', 'LineaFacturaProveedor', 'products');
        $this->createListView('ListAlbaranProveedor', 'AlbaranProveedor', 'delivery-notes');
        $this->createListView('ListPedidoProveedor', 'PedidoProveedor', 'orders');
        $this->createListView('ListPresupuestoProveedor', 'PresupuestoProveedor', 'estimations');
        $this->createReceiptView();
    }

    /**
     * Load view data
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $codproveedor = $this->getViewModelValue('EditProveedor', 'codproveedor');
        switch ($viewName) {
            case 'EditProveedor':
                parent::loadData($viewName, $view);
                $this->setCustomWidgetValues($viewName);
                break;

            case 'EditCuentaBancoProveedor':
            case 'ListAlbaranProveedor':
            case 'ListContacto':
            case 'ListFacturaProveedor':
            case 'ListPedidoProveedor':
            case 'ListPresupuestoProveedor':
            case 'ListReciboProveedor':
                $where = [new DataBaseWhere('codproveedor', $codproveedor)];
                $view->loadData('', $where);
                break;

            case 'ListLineaFacturaProveedor':
                $inSQL = 'SELECT idfactura FROM facturasprov WHERE codproveedor = ' . $this->dataBase->var2str($codproveedor);
                $where = [new DataBaseWhere('idfactura', $inSQL, 'IN')];
                $view->loadData('', $where);
                break;

            case 'ListSubcuenta':
                $codsubcuenta = $this->getViewModelValue('EditProveedor', 'codsubcuenta');
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
        $columnVATType->widget->setValuesFromArrayKeys(RegimenIVA::all());

        /// Model exists?
        if (!$this->views[$viewName]->model->exists()) {
            $this->views[$viewName]->disableColumn('contact');
            return;
        }

        /// Search for supplier contacts
        $codproveedor = $this->getViewModelValue($viewName, 'codproveedor');
        $where = [new DataBaseWhere('codproveedor', $codproveedor)];
        $contacts = $this->codeModel->all('contactos', 'idcontacto', 'descripcion', false, $where);

        /// Load values option to default contact
        $columnBilling = $this->views[$viewName]->columnForName('contact');
        $columnBilling->widget->setValuesFromCodeModel($contacts);
    }
}
