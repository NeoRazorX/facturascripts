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

use FacturaScripts\Core\Lib\ExtendedController;

/**
 * Controller to list the items in the Pais model
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class ListPais extends ExtendedController\ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
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
        /// Countries
        $this->addView('ListPais', 'Pais', 'countries', 'fas fa-globe-americas');
        $this->addOrderBy('ListPais', ['codpais'], 'code');
        $this->addOrderBy('ListPais', ['nombre'], 'name');
        $this->addOrderBy('ListPais', ['codiso'], 'codiso');
        $this->addSearchFields('ListPais', ['nombre', 'codiso', 'codpais']);

        /// States
        $this->addView('ListProvincia', 'Provincia', 'province', 'fas fa-map-signs');
        $this->addOrderBy('ListProvincia', ['provincia'], 'province');
        $this->addOrderBy('ListProvincia', ['codpais'], 'alfa-code-3', 1);
        $this->addOrderBy('ListProvincia', ['codpostal2d'], 'postalcode');
        $this->addSearchFields('ListProvincia', ['provincia', 'codisoprov']);
    }
}
