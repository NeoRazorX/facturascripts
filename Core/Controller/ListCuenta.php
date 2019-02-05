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

use FacturaScripts\Dinamic\Lib\ExtendedController\ListController;
use FacturaScripts\Dinamic\Model\CodeModel;

/**
 * Controller to list the items in the Cuenta model.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class ListCuenta extends ListController
{

    /**
     *
     * @var CodeModel[]
     */
    protected $exerciseValues;

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
     * 
     * @param string $name
     */
    protected function createViewCuentas($name = 'ListCuenta')
    {
        $this->addView($name, 'Cuenta', 'accounts', 'fas fa-book');
        $this->addOrderBy($name, ['codejercicio desc, codcuenta'], 'code');
        $this->addOrderBy($name, ['codejercicio desc, descripcion'], 'description');
        $this->addSearchFields($name, ['descripcion', 'codcuenta', 'codejercicio', 'codcuentaesp']);

        /// filters
        $this->addFilterSelect($name, 'codejercicio', 'exercise', 'codejercicio', $this->exerciseValues);

        $specialAccountsValues = $this->codeModel->all('cuentasesp', 'codcuentaesp', 'codcuentaesp');
        $this->addFilterSelect($name, 'codcuentaesp', 'special-account', 'codcuentaesp', $specialAccountsValues);
    }

    /**
     * 
     * @param string $name
     */
    protected function createViewCuentasEsp($name = 'ListCuentaEspecial')
    {
        $this->addView($name, 'CuentaEspecial', 'special-account', 'fas fa-newspaper');
        $this->addOrderBy($name, ['descripcion'], 'description');
        $this->addOrderBy($name, ['codcuentaesp'], 'code');
        $this->addSearchFields($name, ['descripcion', 'codcuentaesp']);

        /// disable new button
        $this->setSettings($name, 'btnNew', false);
    }

    /**
     * 
     * @param string $name
     */
    protected function createViewSubcuentas($name = 'ListSubcuenta')
    {
        $this->addView($name, 'Subcuenta', 'subaccounts', 'fas fa-th-list');
        $this->addOrderBy($name, ['codejercicio desc, codsubcuenta'], 'code');
        $this->addOrderBy($name, ['codejercicio desc, descripcion'], 'description');
        $this->addOrderBy($name, ['saldo'], 'balance');
        $this->addSearchFields($name, ['codsubcuenta', 'descripcion', 'codejercicio', 'codcuentaesp']);

        /// filters
        $this->addFilterSelect($name, 'codejercicio', 'exercise', 'codejercicio', $this->exerciseValues);
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        /// load exercises in class to use in filters
        $this->exerciseValues = $this->codeModel->all('ejercicios', 'codejercicio', 'nombre');

        $this->createViewSubcuentas();
        $this->createViewCuentas();
        $this->createViewCuentasEsp();
    }
}
