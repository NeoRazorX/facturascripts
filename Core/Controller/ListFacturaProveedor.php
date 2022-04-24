<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\DataSrc\Divisas;
use FacturaScripts\Core\DataSrc\FormasPago;
use FacturaScripts\Dinamic\Lib\ExtendedController\ListBusinessDocument;

/**
 * Controller to list the items in the FacturaProveedor model
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Artex Trading sa             <jcuello@artextrading.com>
 * @author Raul Jimenez                 <raul.jimenez@nazcanetworks.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class ListFacturaProveedor extends ListBusinessDocument
{
    /**
    * Add a modal button for renumber entries
    *
    * @param string $viewName
    */
    protected function addRenumberInvoicesButton(string $viewName)
    {
        $this->addButton($viewName, [
            'action' => 'renumber-invoices',
            'icon' => 'fas fa-sort-numeric-down',
            'label' => 'renumber-invoices',
            'type' => 'modal'
            ]);
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
        $data['title'] = 'invoices';
        $data['icon'] = 'fas fa-file-invoice-dollar';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewPurchases('ListFacturaProveedor', 'FacturaProveedor', 'invoices');
        if ($this->permissions->onlyOwnerData === false) {
            $this->createViewLines('ListLineaFacturaProveedor', 'LineaFacturaProveedor');
        }
        $this->createViewReceipts();
    }

    /**
     * @param string $viewName
     * @param string $modelName
     * @param string $label
     */
    protected function createViewPurchases(string $viewName, string $modelName, string $label)
    {
        parent::createViewPurchases($viewName, $modelName, $label);
        $this->addFilterCheckbox('ListFacturaProveedor', 'pagada', 'unpaid', 'pagada', '=', false);
        $this->addFilterCheckbox('ListFacturaProveedor', 'idasiento', 'invoice-without-acc-entry', 'idasiento', 'IS', null);
        $this->addButtonLockInvoice('ListFacturaProveedor');
        $this->addRenumberInvoicesButton($viewName);
    }

    /**
     * @param string $viewName
     */
    protected function createViewReceipts(string $viewName = 'ListReciboProveedor')
    {
        $this->addView($viewName, 'ReciboProveedor', 'receipts', 'fas fa-dollar-sign');
        $this->addOrderBy($viewName, ['fecha', 'idrecibo'], 'date', 2);
        $this->addOrderBy($viewName, ['fechapago'], 'payment-date');
        $this->addOrderBy($viewName, ['vencimiento'], 'expiration');
        $this->addOrderBy($viewName, ['importe'], 'amount');
        $this->addSearchFields($viewName, ['codigofactura', 'observaciones']);

        // filters
        $this->addFilterPeriod($viewName, 'date', 'period', 'fecha');
        $this->addFilterAutocomplete($viewName, 'codproveedor', 'supplier', 'codproveedor', 'Proveedor');
        $this->addFilterNumber($viewName, 'min-total', 'amount', 'importe', '>=');
        $this->addFilterNumber($viewName, 'max-total', 'amount', 'importe', '<=');

        $currencies = Divisas::codeModel();
        if (count($currencies) > 2) {
            $this->addFilterSelect($viewName, 'coddivisa', 'currency', 'coddivisa', $currencies);
        }

        $paymethods = FormasPago::codeModel();
        if (count($paymethods) > 2) {
            $this->addFilterSelect($viewName, 'codpago', 'payment-method', 'codpago', $paymethods);
        }

        $this->addFilterCheckbox($viewName, 'pagado', 'unpaid', '', '!=');

        // buttons
        $this->addButtonPayReceipt($viewName);

        // settings
        $this->setSettings($viewName, 'btnNew', false);
    }

    /**
    *
    * @return bool
    */
    protected function renumberInvoicesAction()
    {
        if (false === $this->permissions->allowUpdate) 
        {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return true;
        }
        $codejercicio = $this->request->request->get('exercise');
        if ($this->views['ListFacturaProveedor']->model->renumberInvoices($codejercicio)) 
        {
            $this->toolBox()->i18nLog()->notice('renumber-invoices-ok');
        }
        return true;
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
        switch ($action) 
        {
            case 'renumber-invoices':
                return $this->renumberInvoicesAction();
        }
        return parent::execPreviousAction($action);
    }
}
