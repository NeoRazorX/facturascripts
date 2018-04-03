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

use FacturaScripts\Core\Lib\ExtendedController;

/**
 * Controller to list the items in the Cuenta model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ListCuenta extends ExtendedController\ListController
{

    /**
     * Load views
     */
    protected function createViews()
    {
        /* Sub-Accounts */
        $this->addView('Subcuenta', 'ListSubcuenta', 'subaccounts', 'fa-th-list');
        $this->addSearchFields('ListSubcuenta', ['codsubcuenta', 'descripcion', 'codejercicio']);

        $this->addFilterSelect('ListSubcuenta', 'codejercicio', 'ejercicios', 'codejercicio', 'nombre');

        $this->addOrderBy('ListSubcuenta', 'codejercicio desc, codsubcuenta', 'code');
        $this->addOrderBy('ListSubcuenta', 'codejercicio desc, descripcion', 'description');

        /* Accounts */
        $this->addView('Cuenta', 'ListCuenta', 'accounts', 'fa-book');
        $this->addSearchFields('ListCuenta', ['descripcion', 'codcuenta', 'codejercicio']);

        $this->addOrderBy('ListCuenta', 'codejercicio desc, codcuenta', 'code');
        $this->addOrderBy('ListCuenta', 'codejercicio desc, descripcion', 'description');

        $this->addFilterSelect('ListCuenta', 'codejercicio', 'ejercicios', 'codejercicio', 'nombre');

        /* Special account */
        $this->addView('CuentaEspecial', 'ListCuentaEspecial', 'special-account', 'fa-newspaper-o');
        $this->addSearchFields('ListCuentaEspecial', ['descripcion', 'codcuentaesp']);

        $this->addOrderBy('ListCuentaEspecial', 'descripcion', 'description');
        $this->addOrderBy('ListCuentaEspecial', 'codcuentaesp', 'code');
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'accounting-accounts';
        $pagedata['icon'] = 'fa-book';
        $pagedata['menu'] = 'accounting';

        return $pagedata;
    }
}
