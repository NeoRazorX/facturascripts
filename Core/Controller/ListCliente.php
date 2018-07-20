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

    private function createViewContacts()
    {
        $this->addView('ListContacto', 'Contacto', 'contacts', 'fa-address-book');
        $this->addSearchFields('ListContacto', ['nombre', 'apellidos', 'email']);
        $this->addOrderBy('ListContacto', ['email'], 'email');
        $this->addOrderBy('ListContacto', ['nombre'], 'name');
        $this->addOrderBy('ListContacto', ['empresa'], 'company');
        $this->addOrderBy('ListContacto', ['lastactivity'], 'last-activity', 2);

        $this->addFilterAutocomplete('ListContacto', 'codcliente', 'customer', 'codcliente', 'clientes', 'codcliente', 'nombre');

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
    }

    private function createViewCustomers($valuesGroup)
    {
        $this->addView('ListCliente', 'Cliente', 'customers', 'fa-users');
        $this->addSearchFields('ListCliente', ['nombre', 'razonsocial', 'codcliente', 'email']);
        $this->addOrderBy('ListCliente', ['codcliente'], 'code');
        $this->addOrderBy('ListCliente', ['nombre'], 'name', 1);
        $this->addOrderBy('ListCliente', ['fechaalta', 'codcliente'], 'date');

        $this->addFilterSelect('ListCliente', 'codgrupo', 'group', 'codgrupo', $valuesGroup);
        $this->addFilterCheckbox('ListCliente', 'debaja', 'suspended', 'debaja');
    }

    private function createViewGroups($valuesGroup)
    {
        $this->addView('ListGrupoClientes', 'GrupoClientes', 'groups', 'fa-folder-open');
        $this->addSearchFields('ListGrupoClientes', ['nombre', 'codgrupo']);
        $this->addOrderBy('ListGrupoClientes', ['codgrupo'], 'code');
        $this->addOrderBy('ListGrupoClientes', ['nombre'], 'name', 1);
        $this->addFilterSelect('ListGrupoClientes', 'parent', 'parent', 'parent', $valuesGroup);
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $valuesGroup = $this->codeModel->all('gruposclientes', 'codgrupo', 'nombre');

        $this->createViewCustomers($valuesGroup);
        $this->createViewContacts();
        $this->createViewGroups($valuesGroup);
    }

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
}
