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
use FacturaScripts\Core\Lib\ExtendedController;
use FacturaScripts\Dinamic\Lib\RegimenIVA;

/**
 * Controller to edit a single item from the  Empresa model
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class EditEmpresa extends ExtendedController\EditController
{

    /**
     * Returns the model name.
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'Empresa';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'company';
        $pagedata['menu'] = 'admin';
        $pagedata['icon'] = 'fas fa-building';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Create views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->createViewWarehouse();
        $this->createViewBankAccounts();
        $this->createViewPaymentMethods();
        $this->createViewExercises();
    }

    /**
     * 
     * @param string $viewName
     */
    private function createViewBankAccounts($viewName = 'EditCuentaBanco')
    {
        $this->addEditListView($viewName, 'CuentaBanco', 'bank-accounts', 'fas fa-piggy-bank');
        $this->views[$viewName]->disableColumn('company');
    }

    /**
     * 
     * @param string $viewName
     */
    private function createViewExercises($viewName = 'ListEjercicio')
    {
        $this->addListView($viewName, 'Ejercicio', 'exercises', 'fas fa-calendar-alt');
        $this->views[$viewName]->disableColumn('company');
    }

    /**
     * 
     * @param string $viewName
     */
    private function createViewPaymentMethods($viewName = 'EditFormaPago')
    {
        $this->addEditListView($viewName, 'FormaPago', 'payment-method', 'fas fa-credit-card');
        $this->views[$viewName]->disableColumn('company');
    }

    /**
     * 
     * @param string $viewName
     */
    private function createViewWarehouse($viewName = 'EditAlmacen')
    {
        $this->addEditListView($viewName, 'Almacen', 'warehouses', 'fas fa-building');
        $this->views[$viewName]->disableColumn('company');
    }

    /**
     * Load view data procedure
     *
     * @param string                      $viewName
     * @param ExtendedController\EditView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'EditAlmacen':
            case 'EditCuentaBanco':
            case 'EditFormaPago':
            case 'ListEjercicio':
                $idcompany = $this->getViewModelValue('EditEmpresa', 'idempresa');
                $where = [new DataBaseWhere('idempresa', $idcompany)];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                $this->setCustomWidgetValues();
                break;
        }
    }

    protected function setCustomWidgetValues()
    {
        /// Load values option to VAT Type select input
        $columnVATType = $this->views['EditEmpresa']->columnForName('vat-regime');
        $columnVATType->widget->setValuesFromArrayKeys(RegimenIVA::all());
    }
}
