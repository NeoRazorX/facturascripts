<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\BusinessDocumentGenerator;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\Proveedor;

/**
 * Controller for editing models that are related and show
 * a history of purchase or sale documents.
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
abstract class ComercialContactController extends EditController
{
    use ListBusinessActionTrait;
    use DocFilesTrait;

    /**
     * Set custom configuration when load main data
     *
     * @param string $viewName
     */
    abstract protected function setCustomWidgetValues(string $viewName);

    /**
     * Check that the subaccount length is correct.
     *
     * @param ?string $code
     */
    protected function checkSubaccountLength(?string $code): void
    {
        if (empty($code)) {
            return;
        }

        $exercise = new Ejercicio();
        foreach ($exercise->all([], [], 0, 0) as $exe) {
            if ($exe->isOpened() && strlen($code) != $exe->longsubcuenta) {
                Tools::log()->warning('account-length-error', ['%code%' => $code]);
            }
        }
    }

    protected function checkViesAction(): bool
    {
        $model = $this->getModel();
        if (false === $model->loadFromCode($this->request->get('code'))) {
            return true;
        }

        if ($model->checkVies()) {
            Tools::log()->notice('vies-check-success', ['%vat-number%' => $model->cifnif]);
        }

        return true;
    }

    /**
     * Add a Contact List View.
     *
     * @param string $viewName
     */
    protected function createContactsView(string $viewName = 'EditDireccionContacto'): void
    {
        $this->addEditListView($viewName, 'Contacto', 'addresses-and-contacts', 'fa-solid fa-address-book');
    }

    /**
     * Add a Customer document List View.
     *
     * @param string $viewName
     * @param string $model
     * @param string $label
     */
    protected function createCustomerListView(string $viewName, string $model, string $label): void
    {
        $this->createListView($viewName, $model, $label, $this->getCustomerFields());
    }

    /**
     * Add an Email Sent List View.
     *
     * @param string $viewName
     */
    protected function createEmailsView(string $viewName = 'ListEmailSent'): void
    {
        $this->addListView($viewName, 'EmailSent', 'emails-sent', 'fa-solid fa-envelope')
            ->addOrderBy(['date'], 'date', 2)
            ->addSearchFields(['addressee', 'body', 'subject']);

        // desactivamos la columna de destinatario
        $this->views[$viewName]->disableColumn('to');

        // desactivamos el botón nuevo
        $this->setSettings($viewName, 'btnNew', false);

        // filtros
        $this->listView($viewName)->addFilterPeriod('period', 'date', 'date', true);
    }

    /**
     * Add Product Lines from documents.
     *
     * @param string $viewName
     * @param string $model
     * @param string $label
     */
    protected function createLineView(string $viewName, string $model, string $label = 'products'): void
    {
        $this->addListView($viewName, $model, $label, 'fa-solid fa-cubes')
            ->addOrderBy(['idlinea'], 'code', 2)
            ->addOrderBy(['cantidad'], 'quantity')
            ->addOrderBy(['pvptotal'], 'amount')
            ->addSearchFields(['referencia', 'descripcion']);

        // botones
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'btnPrint', true);
    }

    /**
     * Add a document List View
     *
     * @param string $viewName
     * @param string $model
     * @param string $label
     * @param array $fields
     */
    private function createListView(string $viewName, string $model, string $label, array $fields): void
    {
        $this->addListView($viewName, $model, $label, 'fa-solid fa-copy')
            ->addOrderBy(['codigo'], 'code')
            ->addOrderBy(['fecha', 'hora'], 'date', 2)
            ->addOrderBy(['numero'], 'number')
            ->addOrderBy([$fields['numfield']], $fields['numtitle'])
            ->addOrderBy(['total'], 'amount')
            ->addSearchFields(['codigo', 'observaciones', $fields['numfield']]);

        // disable columns
        $this->listView($viewName)->disableColumn($fields['linkfield'], true);

        // filters
        $this->listView($viewName)->addFilterPeriod('period', 'date', 'fecha');
    }

    /**
     * Add a receipt list view.
     *
     * @param string $viewName
     * @param string $model
     */
    protected function createReceiptView(string $viewName, string $model): void
    {
        $this->addListView($viewName, $model, 'receipts', 'fa-solid fa-dollar-sign')
            ->addOrderBy(['fecha'], 'date')
            ->addOrderBy(['fechapago'], 'payment-date')
            ->addOrderBy(['vencimiento'], 'expiration', 2)
            ->addOrderBy(['importe'], 'amount')
            ->addSearchFields(['codigofactura', 'observaciones']);

        // filtros
        $this->listView($viewName)->addFilterPeriod('period-f', 'fecha', 'fecha');
        $this->listView($viewName)->addFilterPeriod('period-v', 'expiration', 'vencimiento');

        // botones
        $this->addButtonPayReceipt($viewName);
        $this->setSettings($viewName, 'btnPrint', true);
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'btnDelete', false);

        // desactivar columnas
        $this->views[$viewName]->disableColumn('customer');
        $this->views[$viewName]->disableColumn('supplier');
    }

    /**
     * Add Subaccount List View.
     *
     * @param string $viewName
     */
    protected function createSubaccountsView(string $viewName = 'ListSubcuenta'): void
    {
        $this->addListView($viewName, 'Subcuenta', 'subaccounts', 'fa-solid fa-book')
            ->addOrderBy(['codsubcuenta'], 'code')
            ->addOrderBy(['codejercicio'], 'exercise', 2)
            ->addOrderBy(['descripcion'], 'description')
            ->addOrderBy(['saldo'], 'balance')
            ->addSearchFields(['codsubcuenta', 'descripcion']);

        // disable buttons
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
    protected function createSupplierListView(string $viewName, string $model, string $label): void
    {
        $this->createListView($viewName, $model, $label, $this->getSupplierFields());
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
        $codes = $this->request->request->getArray('codes');
        $model = $this->views[$this->active]->model;

        switch ($action) {
            case 'add-file':
                return $this->addFileAction();

            case 'approve-document':
                return $this->approveDocumentAction($codes, $model, $allowUpdate, $this->dataBase);

            case 'approve-document-same-date':
                BusinessDocumentGenerator::setSameDate(true);
                return $this->approveDocumentAction($codes, $model, $allowUpdate, $this->dataBase);

            case 'check-vies':
                return $this->checkViesAction();

            case 'delete-file':
                return $this->deleteFileAction();

            case 'edit-file':
                return $this->editFileAction();

            case 'generate-accounting-entries':
                return $this->generateAccountingEntriesAction($model, $allowUpdate, $this->dataBase);

            case 'group-document':
                return $this->groupDocumentAction($codes, $model);

            case 'lock-invoice':
                return $this->lockInvoiceAction($codes, $model, $allowUpdate, $this->dataBase);

            case 'pay-receipt':
                return $this->payReceiptAction($codes, $model, $allowUpdate, $this->dataBase, $this->user->nick);

            case 'unlink-file':
                return $this->unlinkFileAction();
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
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $mvn = $this->getMainViewName();

        switch ($viewName) {
            case $mvn:
                parent::loadData($viewName, $view);
                $this->setCustomWidgetValues($viewName);
                if ($view->model->exists() && $view->model->cifnif) {
                    $this->addButton($viewName, [
                        'action' => 'check-vies',
                        'color' => 'info',
                        'icon' => 'fa-solid fa-check-double',
                        'label' => 'check-vies'
                    ]);
                }
                break;

            case 'docfiles':
                $this->loadDataDocFiles($view, $this->getModelClassName(), $this->getModel()->primaryColumnValue());
                break;

            case 'ListSubcuenta':
                $codsubcuenta = $this->getViewModelValue($mvn, 'codsubcuenta');
                $where = [new DataBaseWhere('codsubcuenta', $codsubcuenta)];
                $view->loadData('', $where);
                $this->setSettings($viewName, 'active', $view->count > 0);
                break;

            case 'ListEmailSent':
                $email = $this->getViewModelValue($mvn, 'email');
                if (empty($email)) {
                    $this->setSettings($viewName, 'active', false);
                    break;
                }

                $where = [new DataBaseWhere('addressee', $email)];
                $view->loadData('', $where);

                // añadimos un botón para enviar un nuevo email
                $this->addButton($viewName, [
                    'action' => 'SendMail?email=' . $email,
                    'color' => 'success',
                    'icon' => 'fa-solid fa-envelope',
                    'label' => 'send',
                    'type' => 'link'
                ]);
                break;
        }
    }

    /**
     * @param Cliente|Proveedor $subject
     */
    protected function updateContact($subject): void
    {
        $contact = $subject->getDefaultAddress();
        $contact->email = $subject->email;
        $contact->fax = $subject->fax;
        $contact->telefono1 = $subject->telefono1;
        $contact->telefono2 = $subject->telefono2;

        // Sincronice fiscal data for pass validation
        $contact->cifnif = $subject->cifnif;
        $contact->tipoidfiscal = $subject->tipoidfiscal;

        $contact->save();
    }
}
