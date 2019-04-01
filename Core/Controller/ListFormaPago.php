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

use FacturaScripts\Core\Lib\ExtendedController;

/**
 * Controller to list the items in the FormaPago model
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class ListFormaPago extends ExtendedController\ListController
{

    /**
     * List of companies to filter the views
     *
     * @var array
     */
    private $companyValues = [];

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'payment-methods';
        $pagedata['icon'] = 'fas fa-credit-card';
        $pagedata['menu'] = 'accounting';

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        // Get company list
        $this->companyValues = $this->codeModel->all('empresas', 'idempresa', 'nombre');

        // Add views
        $this->createViewsPaymentMethods();
        $this->createViewsBankAccounts();
    }

    /**
     * Add Bank Acounts view
     * 
     * @param string $viewName
     */
    private function createViewsBankAccounts($viewName = 'ListCuentaBanco')
    {
        $this->addView($viewName, 'CuentaBanco', 'bank-accounts', 'fas fa-piggy-bank');
        $this->addSearchFields($viewName, ['descripcion', 'codcuenta']);
        $this->addOrderBy($viewName, ['codcuenta'], 'code');
        $this->addOrderBy($viewName, ['descripcion'], 'description');

        $this->addFilterSelect('ListCuentaBanco', 'idempresa', 'company', 'idempresa', $this->companyValues);
    }

    /**
     * Add Payment Methods view
     * 
     * @param string $viewName
     */
    private function createViewsPaymentMethods($viewName = 'ListFormaPago')
    {
        $this->addView($viewName, 'FormaPago', 'payment-methods', 'fas fa-credit-card');
        $this->addSearchFields($viewName, ['descripcion', 'codpago']);
        $this->addOrderBy($viewName, ['codpago'], 'code');
        $this->addOrderBy($viewName, ['descripcion'], 'description');
        $this->addOrderBy($viewName, ['idempresa', 'codpago'], 'company');

        $this->addFilterSelect($viewName, 'idempresa', 'company', 'idempresa', $this->companyValues);
        $this->addFilterSelect($viewName, 'genreceipt', 'generate-receipt', 'genrecibos', $this->getGenerateReceiptOptions());
        $this->addFilterCheckbox($viewName, 'domiciliado', 'domicilied', 'domiciliado');
        $this->addFilterCheckbox($viewName, 'imprimir', 'print', 'imprimir');
    }

    /**
     * Return list of generate receipt availables
     *
     * @return array
     */
    private function getGenerateReceiptOptions()
    {
        return [
            ['code' => 'Pagados', 'description' => $this->i18n->trans('paid')],
            ['code' => 'Emitidos', 'description' => $this->i18n->trans('issued')],
        ];
    }
}
