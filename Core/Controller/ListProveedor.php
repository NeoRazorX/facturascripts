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
        $data['icon'] = 'fa-solid fa-users';
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

    protected function createViewAddresses(string $viewName = 'ListContacto'): void
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
                'label' => Tools::lang()->trans('suppliers'),
                'where' => [new DataBaseWhere('codproveedor', null, 'IS NOT')]
            ],
            [
                'label' => Tools::lang()->trans('all'),
                'where' => []
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

        $this->addFilterAutocomplete($viewName, 'codpostal', 'zip-code', 'codpostal', 'contactos', 'codpostal');

        $this->addFilterCheckbox($viewName, 'verificado', 'verified', 'verificado');

        // desactivamos el mega-search
        $this->setSettings($viewName, 'megasearch', false);
    }

    protected function createViewSuppliers(string $viewName = 'ListProveedor'): void
    {
        $this->addView($viewName, 'Proveedor', 'suppliers', 'fa-solid fa-users')
            ->addOrderBy(['codproveedor'], 'code')
            ->addOrderBy(['cifnif'], 'fiscal-number')
            ->addOrderBy(['LOWER(nombre)'], 'name', 1)
            ->addOrderBy(['fechaalta'], 'creation-date')
            ->addSearchFields([
                'cifnif', 'codproveedor', 'codsubcuenta', 'email', 'nombre', 'observaciones', 'razonsocial',
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
            ['label' => Tools::lang()->trans('is-creditor'), 'where' => [new DataBaseWhere('acreedor', true)]],
            ['label' => Tools::lang()->trans('supplier'), 'where' => [new DataBaseWhere('acreedor', false)]],
        ]);

        $fiscalIds = $this->codeModel->all('proveedores', 'tipoidfiscal', 'tipoidfiscal');
        $this->addFilterSelect($viewName, 'tipoidfiscal', 'fiscal-id', 'tipoidfiscal', $fiscalIds);

        $this->addFilterSelect($viewName, 'codserie', 'series', 'codserie', Series::codeModel());
        $this->addFilterSelect($viewName, 'codretencion', 'retentions', 'codretencion', Retenciones::codeModel());
        $this->addFilterSelect($viewName, 'codpago', 'payment-methods', 'codpago', FormasPago::codeModel());

        $vatRegimes = $this->codeModel->all('proveedores', 'regimeniva', 'regimeniva');
        $this->addFilterSelect($viewName, 'regimeniva', 'vat-regime', 'regimeniva', $vatRegimes);
    }
}
