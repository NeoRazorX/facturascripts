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
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\GrupoClientes;

/**
 * Controller to edit a single item from the Tarifa model
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author jhonsmall            <juancarloschico0@gmail.com>
 */
class EditTarifa extends EditController
{

    /**
     * Returns the model name.
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'Tarifa';
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
        $data['title'] = 'rate';
        $data['icon'] = 'fas fa-percentage';
        return $data;
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createCustomerGroupView(string $viewName = 'ListGrupoClientes')
    {
        $this->addListView($viewName, 'GrupoClientes', 'customer-group', 'fas fa-users-cog');
        $this->views[$viewName]->searchFields = ['nombre', 'codgrupo'];
        $this->views[$viewName]->addOrderBy(['codgrupo'], 'code');
        $this->views[$viewName]->addOrderBy(['nombre'], 'name', 1);

        /// disable column
        $this->views[$viewName]->disableColumn('rate');

        /// disable buttons
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);

        /// add custom buttons
        $this->addButton($viewName, [
            'action' => 'setgrouprate',
            'color' => 'success',
            'icon' => 'fas fa-folder-plus',
            'label' => 'add',
            'type' => 'modal'
        ]);
        $this->addButton($viewName, [
            'action' => 'unsetgrouprate',
            'color' => 'danger',
            'confirm' => true,
            'icon' => 'fas fa-folder-minus',
            'label' => 'remove-from-list'
        ]);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createCustomerView(string $viewName = 'ListCliente')
    {
        $this->addListView($viewName, 'Cliente', 'customers', 'fas fa-users');
        $this->views[$viewName]->searchFields = ['cifnif', 'codcliente', 'email', 'nombre', 'observaciones', 'razonsocial', 'telefono1', 'telefono2'];
        $this->views[$viewName]->addOrderBy(['codcliente'], 'code');
        $this->views[$viewName]->addOrderBy(['nombre'], 'name', 1);
        $this->views[$viewName]->addOrderBy(['fechaalta', 'codcliente'], 'date');

        /// disable buttons
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);

        $this->addButton($viewName, [
            'action' => 'setcustomerrate',
            'color' => 'success',
            'icon' => 'fas fa-folder-plus',
            'label' => 'add',
            'type' => 'modal'
        ]);
        $this->addButton($viewName, [
            'action' => 'unsetcustomerrate',
            'color' => 'danger',
            'confirm' => true,
            'icon' => 'fas fa-folder-minus',
            'label' => 'remove-from-list'
        ]);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createProductView(string $viewName = 'ListTarifaProducto')
    {
        $this->addListView($viewName, 'Join\TarifaProducto', 'products', 'fas fa-cubes');
        $this->views[$viewName]->addOrderBy(['coste'], 'cost-price');
        $this->views[$viewName]->addOrderBy(['descripcion'], 'description');
        $this->views[$viewName]->addOrderBy(['precio'], 'price');
        $this->views[$viewName]->addOrderBy(['referencia'], 'reference', 1);
        $this->views[$viewName]->addSearchFields(['variantes.referencia', 'descripcion']);

        /// disable buttons
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'checkBoxes', false);
    }

    /**
     * Creates tabs or views.
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        $this->createProductView();
        $this->createCustomerGroupView();
        $this->createCustomerView();
    }

    /**
     * 
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListCliente':
            case 'ListGrupoClientes':
            case 'ListTarifaProducto':
                $codtarifa = $this->getViewModelValue($this->getMainViewName(), 'codtarifa');
                $where = [new DataBaseWhere('codtarifa', $codtarifa)];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
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
            case 'unsetcustomerrate':
                $this->unsetCustomerRate();
                break;

            case 'unsetgrouprate':
                $this->unsetGroupRate();
                break;

            case 'setcustomerrate':
                $this->setCustomerRate();
                break;

            case 'setgrouprate':
                $this->setGroupRate();
                break;

            default:
                return parent::execPreviousAction($action);
        }
    }

    protected function unsetCustomerRate()
    {
        $codes = $this->request->request->get('code', '');
        if (empty($codes) || false === \is_array($codes)) {
            $this->toolBox()->i18nLog()->warning('no-selected-item');
            return;
        }

        $customer = new Cliente();
        foreach ($codes as $cod) {
            if ($customer->loadFromCode($cod)) {
                $customer->codtarifa = null;
                $customer->save();
            }
        }

        $this->toolBox()->i18nLog()->notice('record-updated-correctly');
    }

    protected function unsetGroupRate()
    {
        $codes = $this->request->request->get('code', '');
        if (empty($codes) || false === \is_array($codes)) {
            $this->toolBox()->i18nLog()->warning('no-selected-item');
            return;
        }

        $group = new GrupoClientes();
        foreach ($codes as $cod) {
            if ($group->loadFromCode($cod)) {
                $group->codtarifa = null;
                $group->save();
            }
        }

        $this->toolBox()->i18nLog()->notice('record-updated-correctly');
    }

    protected function setCustomerRate()
    {
        $customer = new Cliente();
        $code = $this->request->request->get('setcustomerrate');
        if (empty($code) || false === $customer->loadFromCode($code)) {
            $this->toolBox()->i18nLog()->warning('customer-not-found');
            return;
        }

        $customer->codtarifa = $this->request->get('code');
        if ($customer->save()) {
            $this->toolBox()->i18nLog()->notice('record-updated-correctly');
            return;
        }

        $this->toolBox()->i18nLog()->warning('record-save-error');
    }

    protected function setGroupRate()
    {
        $group = new GrupoClientes();
        $code = $this->request->request->get('setgrouprate');
        if (empty($code) || false === $group->loadFromCode($code)) {
            $this->toolBox()->i18nLog()->warning('group-not-found');
            return;
        }

        $group->codtarifa = $this->request->get('code');
        if ($group->save()) {
            $this->toolBox()->i18nLog()->notice('record-updated-correctly');
            return;
        }

        $this->toolBox()->i18nLog()->warning('record-save-error');
    }
}
