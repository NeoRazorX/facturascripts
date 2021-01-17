<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\CodeModel;

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
        $this->createViewCustomers();
        $this->createViewContacts();
        $this->createViewBankAccounts();
        $this->createViewGroups();
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewBankAccounts(string $viewName = 'ListCuentaBancoCliente')
    {
        $this->addView($viewName, 'CuentaBancoCliente', 'bank-accounts', 'fas fa-piggy-bank');
        $this->addSearchFields($viewName, ['codcuenta', 'descripcion', 'iban', 'swift']);
        $this->addOrderBy($viewName, ['codcuenta'], 'bank-mandate');
        $this->addOrderBy($viewName, ['descripcion'], 'description');
        $this->addOrderBy($viewName, ['iban'], 'iban');
        $this->addOrderBy($viewName, ['fmandato', 'codcuenta'], 'bank-mandate-date', 2);

        /// disable buttons
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'checkBoxes', false);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewContacts(string $viewName = 'ListContacto')
    {
        $this->addView($viewName, 'Contacto', 'addresses-and-contacts', 'fas fa-address-book');
        $this->addSearchFields($viewName, ['nombre', 'apellidos', 'email', 'empresa', 'observaciones', 'telefono1', 'telefono2', 'lastip']);
        $this->addOrderBy($viewName, ['descripcion'], 'description');
        $this->addOrderBy($viewName, ['direccion'], 'address');
        $this->addOrderBy($viewName, ['nombre'], 'name');
        $this->addOrderBy($viewName, ['fechaalta'], 'creation-date', 2);

        /// filters
        $values = [
            [
                'label' => $this->toolBox()->i18n()->trans('customers'),
                'where' => [new DataBaseWhere('codcliente', null, 'IS NOT')]
            ]
        ];
        $this->addFilterSelectWhere($viewName, 'type', $values);

        $countries = $this->codeModel->all('paises', 'codpais', 'nombre');
        $this->addFilterSelect($viewName, 'codpais', 'country', 'codpais', $countries);

        $provinces = $this->codeModel->all('contactos', 'provincia', 'provincia');
        if (\count($provinces) >= CodeModel::ALL_LIMIT) {
            $this->addFilterAutocomplete($viewName, 'provincia', 'province', 'provincia', 'contactos', 'provincia');
        } else {
            $this->addFilterSelect($viewName, 'provincia', 'province', 'provincia', $provinces);
        }

        $cities = $this->codeModel->all('contactos', 'ciudad', 'ciudad');
        if (\count($cities) >= CodeModel::ALL_LIMIT) {
            $this->addFilterAutocomplete($viewName, 'ciudad', 'city', 'ciudad', 'contactos', 'ciudad');
        } else {
            $this->addFilterSelect($viewName, 'ciudad', 'city', 'ciudad', $cities);
        }

        $this->addFilterCheckbox($viewName, 'verificado', 'verified', 'verificado');
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewCustomers(string $viewName = 'ListCliente')
    {
        $this->addView($viewName, 'Cliente', 'customers', 'fas fa-users');
        $this->addSearchFields($viewName, ['cifnif', 'codcliente', 'email', 'nombre', 'observaciones', 'razonsocial', 'telefono1', 'telefono2']);
        $this->addOrderBy($viewName, ['codcliente'], 'code');
        $this->addOrderBy($viewName, ['nombre'], 'name', 1);
        $this->addOrderBy($viewName, ['cifnif'], 'fiscal-number');
        $this->addOrderBy($viewName, ['fechaalta', 'codcliente'], 'creation-date');
        $this->addOrderBy($viewName, ['riesgoalcanzado'], 'current-risk');

        /// filters
        $i18n = $this->toolBox()->i18n();
        $values = [
            ['label' => $i18n->trans('only-active'), 'where' => [new DataBaseWhere('debaja', false)]],
            ['label' => $i18n->trans('only-suspended'), 'where' => [new DataBaseWhere('debaja', true)]],
            ['label' => $i18n->trans('all'), 'where' => []]
        ];
        $this->addFilterSelectWhere($viewName, 'status', $values);

        $groupValues = $this->codeModel->all('gruposclientes', 'codgrupo', 'nombre');
        $this->addFilterSelect($viewName, 'codgrupo', 'group', 'codgrupo', $groupValues);

        $series = $this->codeModel->all('series', 'codserie', 'descripcion');
        $this->addFilterSelect($viewName, 'codserie', 'series', 'codserie', $series);

        $retentions = $this->codeModel->all('retenciones', 'codretencion', 'descripcion');
        $this->addFilterSelect($viewName, 'codretencion', 'retentions', 'codretencion', $retentions);

        $paymentMethods = $this->codeModel->all('formaspago', 'codpago', 'descripcion');
        $this->addFilterSelect($viewName, 'codpago', 'payment-methods', 'codpago', $paymentMethods);

        $vatRegimes = $this->codeModel->all('clientes', 'regimeniva', 'regimeniva');
        $this->addFilterSelect($viewName, 'regimeniva', 'vat-regime', 'regimeniva', $vatRegimes);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewGroups(string $viewName = 'ListGrupoClientes')
    {
        $this->addView($viewName, 'GrupoClientes', 'groups', 'fas fa-users-cog');
        $this->addSearchFields($viewName, ['nombre', 'codgrupo']);
        $this->addOrderBy($viewName, ['codgrupo'], 'code');
        $this->addOrderBy($viewName, ['nombre'], 'name', 1);
    }
}
