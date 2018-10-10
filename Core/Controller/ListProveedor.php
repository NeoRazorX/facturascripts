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
        $pagedata['icon'] = 'fas fa-users';
        $pagedata['menu'] = 'purchases';

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        /* Supplier */
        $this->addView('ListProveedor', 'Proveedor', 'suppliers', 'fas fa-users');
        $this->addSearchFields('ListProveedor', ['cifnif', 'codproveedor', 'email', 'nombre', 'observaciones', 'razonsocial', 'telefono1', 'telefono2']);
        $this->addOrderBy('ListProveedor', ['codproveedor'], 'code');
        $this->addOrderBy('ListProveedor', ['nombre'], 'name', 1);
        $this->addOrderBy('ListProveedor', ['fecha'], 'date');
        $this->addFilterCheckbox('ListProveedor', 'debaja', 'suspended', 'debaja');

        $this->createViewAdresses();
    }

    private function createViewAdresses()
    {
        $this->addView('ListContacto', 'Contacto', 'addresses-and-contacts', 'fas fa-address-book');
        $this->addSearchFields('ListContacto', ['nombre', 'apellidos', 'email']);
        $this->addOrderBy('ListContacto', ['email'], 'email');
        $this->addOrderBy('ListContacto', ['nombre'], 'name');
        $this->addOrderBy('ListContacto', ['empresa'], 'company');
        $this->addOrderBy('ListContacto', ['lastactivity'], 'last-activity', 2);

        $cargoValues = $this->codeModel->all('contactos', 'cargo', 'cargo');
        $this->addFilterSelect('ListContacto', 'cargo', 'position', 'cargo', $cargoValues);

        $counties = $this->codeModel->all('paises', 'codpais', 'nombre');
        $this->addFilterSelect('ListContacto', 'codpais', 'country', 'codpais', $counties);

        $provinces = $this->codeModel->all('contactos', 'provincia', 'provincia');
        $this->addFilterSelect('ListContacto', 'provincia', 'province', 'provincia', $provinces);

        $cities = $this->codeModel->all('contactos', 'ciudad', 'ciudad');
        $this->addFilterSelect('ListContacto', 'ciudad', 'city', 'ciudad', $cities);

        $this->addFilterCheckbox('ListContacto', 'verificado', 'verified', 'verificado');
        $this->addFilterCheckbox('ListContacto', 'admitemarketing', 'allow-marketing', 'admitemarketing');

        /// disable megasearch
        $this->setSettings('ListContacto', 'megasearch', false);
    }
}
