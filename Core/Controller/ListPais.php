<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Lib\ExtendedController;

/**
 * Controller to list the items in the Pais model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ListPais extends ExtendedController\ListController
{

    /**
     * Load views
     */
    protected function createViews()
    {
        /// Countries
        $this->addView('Pais', 'ListPais', 'countries', 'fa-globe');
        $this->addSearchFields('ListPais', ['nombre', 'codiso', 'codpais']);

        $this->addFilterCheckbox('ListPais', 'validarprov', 'validate-states');
        $this->addOrderBy('ListPais', 'codpais', 'code');
        $this->addOrderBy('ListPais', 'nombre', 'name');
        $this->addOrderBy('ListPais', 'codiso', 'codiso');

        /// States
        $this->addView('Provincia', 'ListProvincia', 'province', 'fa-map-signs');
        $this->addSearchFields('ListProvincia', ['provincia', 'codisoprov']);

        $this->addOrderBy('ListProvincia', 'provincia', 'province');
        $this->addOrderBy('ListProvincia', 'codpais', 'alfa-code-3', 1);
        $this->addOrderBy('ListProvincia', 'codpostal2d', 'postalcode');
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'countries';
        $pagedata['icon'] = 'fa-globe';
        $pagedata['menu'] = 'admin';

        return $pagedata;
    }
}
