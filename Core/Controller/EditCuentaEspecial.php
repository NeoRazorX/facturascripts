<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class EditCuentaEspecial extends EditController
{
    public function getModelClassName(): string
    {
        return 'CuentaEspecial';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'special-account';
        $data['icon'] = 'fa-solid fa-newspaper';
        return $data;
    }

    protected function createAccountsView(string $viewName = 'ListCuenta')
    {
        $this->addListView($viewName, 'Cuenta', 'accounts', 'fa-solid fa-book');
        $this->views[$viewName]->addOrderBy(['codejercicio', 'codcuenta'], 'exercise', 2);
        $this->views[$viewName]->addOrderBy(['descripcion'], 'description');
        $this->views[$viewName]->addSearchFields(['codcuenta', 'descripcion']);

        // disable columns
        $this->views[$viewName]->disableColumn('special-account');

        // disable buttons
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'checkBoxes', false);
    }

    protected function createSubaccountsView(string $viewName = 'ListSubcuenta')
    {
        $this->addListView($viewName, 'Subcuenta', 'subaccounts', 'fa-solid fa-th-list');
        $this->views[$viewName]->addOrderBy(['codejercicio', 'codsubcuenta'], 'exercise', 2);
        $this->views[$viewName]->addOrderBy(['descripcion'], 'description');
        $this->views[$viewName]->addSearchFields(['codsubcuenta', 'descripcion']);

        // disable columns
        $this->views[$viewName]->disableColumn('special-account');

        // disable buttons
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

        // disable buttons
        $mainViewName = $this->getMainViewName();
        $this->setSettings($mainViewName, 'btnDelete', false);
        $this->setSettings($mainViewName, 'btnNew', false);

        $this->setTabsPosition('bottom');
        $this->createAccountsView();
        $this->createSubaccountsView();
    }

    /**
     * @param string $viewName
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
