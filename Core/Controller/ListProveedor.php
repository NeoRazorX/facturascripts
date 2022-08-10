<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Controller to list the items in the Proveedor model
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class ListProveedor extends ListController
{

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'purchases';
        $data['title'] = 'suppliers';
        $data['icon'] = 'fas fa-users';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewSuppliers();
        $this->createViewAddresses();
    }

    protected function createViewAddresses(string $viewName = 'ListContacto')
    {
        $this->addView($viewName, 'Contacto', 'addresses-and-contacts', 'fas fa-address-book');
        $this->addSearchFields($viewName, [
            'apellidos', 'codpostal', 'descripcion', 'direccion', 'email', 'empresa',
            'nombre', 'observaciones', 'telefono1', 'telefono2'
        ]);
        $this->addOrderBy($viewName, ['descripcion'], 'description');
        $this->addOrderBy($viewName, ['direccion'], 'address');
        $this->addOrderBy($viewName, ['nombre'], 'name');
        $this->addOrderBy($viewName, ['fechaalta'], 'creation-date', 2);

        // filters
        $values = [
            [
                'label' => $this->toolBox()->i18n()->trans('suppliers'),
                'where' => [new DataBaseWhere('codproveedor', null, 'IS NOT')]
            ]
        ];
        $this->addFilterSelectWhere($viewName, 'type', $values);

        $cargoValues = $this->codeModel->all('contactos', 'cargo', 'cargo');
        $this->addFilterSelect($viewName, 'cargo', 'position', 'cargo', $cargoValues);

        $this->addFilterSelect($viewName, 'codpais', 'country', 'codpais', Paises::codeModel());

        $provinces = $this->codeModel->all('contactos', 'provincia', 'provincia');
        $this->addFilterSelect($viewName, 'provincia', 'province', 'provincia', $provinces);

        $cities = $this->codeModel->all('contactos', 'ciudad', 'ciudad');
        $this->addFilterSelect($viewName, 'ciudad', 'city', 'ciudad', $cities);

        $this->addFilterCheckbox($viewName, 'verificado', 'verified', 'verificado');
        $this->addFilterCheckbox($viewName, 'admitemarketing', 'allow-marketing', 'admitemarketing');

        // disable mega-search
        $this->setSettings($viewName, 'megasearch', false);
    }

    protected function createViewSuppliers(string $viewName = 'ListProveedor')
    {
        $this->addView($viewName, 'Proveedor', 'suppliers', 'fas fa-users');
        $this->addSearchFields($viewName, ['cifnif', 'codproveedor', 'email', 'nombre', 'observaciones', 'razonsocial', 'telefono1', 'telefono2']);
        $this->addOrderBy($viewName, ['codproveedor'], 'code');
        $this->addOrderBy($viewName, ['cifnif'], 'fiscal-number');
        $this->addOrderBy($viewName, ['LOWER(nombre)'], 'name', 1);
        $this->addOrderBy($viewName, ['fechaalta'], 'creation-date');

        // filters
        $i18n = $this->toolBox()->i18n();
        $this->addFilterSelectWhere($viewName, 'status', [
            ['label' => $i18n->trans('only-active'), 'where' => [new DataBaseWhere('debaja', false)]],
            ['label' => $i18n->trans('only-suspended'), 'where' => [new DataBaseWhere('debaja', true)]],
            ['label' => $i18n->trans('all'), 'where' => []]
        ]);
        $this->addFilterSelectWhere($viewName, 'type', [
            ['label' => $i18n->trans('all'), 'where' => []],
            ['label' => $i18n->trans('is-creditor'), 'where' => [new DataBaseWhere('acreedor', true)]],
            ['label' => $i18n->trans('supplier'), 'where' => [new DataBaseWhere('acreedor', false)]],
        ]);

        $this->addFilterSelect($viewName, 'codserie', 'series', 'codserie', Series::codeModel());
        $this->addFilterSelect($viewName, 'codretencion', 'retentions', 'codretencion', Retenciones::codeModel());
        $this->addFilterSelect($viewName, 'codpago', 'payment-methods', 'codpago', FormasPago::codeModel());

        $vatRegimes = $this->codeModel->all('proveedores', 'regimeniva', 'regimeniva');
        $this->addFilterSelect($viewName, 'regimeniva', 'vat-regime', 'regimeniva', $vatRegimes);
    }
}
