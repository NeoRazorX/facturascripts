<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

/**
 * Controller to list the items in the Proveedor model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Cristo M. Estévez Hernández <cristom.estevez@gmail.com>
 */
class ListProveedor extends ExtendedController\ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'suppliers';
        $pagedata['icon'] = 'fa-users';
        $pagedata['menu'] = 'purchases';

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        /* Supplier */
        $this->addView('ListProveedor', 'Proveedor', 'suppliers', 'fa-users');
        $this->addSearchFields('ListProveedor', ['nombre', 'razonsocial', 'codproveedor', 'email']);

        $this->addOrderBy('ListProveedor', 'codproveedor', 'code');
        $this->addOrderBy('ListProveedor', 'nombre', 'name', 1);
        $this->addOrderBy('ListProveedor', 'fecha', 'date');

        $this->addFilterCheckbox('ListProveedor', 'debaja', 'suspended', 'debaja');

        $this->createViewAdresses();
    }

    private function createViewAdresses() : void 
    {
        $this->addView('ListDireccionProveedor', 'DireccionProveedor', 'addresses', 'fa-road');
        $this->addSearchFields('ListDireccionProveedor', ['codproveedor', 'descripcion', 'direccion', 'ciudad', 'provincia', 'codpostal']);
        $this->addOrderBy('ListDireccionProveedor', 'codproveedor', 'supplier');
        $this->addOrderBy('ListDireccionProveedor', 'descripcion', 'description');
        $this->addOrderBy('ListDireccionProveedor', 'codpostal', 'postalcode');

        $cities = $this->codeModel->all('dirproveedores', 'ciudad', 'ciudad');
        $this->addFilterSelect('ListDireccionProveedor', 'ciudad', 'city', 'ciudad', $cities);

        $provinces = $this->codeModel->all('dirproveedores', 'provincia', 'provincia');
        $this->addFilterSelect('ListDireccionProveedor', 'provincia', 'province', 'provincia', $provinces);

        $countries = $this->codeModel->all('paises', 'codpais', 'nombre');
        $this->addFilterSelect('ListDireccionProveedor', 'codpais', 'country', 'codpais', $countries);
    }
}
