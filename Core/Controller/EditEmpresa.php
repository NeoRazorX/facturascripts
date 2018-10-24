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
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Controller to edit a single item from the  Empresa model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class EditEmpresa extends ExtendedController\EditController
{

    /**
     * Create views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->createViewBankAccounts();
        $this->createViewWarehouse();
    }

    private function createViewBankAccounts()
    {
        $this->addEditListView('EditCuentaBanco', 'CuentaBanco', 'accounts', 'fas fa-piggy-bank');
        $this->hideCompanyColumn('EditCuentaBanco');
    }

    private function createViewWarehouse()
    {
        $this->addEditListView('EditAlmacen', 'Almacen', 'warehouse', 'fas fa-building');
        $this->hideCompanyColumn('EditAlmacen');
    }

    private function hideCompanyColumn($viewName)
    {
        $companyColumn = $this->views[$viewName]->columnForField('idempresa');
        $companyColumn->display = 'none';
    }

    /**
     * Returns the model name
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
        $pagedata['icon'] = 'fas fa-home';
        $pagedata['showonmenu'] = false;

        return $pagedata;
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
            case 'EditCuentaBanco':
            case 'EditAlmacen':
                $idcompany = $this->getViewModelValue('EditEmpresa', 'idempresa');
                $where = [new DataBaseWhere('idempresa', $idcompany)];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }
}
