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
namespace FacturaScripts\Core\Lib\ExtendedController;

/**
 * Controller for editing models that are related and show
 * a history of purchase or sale documents.
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Artex Trading sa             <jcuello@artextrading.com>
 */
abstract class EditDocHistoryController extends EditController
{

    /**
     * Add a Contact List View
     *
     * @param string $viewName
     */
    protected function createContactsView($viewName = 'ListContacto')
    {
        $this->addListView($viewName, 'Contacto', 'addresses-and-contacts', 'fas fa-address-book');
        $view = $this->views[$viewName];

        /// sort options
        $view->addOrderBy(['fechaalta'], 'date');
        $view->addOrderBy(['descripcion'], 'descripcion', 2);

        /// search columns
        $view->searchFields[] = 'apellidos';
        $view->searchFields[] = 'descripcion';
        $view->searchFields[] = 'direccion';
        $view->searchFields[] = 'email';
        $view->searchFields[] = 'nombre';

        /// Disable buttons
        $this->setSettings($viewName, 'btnDelete', false);
    }


    /**
     * Add a Customer document List View
     *
     * @param type $viewName
     * @param type $model
     * @param type $label
     */
    protected function createCustomerListView($viewName, $model, $label) {
        $this->createListView($viewName, $model, $label, $this->getCustomerFields());
    }

    /**
     * Add Product Lines from documents
     *
     * @param string $viewName
     * @param string $model
     * @param string $label
     */
    protected function createLineView($viewName, $model, $label = 'products')
    {
        $this->addListView($viewName, $model, $label, 'fas fa-cubes');
        $view = $this->views[$viewName];

        /// sort options
        $view->addOrderBy(['idlinea'], 'code', 2);
        $view->addOrderBy(['cantidad'], 'quantity');
        $view->addOrderBy(['pvptotal'], 'amount');

        /// search columns
        $view->searchFields[] = 'referencia';
        $view->searchFields[] = 'descripcion';

        /// Disable buttons
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
    }

    /**
     *
     * @param string $viewName
     */
    protected function createReceiptView($viewName, $model)
    {
        $this->addListView($viewName, $model, 'receipts', 'fas fa-dollar-sign');
        $view = $this->views[$viewName];

        $view->addOrderBy(['fecha'], 'date', 2);
        $view->addOrderBy(['fechapago'], 'payment-date');
        $view->addOrderBy(['vencimiento'], 'expiration');
        $view->addOrderBy(['importe'], 'amount');
        $view->searchFields[] = 'observaciones';

        /// settings
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'btnDelete', false);
    }

    /**
     * Add Subaccount List View
     *
     * @param string $viewName
     */
    protected function createSubaccountsView($viewName = 'ListSubcuenta')
    {
        $this->addListView($viewName, 'Subcuenta', 'subaccounts', 'fas fa-book');
        $view = $this->views[$viewName];

        /// sort options
        $view->addOrderBy(['codigo'], 'code');
        $view->addOrderBy(['codejercicio'], 'exercise', 2);
        $view->addOrderBy(['descripcion'], 'descripcion');
        $view->addOrderBy(['saldo'], 'balance');

        /// search columns
        $view->searchFields[] = 'codigo';
        $view->searchFields[] = 'description';

        /// Disable buttons
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
    }

    /**
     * Add a Supplier document List View
     *
     * @param type $viewName
     * @param type $model
     * @param type $label
     */
    protected function createSupplierListView($viewName, $model, $label)
    {
        $this->createListView($viewName, $model, $label, $this->getSupplierFields());
    }

    /**
     * Add a document List View
     *
     * @param string $viewName
     * @param string $model
     * @param string $label
     * @param array  $fields
     */
    private function createListView($viewName, $model, $label, $fields)
    {
        $this->addListView($viewName, $model, $label, 'fas fa-copy');
        $view = $this->views[$viewName];

        /// sort options
        $view->addOrderBy(['codigo'], 'code');
        $view->addOrderBy(['fecha', 'hora'], 'date', 2);
        $view->addOrderBy(['numero'], 'number');
        $view->addOrderBy([$fields['numfield']], $fields['numtitle']);
        $view->addOrderBy(['total'], 'amount');

        /// search columns
        $view->searchFields[] = $fields['numfield'];
        $view->searchFields[] = 'observaciones';

        /// Disable columns
        $view->disableColumn($fields['linkfield'], true);
    }

    /**
     * Customer special fields
     *
     * @return array
     */
    private function getCustomerFields(): array
    {
        return [
            'linkfield' => 'customer',
            'numfield' => 'numero2',
            'numtitle' => 'number2'
        ];
    }

    /**
     * Supplier special fields
     *
     * @return array
     */
    private function getSupplierFields(): array
    {
        return [
            'linkfield' => 'supplier',
            'numfield' => 'numproveedor',
            'numtitle' => 'numsupplier'
        ];
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
        $columnBilling->widget->setValuesFromCodeModel($contacts);

        /// Load values option to default shipping address from client contacts list
        $columnShipping = $this->views[$viewName]->columnForName('shipping-address');
        $columnShipping->widget->setValuesFromCodeModel($contacts);
    }
}