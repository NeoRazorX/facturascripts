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

    protected function createViewCities(string $viewName = 'ListCiudad')
    {
        $this->addView($viewName, 'Ciudad', 'cities', 'fas fa-city');
        $this->addOrderBy($viewName, ['ciudad'], 'name');
        $this->addOrderBy($viewName, ['idprovincia'], 'province');
        $this->addSearchFields($viewName, ['ciudad']);
    }

    protected function createViewCountries(string $viewName = 'ListPais')
    {
        $this->addView($viewName, 'Pais', 'countries', 'fas fa-globe-americas');
        $this->addOrderBy($viewName, ['codpais'], 'code');
        $this->addOrderBy($viewName, ['nombre'], 'name', 1);
        $this->addOrderBy($viewName, ['codiso'], 'codiso');
        $this->addSearchFields($viewName, ['nombre', 'codiso', 'codpais']);
    }

    protected function createViewsDivisas(string $viewName = 'ListDivisa')
    {
        $this->addView($viewName, 'Divisa', 'currency', 'fas fa-money-bill-alt');
        $this->addOrderBy($viewName, ['coddivisa'], 'code');
        $this->addOrderBy($viewName, ['descripcion'], 'description', 1);
        $this->addOrderBy($viewName, ['codiso'], 'codiso');
        $this->addSearchFields($viewName, ['descripcion', 'coddivisa']);
    }

    protected function createViewProvinces(string $viewName = 'ListProvincia')
    {
        $this->addView($viewName, 'Provincia', 'province', 'fas fa-map-signs');
        $this->addOrderBy($viewName, ['provincia'], 'name');
        $this->addOrderBy($viewName, ['codpais'], 'country');
        $this->addSearchFields($viewName, ['provincia', 'codisoprov']);
    }
}
