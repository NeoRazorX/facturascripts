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
use FacturaScripts\Core\DataSrc\FormasPago;
use FacturaScripts\Core\DataSrc\Retenciones;
use FacturaScripts\Core\DataSrc\Series;
use FacturaScripts\Dinamic\Lib\ExtendedController\BaseView;
use FacturaScripts\Dinamic\Lib\ExtendedController\EditController;
use FacturaScripts\Dinamic\Model\Cliente;

/**
 * Controller to edit a single item from the GrupoClientes model
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Nazca Networks                <comercial@nazcanetworks.com>
 * @author Cristo M. Estévez Hernández   <cristom.estevez@gmail.com>
 */
class EditGrupoClientes extends EditController
{
    public function getModelClassName(): string
    {
        return 'GrupoClientes';
    }

    public function getPageData(): array
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
        if (false === is_array($codes)) {
            return;
        }

        $num = 0;
        $cliente = new Cliente();
        foreach ($codes as $code) {
            if (false === $cliente->loadFromCode($code)) {
                return;
            }

            $cliente->codgrupo = $this->request->query->get('code');
            if ($cliente->save()) {
                $num++;
            }
        }

        $this->toolBox()->i18nLog()->notice('items-added-correctly', ['%num%' => $num]);
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
     * @param string $viewName
     */
    protected function createViewCommon(string $viewName)
    {
        $this->views[$viewName]->addOrderBy(['codcliente'], 'code');
        $this->views[$viewName]->addOrderBy(['email'], 'email');
        $this->views[$viewName]->addOrderBy(['fechaalta'], 'creation-date');
        $this->views[$viewName]->addOrderBy(['nombre'], 'name', 1);
        $this->views[$viewName]->addOrderBy(['riesgoalcanzado'], 'current-risk');
        $this->views[$viewName]->searchFields = ['cifnif', 'codcliente', 'email', 'nombre', 'observaciones', 'razonsocial', 'telefono1', 'telefono2'];

        // settings
        $this->views[$viewName]->disableColumn('group');
        $this->views[$viewName]->settings['btnNew'] = false;
        $this->views[$viewName]->settings['btnDelete'] = false;

        // filters
        $i18n = $this->toolBox()->i18n();
        $values = [
            ['label' => $i18n->trans('only-active'), 'where' => [new DataBaseWhere('debaja', false)]],
            ['label' => $i18n->trans('only-suspended'), 'where' => [new DataBaseWhere('debaja', true)]],
            ['label' => $i18n->trans('all'), 'where' => []]
        ];
        $this->views[$viewName]->addFilterSelectWhere('status', $values);

        $this->views[$viewName]->addFilterSelect('codserie', 'series', 'codserie', Series::codeModel());
        $this->views[$viewName]->addFilterSelect('codretencion', 'retentions', 'codretencion', Retenciones::codeModel());
        $this->views[$viewName]->addFilterSelect('codpago', 'payment-methods', 'codpago', FormasPago::codeModel());
    }

    /**
     * @param string $viewName
     */
    protected function createViewCustomers(string $viewName = 'ListCliente')
    {
        $this->addListView($viewName, 'Cliente', 'customers', 'fas fa-users');
        $this->createViewCommon($viewName);

        // add action button
        $this->addButton($viewName, [
            'action' => 'remove-customer',
            'color' => 'danger',
            'confirm' => true,
            'icon' => 'fas fa-folder-minus',
            'label' => 'remove-from-list'
        ]);
    }

    /**
     * @param string $viewName
     */
    protected function createViewNewCustomers(string $viewName = 'ListCliente-new')
    {
        $this->addListView($viewName, 'Cliente', 'add', 'fas fa-user-plus');
        $this->createViewCommon($viewName);

        // add action button
        $this->addButton($viewName, [
            'action' => 'add-customer',
            'color' => 'success',
            'icon' => 'fas fa-folder-plus',
            'label' => 'add'
        ]);
    }

    /**
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
        }

        return parent::execPreviousAction($action);
    }

    /**
     * Procedure responsible for loading the data to be displayed.
     *
     * @param string $viewName
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
        if (false === is_array($codes)) {
            return;
        }

        $num = 0;
        $cliente = new Cliente();
        foreach ($codes as $code) {
            if (false === $cliente->loadFromCode($code)) {
                return;
            }

            $cliente->codgrupo = null;
            if ($cliente->save()) {
                $num++;
            }
        }

        $this->toolBox()->i18nLog()->notice('items-removed-correctly', ['%num%' => $num]);
    }
}
