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
use FacturaScripts\Core\Lib\ExtendedController\ListController;

/**
 * Controller to list the items in the Cliente model
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Artex Trading sa             <jcuello@artextrading.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class ListCliente extends ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'customers';
        $data['icon'] = 'fas fa-users';
        return $data;
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
     * 
     * @param string $name
     */
    protected function createViewContacts($name = 'ListContacto')
    {
        $this->addView($name, 'Contacto', 'addresses-and-contacts', 'fas fa-address-book');
        $this->addSearchFields($name, ['nombre', 'apellidos', 'email']);
        $this->addOrderBy($name, ['email'], 'email');
        $this->addOrderBy($name, ['nombre'], 'name');
        $this->addOrderBy($name, ['empresa'], 'company');
        $this->addOrderBy($name, ['level'], 'level');
        $this->addOrderBy($name, ['puntos'], 'points');
        $this->addOrderBy($name, ['lastactivity'], 'last-activity', 2);

        $cargoValues = $this->codeModel->all('contactos', 'cargo', 'cargo');
        $this->addFilterSelect($name, 'cargo', 'position', 'cargo', $cargoValues);

        $counties = $this->codeModel->all('paises', 'codpais', 'nombre');
        $this->addFilterSelect($name, 'codpais', 'country', 'codpais', $counties);

        $provinces = $this->codeModel->all('contactos', 'provincia', 'provincia');
        $this->addFilterSelect($name, 'provincia', 'province', 'provincia', $provinces);

        $cities = $this->codeModel->all('contactos', 'ciudad', 'ciudad');
        $this->addFilterSelect($name, 'ciudad', 'city', 'ciudad', $cities);

        $this->addFilterCheckbox($name, 'verificado', 'verified', 'verificado');
        $this->addFilterCheckbox($name, 'admitemarketing', 'allow-marketing', 'admitemarketing');
    }

    /**
     * 
     * @param array  $valuesGroup
     * @param string $name
     */
    private function createViewCustomers(array $valuesGroup, $name = 'ListCliente')
    {
        $this->addView($name, 'Cliente', 'customers', 'fas fa-users');
        $this->addSearchFields($name, ['cifnif', 'codcliente', 'email', 'nombre', 'observaciones', 'razonsocial', 'telefono1', 'telefono2']);
        $this->addOrderBy($name, ['codcliente'], 'code');
        $this->addOrderBy($name, ['nombre'], 'name', 1);
        $this->addOrderBy($name, ['fechaalta', 'codcliente'], 'date');

        $this->addFilterSelect($name, 'codgrupo', 'group', 'codgrupo', $valuesGroup);

        $values = [
            ['label' => $this->i18n->trans('only-active'), 'where' => [new DataBaseWhere('debaja', false)]],
            ['label' => $this->i18n->trans('only-suspended'), 'where' => [new DataBaseWhere('debaja', true)]],
            ['label' => $this->i18n->trans('all'), 'where' => []]
        ];
        $this->addFilterSelectWhere($name, 'status', $values);
        
        $retencions = $this->codeModel->all('retenciones', 'codretencion', 'descripcion');
        $this->addFilterSelect($name, 'codretencion', 'retentions', 'codretencion', $retencions);
        
        $series = $this->codeModel->all('series', 'codserie', 'descripcion');
        $this->addFilterSelect($name, 'codserie', 'series', 'codserie', $series);
        
        $formaspago = $this->codeModel->all('formaspago', 'codpago', 'descripcion');
        $this->addFilterSelect($name, 'codpago', 'payment-methods', 'codpago', $formaspago);
    }

    /**
     * 
     * @param array  $valuesGroup
     * @param string $name
     */
    private function createViewGroups(array $valuesGroup, $name = 'ListGrupoClientes')
    {
        $this->addView($name, 'GrupoClientes', 'groups', 'fas fa-folder-open');
        $this->addSearchFields($name, ['nombre', 'codgrupo']);
        $this->addOrderBy($name, ['codgrupo'], 'code');
        $this->addOrderBy($name, ['nombre'], 'name', 1);

        $this->addFilterSelect($name, 'parent', 'parent', 'parent', $valuesGroup);
    }
}
