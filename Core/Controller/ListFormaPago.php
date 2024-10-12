<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\Lib\ExtendedController\ListController;

/**
 * Controller to list the items in the FormaPago model
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class ListFormaPago extends ListController
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'payment-methods';
        $data['icon'] = 'fa-solid fa-credit-card';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewsPaymentMethods();
        $this->createViewsBankAccounts();
    }

    protected function createViewsBankAccounts(string $viewName = 'ListCuentaBanco'): void
    {
        $this->addView($viewName, 'CuentaBanco', 'bank-accounts', 'fa-solid fa-piggy-bank')
            ->addSearchFields(['descripcion', 'codcuenta'])
            ->addOrderBy(['codcuenta'], 'code')
            ->addOrderBy(['descripcion'], 'description');

        // si solamente hay una empresa, ocultamos la columna de empresa, de lo contrario, añadimos el filtro
        if (count(Empresas::all()) === 1) {
            $this->listView($viewName)->disableColumn('company');
        } else {
            $this->addFilterSelect($viewName, 'idempresa', 'company', 'idempresa', Empresas::codeModel());
        }
    }

    protected function createViewsPaymentMethods(string $viewName = 'ListFormaPago'): void
    {
        $this->addView($viewName, 'FormaPago', 'payment-methods', 'fa-solid fa-credit-card')
            ->addSearchFields(['descripcion', 'codpago'])
            ->addOrderBy(['codpago', 'idempresa'], 'code')
            ->addOrderBy(['descripcion'], 'description')
            ->addOrderBy(['idempresa', 'codpago'], 'company');

        // si solamente hay una empresa, ocultamos la columna de empresa, de lo contrario, añadimos el filtro
        if (count(Empresas::all()) === 1) {
            $this->listView($viewName)->disableColumn('company');
        } else {
            $this->addFilterSelect($viewName, 'idempresa', 'company', 'idempresa', Empresas::codeModel());
        }

        $this->addFilterCheckbox($viewName, 'pagado', 'paid', 'pagado');
        $this->addFilterCheckbox($viewName, 'domiciliado', 'domiciled', 'domiciliado');
    }
}
