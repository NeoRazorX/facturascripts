<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Dinamic\Lib\ExtendedController\ListBusinessDocument;

/**
 * Controller to list the items in the FacturaCliente model
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Artex Trading sa             <jcuello@artextrading.com>
 * @author Raul Jimenez                 <raul.jimenez@nazcanetworks.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class ListFacturaCliente extends ListBusinessDocument
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'invoices';
        $data['icon'] = 'fas fa-copy';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewSales('ListFacturaCliente', 'FacturaCliente', 'invoices');
        $this->addFilterCheckbox('ListFacturaCliente', 'pagada', 'unpaid', '', '=', false);
        $this->addButtonLockInvoice('ListFacturaCliente');

        $this->createViewLines('ListLineaFacturaCliente', 'LineaFacturaCliente');
        $this->createViewReceipts();
    }

    /**
     *
     * @param string $viewName
     */
    protected function createViewReceipts($viewName = 'ListReciboCliente')
    {
        $this->addView($viewName, 'ReciboCliente', 'receipts', 'fas fa-dollar-sign');
        $this->addOrderBy($viewName, ['fecha', 'idrecibo'], 'date', 2);
        $this->addOrderBy($viewName, ['fechapago'], 'payment-date');
        $this->addOrderBy($viewName, ['vencimiento'], 'expiration');
        $this->addOrderBy($viewName, ['importe'], 'amount');
        $this->addSearchFields($viewName, ['observaciones']);

        /// filters
        $this->addFilterPeriod($viewName, 'date', 'period', 'fecha');
        $this->addFilterAutocomplete($viewName, 'codcliente', 'customer', 'codcliente', 'Cliente');
        $this->addFilterNumber($viewName, 'min-total', 'amount', 'importe', '>=');
        $this->addFilterNumber($viewName, 'max-total', 'amount', 'importe', '<=');

        $currencies = $this->codeModel->all('divisas', 'coddivisa', 'descripcion');
        $this->addFilterSelect($viewName, 'coddivisa', 'currency', 'coddivisa', $currencies);

        $paymentValues = $this->codeModel->all('formaspago', 'codpago', 'descripcion');
        $this->addFilterSelect($viewName, 'codpago', 'payment-method', 'codpago', $paymentValues);

        $this->addFilterCheckbox($viewName, 'pagado', 'unpaid', '', '!=');

        /// buttons
        $this->addButtonPayReceipt($viewName);

        /// settings
        $this->setSettings($viewName, 'btnNew', false);
    }
}
