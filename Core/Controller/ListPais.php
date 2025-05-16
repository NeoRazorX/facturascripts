<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\ExtendedController\ListController;

/**
 * Controller to list the items in the Pais model
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class ListPais extends ListController
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'countries';
        $data['icon'] = 'fa-solid fa-globe-americas';
        return $data;
    }

    protected function createViews()
    {
        $this->createViewsCountries();
        $this->createViewsProvinces();
        $this->createViewsCities();
        $this->createViewsPOIs();
        $this->createViewsZipCodes();
        $this->createViewsDivisas();
    }

    protected function createViewsCities(string $viewName = 'ListCiudad'): void
    {
        $this->addView($viewName, 'Ciudad', 'cities', 'fa-solid fa-city')
            ->addOrderBy(['ciudad'], 'name')
            ->addOrderBy(['idprovincia'], 'province')
            ->addSearchFields(['ciudad', 'alias'])
            ->addFilterAutocomplete('idprovincia', 'province', 'idprovincia', 'provincias', 'idprovincia', 'provincia')
            ->setSettings('btnNew', false);
    }

    protected function createViewsCountries(string $viewName = 'ListPais'): void
    {
        $this->addView($viewName, 'Pais', 'countries', 'fa-solid fa-globe-americas')
            ->addOrderBy(['codpais'], 'code')
            ->addOrderBy(['nombre'], 'name', 1)
            ->addOrderBy(['codiso'], 'codiso')
            ->addSearchFields(['nombre', 'codiso', 'codpais', 'alias']);
    }

    protected function createViewsDivisas(string $viewName = 'ListDivisa'): void
    {
        $this->addView($viewName, 'Divisa', 'currency', 'fa-solid fa-money-bill-alt')
            ->addOrderBy(['coddivisa'], 'code')
            ->addOrderBy(['descripcion'], 'description', 1)
            ->addOrderBy(['codiso'], 'codiso')
            ->addSearchFields(['descripcion', 'coddivisa']);
    }

    protected function createViewsPOIs(string $viewName = 'ListPuntoInteresCiudad'): void
    {
        $this->addView($viewName, 'PuntoInteresCiudad', 'points-of-interest', 'fa-solid fa-location-dot')
            ->addOrderBy(['name'], 'name')
            ->addOrderBy(['idciudad'], 'city')
            ->addSearchFields(['name', 'alias'])
            ->addFilterAutocomplete('idciudad', 'city', 'idciudad', 'ciudades', 'idciudad', 'ciudad')
            ->setSettings('btnNew', false);
    }

    protected function createViewsProvinces(string $viewName = 'ListProvincia'): void
    {
        $this->addView($viewName, 'Provincia', 'provinces', 'fa-solid fa-map-signs')
            ->addOrderBy(['provincia'], 'name')
            ->addOrderBy(['codpais'], 'country')
            ->addSearchFields(['provincia', 'codisoprov', 'alias'])
            ->addFilterAutocomplete('codpais', 'country', 'codpais', 'paises', 'codpais', 'nombre')
            ->setSettings('btnNew', false);
    }

    protected function createViewsZipCodes(string $viewName = 'ListCodigoPostal'): void
    {
        $this->addView($viewName, 'CodigoPostal', 'zip-codes', 'fa-solid fa-map-pin')
            ->addOrderBy(['number'], 'number')
            ->addOrderBy(['codpais'], 'country')
            ->addOrderBy(['idprovincia'], 'province')
            ->addOrderBy(['idciudad'], 'city')
            ->addSearchFields(['number'])
            ->addFilterAutocomplete('codpais', 'country', 'codpais', 'paises', 'codpais', 'nombre')
            ->addFilterAutocomplete('idprovincia', 'province', 'idprovincia', 'provincias', 'idprovincia', 'provincia')
            ->addFilterAutocomplete('idciudad', 'city', 'idciudad', 'ciudades', 'idciudad', 'ciudad');
    }
}
