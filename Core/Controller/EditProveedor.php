<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Lib\InvoiceOperation;
use FacturaScripts\Dinamic\Lib\RegimenIVA;
use FacturaScripts\Dinamic\Lib\SupplierRiskTools;
use FacturaScripts\Core\Lib\TaxExceptions;

/**
 * Controller to edit a single item from the Proveedor model
 *
 * @author       Nazca Networks             <comercial@nazcanetworks.com>
 * @author       Fco. Antonio Moreno Pérez  <famphuelva@gmail.com>
 * @author       Carlos García Gómez        <carlos@facturascripts.com>
 * @collaborator Daniel Fernández Giménez   <contacto@danielfg.es>
 */
class EditProveedor extends ComercialContactController
{
    /**
     * Returns the sum of the customer's total delivery notes.
     *
     * @return string
     */
    public function getDeliveryNotesRisk(): string
    {
        $code = $this->getViewModelValue('EditProveedor', 'codproveedor');
        $total = SupplierRiskTools::getDeliveryNotesRisk($code);
        return Tools::money($total);
    }

    public function getImageUrl(): string
    {
        $mvn = $this->getMainViewName();
        return $this->views[$mvn]->model->gravatar();
    }

    /**
     * Returns the sum of the supplier's total outstanding invoices.
     *
     * @return string
     */
    public function getInvoicesRisk(): string
    {
        $code = $this->getViewModelValue('EditProveedor', 'codproveedor');
        $total = SupplierRiskTools::getInvoicesRisk($code);
        return Tools::money($total);
    }

    public function getModelClassName(): string
    {
        return 'Proveedor';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'purchases';
        $data['title'] = 'supplier';
        $data['icon'] = 'fa-solid fa-users';
        return $data;
    }

    protected function createDocumentView(string $viewName, string $model, string $label): void
    {
        $this->createSupplierListView($viewName, $model, $label)
            ->setSettings('btnPrint', true);

        // agrupamos las acciones en un dropdown
        $this->tab($viewName)->addButtonGroup([
            'name' => 'doc-actions',
            'icon' => 'fa-solid fa-circle-check',
            'label' => 'actions'
        ]);
        $this->addButtonApproveDocument($viewName, 'doc-actions');
        $this->addButtonGroupDocument($viewName, 'doc-actions');
    }

    protected function createInvoiceView(string $viewName): void
    {
        $this->createSupplierListView($viewName, 'FacturaProveedor', 'invoices')
            ->setSettings('btnPrint', true);

        // agrupamos las acciones de facturas en un dropdown
        $this->tab($viewName)->addButtonGroup([
            'name' => 'invoice-actions',
            'icon' => 'fa-solid fa-circle-check',
            'label' => 'actions'
        ]);
        $this->addButtonPayInvoice($viewName, 'invoice-actions');
        $this->addButtonLockInvoice($viewName, 'invoice-actions');
    }

    protected function createProductView(string $viewName = 'ListProductoProveedor'): void
    {
        $this->addListView($viewName, 'ProductoProveedor', 'products', 'fa-solid fa-cubes')
            ->addOrderBy(['actualizado'], 'update-time', 2)
            ->addOrderBy(['referencia'], 'reference')
            ->addOrderBy(['refproveedor'], 'supplier-reference')
            ->addOrderBy(['neto'], 'net')
            ->addOrderBy(['stock'], 'stock')
            ->addSearchFields(['referencia', 'refproveedor'])
            ->disableColumn('supplier')
            ->setSettings('btnNew', false)
            ->setSettings('btnPrint', true);
    }

    /**
     * Create views
     */
    protected function createViews(): void
    {
        parent::createViews();

        $this->createContactsView();
        $this->addEditListView('EditCuentaBancoProveedor', 'CuentaBancoProveedor', 'bank-accounts', 'fa-solid fa-piggy-bank');

        if ($this->user->can('EditSubcuenta')) {
            $this->createSubaccountsView();
        }

        $this->createEmailsView();
        $this->createViewDocFiles();

        if ($this->user->can('EditProducto')) {
            $this->createProductView();
        }
        if ($this->user->can('EditFacturaProveedor')) {
            $this->createInvoiceView('ListFacturaProveedor');
        }
        if ($this->user->can('EditAlbaranProveedor')) {
            $this->createDocumentView('ListAlbaranProveedor', 'AlbaranProveedor', 'delivery-notes');
        }
        if ($this->user->can('EditPedidoProveedor')) {
            $this->createDocumentView('ListPedidoProveedor', 'PedidoProveedor', 'orders');
        }
        if ($this->user->can('EditPresupuestoProveedor')) {
            $this->createDocumentView('ListPresupuestoProveedor', 'PresupuestoProveedor', 'estimations');
        }
        if ($this->user->can('EditReciboProveedor')) {
            $this->createReceiptView('ListReciboProveedor', 'ReciboProveedor');
        }
    }

    protected function editAction(): bool
    {
        $return = parent::editAction();
        if ($return && $this->active === $this->getMainViewName()) {
            $this->checkSubaccountLength($this->getModel()->codsubcuenta);
        }

        return $return;
    }

    protected function insertAction(): bool
    {
        if (false === parent::insertAction()) {
            return false;
        }

        // redirect to return_url if return is defined
        $return_url = $this->request->query('return');
        if (empty($return_url)) {
            return true;
        }

        $model = $this->views[$this->active]->model;
        if (strpos($return_url, '?') === false) {
            $this->redirect($return_url . '?' . $model->primaryColumn() . '=' . $model->id());
        } else {
            $this->redirect($return_url . '&' . $model->primaryColumn() . '=' . $model->id());
        }

        return true;
    }

    /**
     * Load view data
     *
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $mainViewName = $this->getMainViewName();
        $codproveedor = $this->getViewModelValue($mainViewName, 'codproveedor');
        $where = [new DataBaseWhere('codproveedor', $codproveedor)];

        switch ($viewName) {
            case 'EditCuentaBancoProveedor':
                $view->loadData('', $where, ['codcuenta' => 'DESC']);
                break;

            case 'EditDireccionContacto':
                $view->loadData('', $where, ['idcontacto' => 'DESC']);
                break;

            case 'ListFacturaProveedor':
                $view->loadData('', $where);
                $this->addButtonGenerateAccountingInvoices($viewName, $codproveedor, 'invoice-actions');
                break;

            case 'ListAlbaranProveedor':
            case 'ListPedidoProveedor':
            case 'ListPresupuestoProveedor':
            case 'ListProductoProveedor':
            case 'ListReciboProveedor':
                $view->loadData('', $where);
                break;

            case 'ListLineaFacturaProveedor':
                $inSQL = 'SELECT idfactura FROM facturasprov WHERE codproveedor = ' . $this->dataBase->var2str($codproveedor);
                $where = [new DataBaseWhere('idfactura', $inSQL, 'IN')];
                $view->loadData('', $where);
                break;

            case $mainViewName:
                parent::loadData($viewName, $view);
                $this->loadLanguageValues($viewName);
                $this->loadExceptionVat($viewName);
                $this->loadOperationValues($viewName);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

    /**
     * Load the available language values from translator.
     */
    protected function loadLanguageValues(string $viewName): void
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

    protected function loadExceptionVat(string $viewName): void
    {
        $column = $this->views[$viewName]->columnForName('vat-exception');
        if ($column && $column->widget->getType() === 'select') {
            $column->widget->setValuesFromArrayKeys(TaxExceptions::all(), true, true);
        }
    }

    protected function loadOperationValues(string $viewName): void
    {
        $column = $this->views[$viewName]->columnForName('operation');
        if ($column && $column->widget->getType() === 'select') {
            $column->widget->setValuesFromArrayKeys(InvoiceOperation::allForPurchases(), true, true);
        }
    }

    protected function setCustomWidgetValues(string $viewName): void
    {
        // Load values option to VAT Type select input
        $columnVATType = $this->views[$viewName]->columnForName('vat-regime');
        if ($columnVATType && $columnVATType->widget->getType() === 'select') {
            $columnVATType->widget->setValuesFromArrayKeys(RegimenIVA::all(), true);
        }

        // Model exists?
        if (false === $this->views[$viewName]->model->exists()) {
            $this->views[$viewName]->disableColumn('contact');
            return;
        }

        // Search for supplier contacts
        $codproveedor = $this->getViewModelValue($viewName, 'codproveedor');
        $where = [new DataBaseWhere('codproveedor', $codproveedor)];
        $contacts = $this->codeModel->all('contactos', 'idcontacto', 'descripcion', false, $where);

        // Load values option to default contact
        $columnBilling = $this->views[$viewName]->columnForName('contact');
        if ($columnBilling && $columnBilling->widget->getType() === 'select') {
            $columnBilling->widget->setValuesFromCodeModel($contacts);
        }
    }
}
