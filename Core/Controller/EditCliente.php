<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Lib\CustomerRiskTools;
use FacturaScripts\Dinamic\Lib\InvoiceOperation;
use FacturaScripts\Dinamic\Lib\TaxException;
use FacturaScripts\Dinamic\Lib\TaxRegime;
use FacturaScripts\Dinamic\Model\AlbaranCliente;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\PedidoCliente;
use FacturaScripts\Dinamic\Model\PresupuestoCliente;
/**
 * Controller to edit a single item from the Cliente model
 *
 * @author       Carlos García Gómez           <carlos@facturascripts.com>
 * @author       Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author       Fco. Antonio Moreno Pérez     <famphuelva@gmail.com>
 * @collaborator Daniel Fernández Giménez      <hola@danielfg.es>
 */
class EditCliente extends ComercialContactController
{
    /**
     * Returns the customer's risk on pending delivery notes.
     *
     * @return string
     */
    public function getDeliveryNotesRisk(): string
    {
        $codcliente = $this->getViewModelValue('EditCliente', 'codcliente');
        $total = empty($codcliente) ? 0 : CustomerRiskTools::getDeliveryNotesRisk($codcliente);
        return Tools::money($total);
    }

    public function getImageUrl(): string
    {
        $mvn = $this->getMainViewName();
        return $this->views[$mvn]->model->gravatar();
    }

    /**
     * Returns the customer's risk on unpaid invoices.
     *
     * @return string
     */
    public function getInvoicesRisk(): string
    {
        $codcliente = $this->getViewModelValue('EditCliente', 'codcliente');
        $total = empty($codcliente) ? 0 : CustomerRiskTools::getInvoicesRisk($codcliente);
        return Tools::money($total);
    }

    public function getModelClassName(): string
    {
        return 'Cliente';
    }

    /**
     * Returns the customer's risk on pending orders.
     *
     * @return string
     */
    public function getOrdersRisk(): string
    {
        $codcliente = $this->getViewModelValue('EditCliente', 'codcliente');
        $total = empty($codcliente) ? 0 : CustomerRiskTools::getOrdersRisk($codcliente);
        return Tools::money($total);
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'customer';
        $data['icon'] = 'fa-solid fa-users';
        return $data;
    }

    protected function createDocumentView(string $viewName, string $model, string $label): void
    {
        $this->createCustomerListView($viewName, $model, $label);

        // botones
        $this->setSettings($viewName, 'btnPrint', true);
        $this->addButtonGroupDocument($viewName);
        $this->addButtonApproveDocument($viewName);
    }

    protected function createInvoiceView(string $viewName): void
    {
        $this->createCustomerListView($viewName, 'FacturaCliente', 'invoices');

        // botones
        $this->setSettings($viewName, 'btnPrint', true);
        $this->addButtonLockInvoice($viewName);
    }

    /**
     * Create views
     */
    protected function createViews()
    {
        parent::createViews();

        $this->createContactsView();
        $this->addEditListView('EditCuentaBancoCliente', 'CuentaBancoCliente', 'customer-banking-accounts', 'fa-solid fa-piggy-bank');

        if ($this->user->can('EditSubcuenta')) {
            $this->createSubaccountsView();
        }

        $this->createEmailsView();
        $this->createViewDocFiles();

        if ($this->user->can('EditFacturaCliente')) {
            $this->createInvoiceView('ListFacturaCliente');
            $this->createLineView('ListLineaFacturaCliente', 'LineaFacturaCliente');
        }
        if ($this->user->can('EditAlbaranCliente')) {
            $this->createDocumentView('ListAlbaranCliente', 'AlbaranCliente', 'delivery-notes');
        }
        if ($this->user->can('EditPedidoCliente')) {
            $this->createDocumentView('ListPedidoCliente', 'PedidoCliente', 'orders');
        }
        if ($this->user->can('EditPresupuestoCliente')) {
            $this->createDocumentView('ListPresupuestoCliente', 'PresupuestoCliente', 'estimations');
        }
        if ($this->user->can('EditReciboCliente')) {
            $this->createReceiptView('ListReciboCliente', 'ReciboCliente');
        }
    }

    /**
     * Crea el panel de 'Direcciones y contactos' con el botón de Aplicar a documentos
     */
    protected function createContactsView(string $viewName = 'EditDireccionContacto'): void
    {
        parent::createContactsView($viewName);

        $this->addButton($viewName, [
            'action' => 'update-docs-address',
            'color' => 'warning',
            'confirm' => true,
            'icon' => 'fa-solid fa-pencil',
            'label' => 'update-docs-address'
        ]);
    }

    protected function editAction(): bool
    {
        $return = parent::editAction();
        if ($return && $this->active === $this->getMainViewName()) {
            $this->checkSubaccountLength($this->getModel()->codsubcuenta);

            // update contact email and phones when customer email or phones are updated
            $this->updateContact($this->views[$this->active]->model);
        }

        return $return;
    }

    protected function execPreviousAction($action)
    {
        if ($action == 'update-docs-address') {
            return $this->updateDocsAddressAction();
        }

        return parent::execPreviousAction($action);
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
     * Load view data procedure
     *
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $mainViewName = $this->getMainViewName();
        $codcliente = $this->getViewModelValue($mainViewName, 'codcliente');
        $where = [new DataBaseWhere('codcliente', $codcliente)];

        switch ($viewName) {
            case 'EditCuentaBancoCliente':
                $view->loadData('', $where, ['codcuenta' => 'DESC']);
                break;

            case 'EditDireccionContacto':
                $view->loadData('', $where, ['idcontacto' => 'DESC']);
                break;

            case 'ListFacturaCliente':
                $view->loadData('', $where);
                $this->addButtonGenerateAccountingInvoices($viewName, $codcliente);
                break;

            case 'ListAlbaranCliente':
            case 'ListPedidoCliente':
            case 'ListPresupuestoCliente':
            case 'ListReciboCliente':
                $view->loadData('', $where);
                break;

            case 'ListLineaFacturaCliente':
                $inSQL = 'SELECT idfactura FROM facturascli WHERE codcliente = ' . $this->dataBase->var2str($codcliente);
                $where = [new DataBaseWhere('idfactura', $inSQL, 'IN')];
                $view->loadData('', $where);
                break;

            case $mainViewName:
                parent::loadData($viewName, $view);
                $this->loadLanguageValues($viewName);
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

    protected function setCustomWidgetValues(string $viewName): void
    {
        $columnVATRegime = $this->views[$viewName]->columnForName('vat-regime');
        if ($columnVATRegime && $columnVATRegime->widget->getType() === 'select') {
            $columnVATRegime->widget->setValuesFromArrayKeys(TaxRegime::all(), true, true);
        }

        $columnInvoiceOperation = $this->views[$viewName]->columnForName('operation');
        if ($columnInvoiceOperation && $columnInvoiceOperation->widget->getType() === 'select') {
            $columnInvoiceOperation->widget->setValuesFromArrayKeys(InvoiceOperation::all(), true, true);
        }

        $columnVATException = $this->views[$viewName]->columnForName('vat-exception');
        if ($columnVATException && $columnVATException->widget->getType() === 'select') {
            $columnVATException->widget->setValuesFromArrayKeys(TaxException::all(), true, true);
        }

        // Model exists?
        if (false === $this->views[$viewName]->model->exists()) {
            $this->views[$viewName]->disableColumn('billing-address');
            $this->views[$viewName]->disableColumn('shipping-address');
            return;
        }

        // Search for client contacts
        $codcliente = $this->getViewModelValue($viewName, 'codcliente');
        $where = [new DataBaseWhere('codcliente', $codcliente)];
        $contacts = $this->codeModel->all('contactos', 'idcontacto', 'descripcion', false, $where);

        // Load values option to default billing address from client contacts list
        $columnBilling = $this->views[$viewName]->columnForName('billing-address');
        if ($columnBilling && $columnBilling->widget->getType() === 'select') {
            $columnBilling->widget->setValuesFromCodeModel($contacts);
        }

        // Load values option to default shipping address from client contacts list
        $columnShipping = $this->views[$viewName]->columnForName('shipping-address');
        if ($columnShipping && $columnShipping->widget->getType() === 'select') {
            $contacts2 = $this->codeModel->all('contactos', 'idcontacto', 'descripcion', true, $where);
            $columnShipping->widget->setValuesFromCodeModel($contacts2);
        }
    }

    /**
     * Actualiza la dirección de envío del cliente a todos los documentos de venta pendientes con la misma dirección:
     */
    protected function updateDocsAddressAction(): bool
    {
        if (false === $this->validateFormToken()) {
            return false;
        } elseif (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return false;
        }

        // recoger contacto
        $contacto = new Contacto();
        $idcontacto = $this->request->input('idcontacto');
        if (false === $contacto->load($idcontacto)) {
            Tools::log()->error('address-not-found');
            return false;
        }

        // recoger cliente
        $cliente = new Cliente();
        $codcliente = $this->request->input('codcliente');
        if (false === $cliente->load($codcliente)) {
            Tools::log()->error('customer-not-found');
            return false;
        }

        // variable para registrar los errores
        $failCounter = 0;
        $successCounter = 0;

        // filtros
        $where = [
            new DataBaseWhere('codcliente', $codcliente),
            new DataBaseWhere('idcontactofact', $idcontacto),
            new DataBaseWhere('editable', true)
        ];
        // para cada documento de venta
        foreach (['AlbaranCliente', 'FacturaCliente', 'PedidoCliente', 'PresupuestoCliente'] as $modelName) {

            // obtener el documento de venta correspondiente
            $salesDocuments = [];
            switch ($modelName) {
                case 'AlbaranCliente':
                    $salesDocuments = AlbaranCliente::all($where);
                    break;

                case 'FacturaCliente':
                    $salesDocuments = FacturaCliente::all($where);
                    break;

                case 'PedidoCliente':
                    $salesDocuments = PedidoCliente::all($where);
                    break;

                case 'PresupuestoCliente':
                    $salesDocuments = PresupuestoCliente::all($where);
                    break;
            }

            // para cada documento de venta aplicar y guardar
            foreach ($salesDocuments as $salesDoc) {
                $salesDoc->direccion = $contacto->direccion;
                $salesDoc->apartado = $contacto->apartado;
                $salesDoc->codpostal = $contacto->codpostal;
                $salesDoc->ciudad = $contacto->ciudad;
                $salesDoc->provincia = $contacto->provincia;
                $salesDoc->codpais = $contacto->codpais;

                if (false === $salesDoc->save()) {
                    $failCounter += 1;
                } else {
                    $successCounter += 1;
                }
            }
        }

        // notificar del resultado
        if ($failCounter === 0) {
            Tools::log()->notice('address-applied-to-documents-successfully', [
                '%successes%' => $successCounter
            ]);
        } else {
            Tools::log()->warning('address-applied-to-documents-with-errors', [
                '%failures%' => $failCounter,
                '%successes%' => $successCounter
            ]);
        }

        return true;
    }
}
