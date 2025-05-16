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
 * Controller to edit a single item from the Retencion model
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class EditRetencion extends EditController
{
    public function getModelClassName(): string
    {
        return 'Retencion';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'retention';
        $data['icon'] = 'fa-solid fa-plus-square';
        return $data;
    }

    /**
     * Create the view to display.
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');
        $this->createViewsCustomers();
        $this->createViewsSuppliers();
    }

    protected function createViewsCustomers(string $viewName = 'ListCliente')
    {
        $this->addListView($viewName, 'Cliente', 'customers', 'fa-solid fa-users');
        $this->views[$viewName]->addSearchFields(['cifnif', 'codcliente', 'email', 'nombre', 'observaciones', 'razonsocial', 'telefono1', 'telefono2']);
        $this->views[$viewName]->addOrderBy(['nombre'], 'name', 1);

        // disable buttons
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'btnDelete', false);
    }

    protected function createViewsSuppliers(string $viewName = 'ListProveedor')
    {
        $this->addListView($viewName, 'Proveedor', 'suppliers', 'fa-solid fa-users');
        $this->views[$viewName]->addSearchFields(['cifnif', 'codproveedor', 'email', 'nombre', 'observaciones', 'razonsocial', 'telefono1', 'telefono2']);
        $this->views[$viewName]->addOrderBy(['nombre'], 'name', 1);

        // disable buttons
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'btnDelete', false);
    }

    /**
     * Loads the data to display.
     *
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListCliente':
            case 'ListProveedor':
                $codretencion = $this->getViewModelValue($this->getMainViewName(), 'codretencion');
                $where = [new DataBaseWhere('codretencion', $codretencion)];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }
}
