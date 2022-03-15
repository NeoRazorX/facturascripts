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
        $data['icon'] = 'fas fa-file-invoice-dollar';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewSales('ListFacturaCliente', 'FacturaCliente', 'invoices');
        if ($this->permissions->onlyOwnerData === false) {
            $this->createViewLines('ListLineaFacturaCliente', 'LineaFacturaCliente');
        }
        $this->createViewReceipts();
    }

    /**
     * @param string $viewName
     * @param string $modelName
     * @param string $label
     */
    protected function createViewSales(string $viewName, string $modelName, string $label)
    {
        parent::createViewSales($viewName, $modelName, $label);
        $this->addFilterCheckbox('ListFacturaCliente', 'pagada', 'unpaid', 'pagada', '=', false);
        $this->addFilterCheckbox('ListFacturaCliente', 'idasiento', 'invoice-without-acc-entry', 'idasiento', 'IS', null);
        $this->addButtonLockInvoice('ListFacturaCliente');
    }

    /**
     * @param string $viewName
     */
    protected function createViewReceipts(string $viewName = 'ListReciboCliente')
    {
        $this->addView($viewName, 'ReciboCliente', 'receipts', 'fas fa-dollar-sign');
        $this->addOrderBy($viewName, ['fecha', 'idrecibo'], 'date', 2);
        $this->addOrderBy($viewName, ['fechapago'], 'payment-date');
        $this->addOrderBy($viewName, ['vencimiento'], 'expiration');
        $this->addOrderBy($viewName, ['importe'], 'amount');
        $this->addSearchFields($viewName, ['codigofactura', 'observaciones']);

        // filters
        $this->addFilterPeriod($viewName, 'date', 'period', 'fecha');
        $this->addFilterAutocomplete($viewName, 'codcliente', 'customer', 'codcliente', 'Cliente');
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
}
