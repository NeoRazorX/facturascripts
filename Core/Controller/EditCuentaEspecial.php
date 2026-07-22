<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Core\Where;

/**
 * Controlador para editar un único elemento del modelo CuentaEspecial
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
        $this->addListView($viewName, 'Cuenta', 'accounts', 'fa-solid fa-book')
            ->addOrderBy(['codejercicio', 'codcuenta'], 'exercise', 2)
            ->addOrderBy(['descripcion'], 'description')
            ->addSearchFields(['codcuenta', 'descripcion'])
            // desactivamos columnas y botones
            ->disableColumn('special-account')
            ->setSettings('btnDelete', false)
            ->setSettings('btnNew', false)
            ->setSettings('checkBoxes', false);
    }

    protected function createSubaccountsView(string $viewName = 'ListSubcuenta')
    {
        $this->addListView($viewName, 'Subcuenta', 'subaccounts', 'fa-solid fa-th-list')
            ->addOrderBy(['codejercicio', 'codsubcuenta'], 'exercise', 2)
            ->addOrderBy(['descripcion'], 'description')
            ->addSearchFields(['codsubcuenta', 'descripcion'])
            // desactivamos columnas y botones
            ->disableColumn('special-account')
            ->setSettings('btnDelete', false)
            ->setSettings('btnNew', false)
            ->setSettings('checkBoxes', false);
    }

    /**
     * Create tabs or views.
     */
    protected function createViews()
    {
        parent::createViews();

        // disable buttons
        $mainViewName = $this->mainTabName();
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
                $codcuentaesp = $this->tabModelValue('EditCuentaEspecial', 'codcuentaesp');
                $where = [Where::eq('codcuentaesp', $codcuentaesp)];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
        }
    }
}
