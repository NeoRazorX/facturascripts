<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Lib\BusinessDocumentGenerator;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Proveedor;

/**
 * Controller for editing models that are related and show
 * a history of purchase or sale documents.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
abstract class ComercialContactController extends EditController
{

    use ListBusinessActionTrait;

    /**
     * Set custom configuration when load main data
     *
     * @param string $viewName
     */
    abstract protected function setCustomWidgetValues($viewName);

    /**
     * Add a Contact List View.
     *
     * @param string $viewName
     */
    protected function createContactsView($viewName = 'EditDireccionContacto')
    {
        $this->addEditListView($viewName, 'Contacto', 'addresses-and-contacts', 'fas fa-address-book');
    }

    /**
     * Add a Customer document List View.
     *
     * @param string $viewName
     * @param string $model
     * @param string $label
     */
    protected function createCustomerListView($viewName, $model, $label)
    {
        $this->createListView($viewName, $model, $label, $this->getCustomerFields());
    }

    /**
     * Add a Email Sent List View.
     *
     * @param string $viewName
     */
    protected function createEmailsView($viewName = 'ListEmailSent')
    {
        $this->addListView($viewName, 'EmailSent', 'emails-sent', 'fas fa-envelope');
        $this->views[$viewName]->addOrderBy(['date'], 'date', 2);
        $this->views[$viewName]->addSearchFields(['addressee', 'body', 'subject']);

        /// disable column
        $this->views[$viewName]->disableColumn('to');

        /// disable buttons
        $this->setSettings($viewName, 'btnNew', false);
    }

    /**
     * Add Product Lines from documents.
     *
     * @param string $viewName
     * @param string $model
     * @param string $label
     */
    protected function createLineView($viewName, $model, $label = 'products')
    {
        $this->addListView($viewName, $model, $label, 'fas fa-cubes');

        /// sort options
        $this->views[$viewName]->addOrderBy(['idlinea'], 'code', 2);
        $this->views[$viewName]->addOrderBy(['cantidad'], 'quantity');
        $this->views[$viewName]->addOrderBy(['pvptotal'], 'amount');

        /// search columns
        $this->views[$viewName]->addSearchFields(['referencia', 'descripcion']);

        /// disable buttons
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'checkBoxes', false);
    }

    /**
     * Add a receipt list view.
     *
     * @param string $viewName
     * @param string $model
     */
    protected function createReceiptView($viewName, $model)
    {
        $this->addListView($viewName, $model, 'receipts', 'fas fa-dollar-sign');

        /// sort options
        $this->views[$viewName]->addOrderBy(['fecha'], 'date', 2);
        $this->views[$viewName]->addOrderBy(['fechapago'], 'payment-date');
        $this->views[$viewName]->addOrderBy(['vencimiento'], 'expiration');
        $this->views[$viewName]->addOrderBy(['importe'], 'amount');

        /// search columns
        $this->views[$viewName]->addSearchFields(['codigofactura', 'observaciones']);

        /// add pay button
        $this->addButtonPayReceipt($viewName);

        /// disable buttons
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'btnDelete', false);

        /// disable columns
        $this->views[$viewName]->disableColumn('customer');
        $this->views[$viewName]->disableColumn('supplier');
    }

    /**
     * Add Subaccount List View.
     *
     * @param string $viewName
     */
    protected function createSubaccountsView($viewName = 'ListSubcuenta')
    {
        $this->addListView($viewName, 'Subcuenta', 'subaccounts', 'fas fa-book');

        /// sort options
        $this->views[$viewName]->addOrderBy(['codsubcuenta'], 'code');
        $this->views[$viewName]->addOrderBy(['codejercicio'], 'exercise', 2);
        $this->views[$viewName]->addOrderBy(['descripcion'], 'description');
        $this->views[$viewName]->addOrderBy(['saldo'], 'balance');

        /// search columns
        $this->views[$viewName]->addSearchFields(['codsubcuenta', 'descripcion']);

        /// disable buttons
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'checkBoxes', false);
    }

    /**
     * Add a Supplier document List View
     *
     * @param string $viewName
     * @param string $model
     * @param string $label
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

        /// sort options
        $this->views[$viewName]->addOrderBy(['codigo'], 'code');
        $this->views[$viewName]->addOrderBy(['fecha', 'hora'], 'date', 2);
        $this->views[$viewName]->addOrderBy(['numero'], 'number');
        $this->views[$viewName]->addOrderBy([$fields['numfield']], $fields['numtitle']);
        $this->views[$viewName]->addOrderBy(['total'], 'amount');

        /// search columns
        $this->views[$viewName]->addSearchFields([$fields['numfield'], 'observaciones']);

        /// disable columns
        $this->views[$viewName]->disableColumn($fields['linkfield'], true);
    }

    /**
     * Run the actions that alter data before reading it.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        $allowUpdate = $this->permissions->allowUpdate;
        $codes = $this->request->request->get('code');
        $model = $this->views[$this->active]->model;

        switch ($action) {
            case 'approve-document':
                return $this->approveDocumentAction($codes, $model, $allowUpdate, $this->dataBase);

            case 'approve-document-same-date':
                BusinessDocumentGenerator::setSameDate(true);
                return $this->approveDocumentAction($codes, $model, $allowUpdate, $this->dataBase);

            case 'group-document':
                return $this->groupDocumentAction($codes, $model);

            case 'lock-invoice':
                return $this->lockInvoiceAction($codes, $model, $allowUpdate, $this->dataBase);

            case 'pay-receipt':
                return $this->payReceiptAction($codes, $model, $allowUpdate, $this->dataBase, $this->user->nick);
        }

        return parent::execPreviousAction($action);
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
     * Load view data
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $mainViewName = $this->getMainViewName();
        switch ($viewName) {
            case $mainViewName:
                parent::loadData($viewName, $view);
                $this->setCustomWidgetValues($viewName);
                break;

            case 'ListSubcuenta':
                $codsubcuenta = $this->getViewModelValue($mainViewName, 'codsubcuenta');
                $where = [new DataBaseWhere('codsubcuenta', $codsubcuenta)];
                $view->loadData('', $where);
                break;

            case 'ListEmailSent':
                $addressee = $this->getViewModelValue($mainViewName, 'email');
                $where = [new DataBaseWhere('addressee', $addressee)];
                $view->loadData('', $where);
                break;
        }
    }

    /**
     *
     * @param Cliente|Proveedor $subject
     */
    protected function updateContact($subject)
    {
        $contact = $subject->getDefaultAddress();
        $contact->email = $subject->email;
        $contact->fax = $subject->fax;
        $contact->telefono1 = $subject->telefono1;
        $contact->telefono2 = $subject->telefono2;
        $contact->save();
    }
}
