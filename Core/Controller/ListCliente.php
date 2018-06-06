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

/**
 * Controller to list the items in the Cliente model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 * @author Cristo M. Estévez Hernández <cristom.estevez@gmail.com>
 */
class ListCliente extends ExtendedController\ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'customers';
        $pagedata['icon'] = 'fa-users';
        $pagedata['menu'] = 'sales';

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        /* Customers */
        $this->addView('ListCliente', 'Cliente', 'customers', 'fa-users');
        $this->addSearchFields('ListCliente', ['nombre', 'razonsocial', 'codcliente', 'email']);
        $this->addOrderBy('ListCliente', ['codcliente'], 'code');
        $this->addOrderBy('ListCliente', ['nombre'], 'name', 1);
        $this->addOrderBy('ListCliente', ['fechaalta', 'codcliente'], 'date');

        $selectValues = $this->codeModel->all('gruposclientes', 'codgrupo', 'nombre');
        $this->addFilterSelect('ListCliente', 'codgrupo', 'group', 'codgrupo', $selectValues);
        $this->addFilterCheckbox('ListCliente', 'debaja', 'suspended', 'debaja');

        $this->createViewAddresses();

        /* Groups */
        $this->addView('ListGrupoClientes', 'GrupoClientes', 'groups', 'fa-folder-open');
        $this->addSearchFields('ListGrupoClientes', ['nombre', 'codgrupo']);
        $this->addOrderBy('ListGrupoClientes', ['codgrupo'], 'code');
        $this->addOrderBy('ListGrupoClientes', ['nombre'], 'name', 1);
        $this->addFilterSelect('ListGrupoClientes', 'parent', 'parent', 'parent', $selectValues);
    }

    private function createViewAddresses(): void
    {
        $this->addView('ListDireccionCliente', 'DireccionCliente', 'addresses', 'fa-road');
        $this->addSearchFields('ListDireccionCliente', ['codcliente', 'descripcion', 'direccion', 'ciudad', 'provincia', 'codpostal']);
        $this->addOrderBy('ListDireccionCliente', ['codcliente'], 'customer');
        $this->addOrderBy('ListDireccionCliente', ['descripcion'], 'description');
        $this->addOrderBy('ListDireccionCliente', ['codpostal'], 'postalcode');

        $cities = $this->codeModel->all('dirproveedores', 'ciudad', 'ciudad');
        $this->addFilterSelect('ListDireccionCliente', 'ciudad', 'city', 'ciudad', $cities);

        $provinces = $this->codeModel->all('dirproveedores', 'provincia', 'provincia');
        $this->addFilterSelect('ListDireccionCliente', 'provincia', 'province', 'provincia', $provinces);

        $countries = $this->codeModel->all('paises', 'codpais', 'nombre');
        $this->addFilterSelect('ListDireccionCliente', 'codpais', 'country', 'codpais', $countries);
    }
}
