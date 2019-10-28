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
use FacturaScripts\Dinamic\Model\Cliente;

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

    protected function addCustomerAction()
    {
        $codes = $this->request->request->get('code', []);
        if (is_array($codes)) {
            foreach ($codes as $code) {
                $cliente = new Cliente();
                if ($cliente->loadFromCode($code)) {
                    $cliente->codgrupo = $this->request->query->get('code');
                    $cliente->save();
                }
            }

            $this->toolBox()->i18nLog()->notice('record-updated-correctly');
        }
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        $this->createViewCustomers();
        $this->createViewNewCustomers();
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewCustomers(string $viewName = 'ListCliente')
    {
        $this->addListView($viewName, 'Cliente', 'customers', 'fas fa-user-check');
        $this->views[$viewName]->addOrderBy(['codcliente'], 'code');
        $this->views[$viewName]->addOrderBy(['email'], 'email');
        $this->views[$viewName]->addOrderBy(['fechaalta'], 'creation-date');
        $this->views[$viewName]->addOrderBy(['nombre'], 'name', 1);
        $this->views[$viewName]->searchFields = ['cifnif', 'codcliente', 'email', 'nombre', 'observaciones', 'razonsocial', 'telefono1', 'telefono2'];

        /// settings
        $this->views[$viewName]->disableColumn('group');
        $this->views[$viewName]->settings['btnNew'] = false;
        $this->views[$viewName]->settings['btnDelete'] = false;

        /// add action button
        $newButton = [
            'action' => 'remove-customer',
            'color' => 'danger',
            'confirm' => true,
            'icon' => 'fas fa-user-times',
            'label' => 'remove',
        ];
        $this->addButton($viewName, $newButton);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewNewCustomers(string $viewName = 'ListCliente-new')
    {
        $this->addListView($viewName, 'Cliente', 'add', 'fas fa-user-times');
        $this->views[$viewName]->addOrderBy(['codcliente'], 'code');
        $this->views[$viewName]->addOrderBy(['email'], 'email');
        $this->views[$viewName]->addOrderBy(['fechaalta'], 'creation-date');
        $this->views[$viewName]->addOrderBy(['nombre'], 'name', 1);
        $this->views[$viewName]->searchFields = ['cifnif', 'codcliente', 'email', 'nombre', 'observaciones', 'razonsocial', 'telefono1', 'telefono2'];

        /// settings
        $this->views[$viewName]->disableColumn('group');
        $this->views[$viewName]->settings['btnNew'] = false;
        $this->views[$viewName]->settings['btnDelete'] = false;

        /// add action button
        $newButton = [
            'action' => 'add-customer',
            'color' => 'success',
            'icon' => 'fas fa-user-check',
            'label' => 'new',
        ];
        $this->addButton($viewName, $newButton);
    }

    /**
     * 
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'add-customer':
                $this->addCustomerAction();
                return true;

            case 'remove-customer':
                $this->removeCustomerAction();
                return true;

            default:
                return parent::execPreviousAction($action);
        }
    }

    /**
     * Procedure responsible for loading the data to be displayed.
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $codgrupo = $this->getViewModelValue('EditGrupoClientes', 'codgrupo');
        switch ($viewName) {
            case 'ListCliente':
                $where = [new DataBaseWhere('codgrupo', $codgrupo)];
                $view->loadData('', $where);
                break;

            case 'ListCliente-new':
                $where = [new DataBaseWhere('codgrupo', null, 'IS')];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

    protected function removeCustomerAction()
    {
        $codes = $this->request->request->get('code', []);
        if (is_array($codes)) {
            foreach ($codes as $code) {
                $cliente = new Cliente();
                if ($cliente->loadFromCode($code)) {
                    $cliente->codgrupo = null;
                    $cliente->save();
                }
            }

            $this->toolBox()->i18nLog()->notice('record-deleted-correctly');
        }
    }
}
