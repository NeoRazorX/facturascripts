<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Controller to list the items in the Cuenta model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ListCuenta extends ExtendedController\ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'accounting-accounts';
        $pagedata['icon'] = 'fas fa-book';
        $pagedata['menu'] = 'accounting';

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        /* Sub-Accounts */
        $this->addView('ListSubcuenta', 'Subcuenta', 'subaccounts', 'fas fa-th-list');
        $this->addSearchFields('ListSubcuenta', ['codsubcuenta', 'descripcion', 'codejercicio', 'codcuentaesp']);

        $exerciseValues = $this->codeModel->all('ejercicios', 'codejercicio', 'nombre');
        $this->addFilterSelect('ListSubcuenta', 'codejercicio', 'exercise', 'codejercicio', $exerciseValues);

        $this->addOrderBy('ListSubcuenta', ['codejercicio desc, codsubcuenta'], 'code');
        $this->addOrderBy('ListSubcuenta', ['codejercicio desc, descripcion'], 'description');
        $this->addOrderBy('ListSubcuenta', ['saldo'], 'balance');

        /* Accounts */
        $this->addView('ListCuenta', 'Cuenta', 'accounts', 'fas fa-book');
        $this->addSearchFields('ListCuenta', ['descripcion', 'codcuenta', 'codejercicio', 'codcuentaesp']);

        $this->addFilterSelect('ListCuenta', 'codejercicio', 'exercise', 'codejercicio', $exerciseValues);
        
        $specialAccountsValues = $this->codeModel->all('cuentaespecial', 'codcuentaesp', 'descripcion');
        $this->addFilterSelect('ListCuenta', 'codcuentaesp', 'special-account', 'codcuentaesp', $specialAccountsValues);

        $this->addOrderBy('ListCuenta', ['codejercicio desc, codcuenta'], 'code');
        $this->addOrderBy('ListCuenta', ['codejercicio desc, descripcion'], 'description');

        /* Special account */
        $this->addView('ListCuentaEspecial', 'CuentaEspecial', 'special-account', 'fas fa-newspaper');
        $this->setSettings('ListCuentaEspecial', 'insert', false);
        $this->addSearchFields('ListCuentaEspecial', ['descripcion', 'codcuentaesp']);

        $this->addOrderBy('ListCuentaEspecial', ['descripcion'], 'description');
        $this->addOrderBy('ListCuentaEspecial', ['codcuentaesp'], 'code');
    }
}
