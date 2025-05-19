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
use FacturaScripts\Core\DataSrc\Paises;
use FacturaScripts\Core\DataSrc\Retenciones;
use FacturaScripts\Core\DataSrc\Series;
use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\CodeModel;

/**
 * Controller to list the items in the Cliente model
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Cristo M. Estévez Hernández   <cristom.estevez@gmail.com>
 */
class ListCliente extends ListController
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'customers';
        $data['icon'] = 'fa-solid fa-users';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewCustomers();
        if ($this->permissions->onlyOwnerData === false) {
            $this->createViewContacts();
            $this->createViewBankAccounts();
            $this->createViewGroups();
        }
    }

    protected function createViewBankAccounts(string $viewName = 'ListCuentaBancoCliente'): void
    {
        $this->addView($viewName, 'CuentaBancoCliente', 'bank-accounts', 'fa-solid fa-piggy-bank')
            ->addSearchFields(['codcuenta', 'descripcion', 'iban', 'swift'])
            ->addOrderBy(['codcuenta'], 'bank-mandate')
            ->addOrderBy(['descripcion'], 'description')
            ->addOrderBy(['iban'], 'iban')
            ->addOrderBy(['fmandato', 'codcuenta'], 'bank-mandate-date', 2);

        // desactivamos botones
        $this->tab($viewName)
            ->setSettings('btnDelete', false)
            ->setSettings('btnNew', false)
            ->setSettings('checkBoxes', false);
    }

    protected function createViewContacts(string $viewName = 'ListContacto'): void
    {
        $this->addView($viewName, 'Contacto', 'addresses-and-contacts', 'fa-solid fa-address-book')
            ->addOrderBy(['descripcion'], 'description')
            ->addOrderBy(['direccion'], 'address')
            ->addOrderBy(['nombre'], 'name')
            ->addOrderBy(['fechaalta'], 'creation-date', 2)
            ->addSearchFields([
                'apartado', 'apellidos', 'codpostal', 'descripcion', 'direccion', 'email', 'empresa',
                'nombre', 'observaciones', 'telefono1', 'telefono2'
            ]);

        // filtros
        $values = [
            [
                'label' => Tools::lang()->trans('customers'),
                'where' => [new DataBaseWhere('codcliente', null, 'IS NOT')]
            ],
            [
                'label' => Tools::lang()->trans('all'),
                'where' => []
            ]
        ];
        $this->addFilterSelectWhere($viewName, 'type', $values);

        $this->addFilterSelect($viewName, 'codpais', 'country', 'codpais', Paises::codeModel());

        $provinces = $this->codeModel->all('contactos', 'provincia', 'provincia');
        if (count($provinces) >= CodeModel::ALL_LIMIT) {
            $this->addFilterAutocomplete($viewName, 'provincia', 'province', 'provincia', 'contactos', 'provincia');
        } else {
            $this->addFilterSelect($viewName, 'provincia', 'province', 'provincia', $provinces);
        }

        $cities = $this->codeModel->all('contactos', 'ciudad', 'ciudad');
        if (count($cities) >= CodeModel::ALL_LIMIT) {
            $this->addFilterAutocomplete($viewName, 'ciudad', 'city', 'ciudad', 'contactos', 'ciudad');
        } else {
            $this->addFilterSelect($viewName, 'ciudad', 'city', 'ciudad', $cities);
        }

        $this->addFilterAutocomplete($viewName, 'codpostal', 'zip-code', 'codpostal', 'contactos', 'codpostal');

        $this->addFilterCheckbox($viewName, 'verificado', 'verified', 'verificado');
    }

    protected function createViewCustomers(string $viewName = 'ListCliente'): void
    {
        $this->addView($viewName, 'Cliente', 'customers', 'fa-solid fa-users')
            ->addOrderBy(['codcliente'], 'code')
            ->addOrderBy(['LOWER(nombre)'], 'name', 1)
            ->addOrderBy(['cifnif'], 'fiscal-number')
            ->addOrderBy(['fechaalta', 'codcliente'], 'creation-date')
            ->addOrderBy(['riesgoalcanzado'], 'current-risk')
            ->addSearchFields([
                'cifnif', 'codcliente', 'codsubcuenta', 'email', 'nombre', 'observaciones', 'razonsocial',
                'telefono1', 'telefono2'
            ]);

        // filtros
        $this->addFilterSelectWhere($viewName, 'status', [
            ['label' => Tools::lang()->trans('only-active'), 'where' => [new DataBaseWhere('debaja', false)]],
            ['label' => Tools::lang()->trans('only-suspended'), 'where' => [new DataBaseWhere('debaja', true)]],
            ['label' => Tools::lang()->trans('all'), 'where' => []]
        ]);
        $this->addFilterSelectWhere($viewName, 'type', [
            ['label' => Tools::lang()->trans('all'), 'where' => []],
            ['label' => Tools::lang()->trans('is-person'), 'where' => [new DataBaseWhere('personafisica', true)]],
            ['label' => Tools::lang()->trans('company'), 'where' => [new DataBaseWhere('personafisica', false)]]
        ]);

        $fiscalIds = $this->codeModel->all('clientes', 'tipoidfiscal', 'tipoidfiscal');
        $this->addFilterSelect($viewName, 'tipoidfiscal', 'fiscal-id', 'tipoidfiscal', $fiscalIds);

        $groupValues = $this->codeModel->all('gruposclientes', 'codgrupo', 'nombre');
        $this->addFilterSelect($viewName, 'codgrupo', 'group', 'codgrupo', $groupValues);

        $this->addFilterSelect($viewName, 'codserie', 'series', 'codserie', Series::codeModel());
        $this->addFilterSelect($viewName, 'codretencion', 'retentions', 'codretencion', Retenciones::codeModel());
        $this->addFilterSelect($viewName, 'codpago', 'payment-methods', 'codpago', FormasPago::codeModel());

        $vatRegimes = $this->codeModel->all('clientes', 'regimeniva', 'regimeniva');
        $this->addFilterSelect($viewName, 'regimeniva', 'vat-regime', 'regimeniva', $vatRegimes);

        $this->addFilterNumber($viewName, 'riesgoalcanzado', 'current-risk', 'riesgoalcanzado');
    }

    protected function createViewGroups(string $viewName = 'ListGrupoClientes'): void
    {
        $this->addView($viewName, 'GrupoClientes', 'groups', 'fa-solid fa-users-cog')
            ->addSearchFields(['nombre', 'codgrupo'])
            ->addOrderBy(['codgrupo'], 'code')
            ->addOrderBy(['nombre'], 'name', 1);
    }
}
