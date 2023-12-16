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
        $data['icon'] = 'fas fa-globe-americas';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewCountries();
        $this->createViewsDivisas();
        $this->createViewProvinces();
        $this->createViewCities();
    }

    protected function createViewCities(string $viewName = 'ListCiudad'): void
    {
        $this->addView($viewName, 'Ciudad', 'cities', 'fas fa-city')
            ->addOrderBy(['ciudad'], 'name')
            ->addOrderBy(['idprovincia'], 'province')
            ->addSearchFields(['ciudad']);
    }

    protected function createViewCountries(string $viewName = 'ListPais'): void
    {
        $this->addView($viewName, 'Pais', 'countries', 'fas fa-globe-americas')
            ->addOrderBy(['codpais'], 'code')
            ->addOrderBy(['nombre'], 'name', 1)
            ->addOrderBy(['codiso'], 'codiso')
            ->addSearchFields(['nombre', 'codiso', 'codpais']);
    }

    protected function createViewsDivisas(string $viewName = 'ListDivisa'): void
    {
        $this->addView($viewName, 'Divisa', 'currency', 'fas fa-money-bill-alt')
            ->addOrderBy(['coddivisa'], 'code')
            ->addOrderBy(['descripcion'], 'description', 1)
            ->addOrderBy(['codiso'], 'codiso')
            ->addSearchFields(['descripcion', 'coddivisa']);
    }

    protected function createViewProvinces(string $viewName = 'ListProvincia'): void
    {
        $this->addView($viewName, 'Provincia', 'province', 'fas fa-map-signs')
            ->addOrderBy(['provincia'], 'name')
            ->addOrderBy(['codpais'], 'country')
            ->addSearchFields(['provincia', 'codisoprov']);
    }
}
