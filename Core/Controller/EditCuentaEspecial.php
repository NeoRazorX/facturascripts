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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Controller to edit a single item from the CuentaEspecial model
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jferrer@artextrading.com>
 */
class EditCuentaEspecial extends EditController
{

    /**
     * Returns the model name.
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'CuentaEspecial';
    }

    /**
     * Returns basic page attributes.
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'special-account';
        $data['icon'] = 'fas fa-newspaper';
        return $data;
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createAccountsView(string $viewName = 'ListCuenta')
    {
        $this->addListView($viewName, 'Cuenta', 'accounts', 'fas fa-book');
        $this->views[$viewName]->addOrderBy(['codejercicio'], 'exercise', 2);

        /// disable columns
        $this->views[$viewName]->disableColumn('special-account');

        /// disable buttons
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'checkBoxes', false);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createSubaccountsView(string $viewName = 'ListSubcuenta')
    {
        $this->addListView($viewName, 'Subcuenta', 'subaccounts', 'fas fa-th-list');
        $this->views[$viewName]->addOrderBy(['codejercicio'], 'exercise', 2);

        /// disable columns
        $this->views[$viewName]->disableColumn('special-account');

        /// disable buttons
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'checkBoxes', false);
    }

    /**
     * Create tabs or views.
     */
    protected function createViews()
    {
        parent::createViews();

        /// disable buttons
        $mainViewName = $this->getMainViewName();
        $this->setSettings($mainViewName, 'btnDelete', false);
        $this->setSettings($mainViewName, 'btnNew', false);

        $this->setTabsPosition('bottom');
        $this->createAccountsView();
        $this->createSubaccountsView();
    }

    /**
     * 
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListCuenta':
            case 'ListSubcuenta':
                $codcuentaesp = $this->getViewModelValue('EditCuentaEspecial', 'codcuentaesp');
                $where = [new DataBaseWhere('codcuentaesp', $codcuentaesp)];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
        }
    }
}
