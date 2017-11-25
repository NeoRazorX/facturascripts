<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\ExtendedController;
use FacturaScripts\Core\Model\CuentaBanco;
use FacturaScripts\Core\Model\FormaPago;

/**
 * Controller to list the items in the FormaPago model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ListFormaPago extends ExtendedController\ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'payment-methods';
        $pagedata['icon'] = 'fa-credit-card';
        $pagedata['menu'] = 'accounting';

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        /* Formas de pago */
        $this->addView(FormaPago::class, 'ListFormaPago', 'payment-methods', 'fa-credit-card');
        $this->addSearchFields('ListFormaPago', ['descripcion', 'codpago', 'codcuenta']);

        $this->addOrderBy('ListFormaPago', 'codpago', 'code');
        $this->addOrderBy('ListFormaPago', 'descripcion', 'description');

        $this->addFilterSelect('ListFormaPago', 'generación', 'formaspago', '', 'genrecibos');
        $this->addFilterSelect('ListFormaPago', 'vencimiento', 'formaspago');
        $this->addFilterCheckbox('ListFormaPago', 'domiciliado', 'domicilied');
        $this->addFilterCheckbox('ListFormaPago', 'imprimir', 'print');

        /* Cuentas bancarias */
        $this->addView(CuentaBanco::class, 'ListCuentaBanco', 'bank-accounts', 'fa-university');
        $this->addSearchFields('ListCuentaBanco', ['descripcion', 'codcuenta']);

        $this->addOrderBy('ListCuentaBanco', 'codcuenta', 'code');
        $this->addOrderBy('ListCuentaBanco', 'descripcion', 'description');
    }
}
