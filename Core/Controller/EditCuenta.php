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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Controller to edit a single item from the Cuenta model
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Artex Trading sa             <jcuello@artextrading.com>
 * @author PC REDNET S.L.               <luismi@pcrednet.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class EditCuenta extends EditController
{

    /**
     * Returns the class name of the model to use in the editView.
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'Cuenta';
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
        $data['title'] = 'account';
        $data['icon'] = 'fas fa-book';
        return $data;
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createAccountingView($viewName = 'ListCuenta')
    {
        $this->addListView($viewName, 'Cuenta', 'children-accounts', 'fas fa-level-down-alt');
        $this->views[$viewName]->addOrderBy(['codcuenta'], 'code', 1);

        /// disable columns
        $this->views[$viewName]->disableColumn('fiscal-exercise');
        $this->views[$viewName]->disableColumn('parent-account');
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createSubAccountingView($viewName = 'ListSubcuenta')
    {
        $this->addListView($viewName, 'Subcuenta', 'subaccounts');
        $this->views[$viewName]->addOrderBy(['codsubcuenta'], 'code', 1);
        $this->views[$viewName]->addOrderBy(['saldo'], 'balance');

        /// disable columns
        $this->views[$viewName]->disableColumn('fiscal-exercise');
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        $this->createSubAccountingView();
        $this->createAccountingView();
    }

    /**
     * Load view data procedure
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $idcuenta = $this->getViewModelValue('EditCuenta', 'idcuenta');
        switch ($viewName) {
            case 'ListCuenta':
                $where = [new DataBaseWhere('parent_idcuenta', $idcuenta)];
                $view->loadData('', $where);
                break;

            case 'ListSubcuenta':
                $where = [new DataBaseWhere('idcuenta', $idcuenta)];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }
}
