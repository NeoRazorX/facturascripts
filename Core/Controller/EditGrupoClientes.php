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
use FacturaScripts\Dinamic\Lib\ExtendedController\BaseView;
use FacturaScripts\Dinamic\Lib\ExtendedController\EditController;

/**
 * Controller to edit a single item from the GrupoClientes model
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Artex Trading sa             <jcuello@artextrading.com>
 * @author Nazca Networks               <comercial@nazcanetworks.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class EditGrupoClientes extends EditController
{

    /**
     * Returns the class name of the model to use in the editView.
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'GrupoClientes';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'customer-group';
        $data['icon'] = 'fas fa-users-cog';
        return $data;
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createCustomerView($viewName = 'ListCliente')
    {
        $this->addListView($viewName, 'Cliente', 'customers', 'fas fa-users');
        $this->views[$viewName]->addOrderBy(['codcliente'], 'code', 1);
        $this->views[$viewName]->addOrderBy(['email'], 'email');
        $this->views[$viewName]->addOrderBy(['fechaalta'], 'creation-date');
        $this->views[$viewName]->addOrderBy(['nombre'], 'name');
        $this->views[$viewName]->searchFields = ['cifnif', 'codcliente', 'email', 'nombre', 'observaciones', 'razonsocial', 'telefono1', 'telefono2'];

        /// settings
        $this->views[$viewName]->disableColumn('group');
        $this->views[$viewName]->settings['btnNew'] = false;
        $this->views[$viewName]->settings['btnDelete'] = false;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        $this->createCustomerView();
    }

    /**
     * Procedure responsible for loading the data to be displayed.
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListCliente':
                $codgrupo = $this->getViewModelValue('EditGrupoClientes', 'codgrupo');
                $where = [new DataBaseWhere('codgrupo', $codgrupo)];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }
}
