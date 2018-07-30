<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * Controller to list the items in the CabeceraTransferenciaStock model
 *
 * @author Cristo M. Estévez Hernández <cristom.estevez@gmail.com>
 */
class ListTransferenciaStock extends ExtendedController\ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'transfers-stock';
        $pagedata['icon'] = 'fa-copy';
        $pagedata['menu'] = 'warehouse';

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addView('ListTransferenciaStock', 'TransferenciaStock', 'transfers-stock');
        $this->addSearchFields('ListTransferenciaStock', ['codalmacenorigen', 'codalmacendestino', 'fecha', 'usuario']);
        
        $this->addOrderBy('ListTransferenciaStock', ['codalmacenorigen'], 'warehouse-origin');
        $this->addOrderBy('ListTransferenciaStock', ['codalmacendestino'], 'warehouse-destination');
        $this->addOrderBy('ListTransferenciaStock', ['fecha'], 'date');
        $this->addOrderBy('ListTransferenciaStock', ['usuario'], 'user');

        $this->addFilterDatePicker('ListTransferenciaStock', 'fecha', 'date', 'fecha');
        $this->addFilterAutocomplete('ListTransferenciaStock', 'usuario', 'user', 'usuario', 'users', 'nick', 'nick');
    }
}
