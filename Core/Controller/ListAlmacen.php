<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 *  Controller to list the items in the Almacen model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ListAlmacen extends ExtendedController\ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'warehouses';
        $pagedata['icon'] = 'fas fa-building';
        $pagedata['menu'] = 'warehouse';

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        /// warehouse
        $this->addView('ListAlmacen', 'Almacen', 'warehouses', 'fas fa-building');
        $this->addSearchFields('ListAlmacen', ['nombre', 'codalmacen']);
        $this->addOrderBy('ListAlmacen', ['codalmacen'], 'code');
        $this->addOrderBy('ListAlmacen', ['nombre'], 'name');

        $selectValues = $this->codeModel->all('empresas', 'idempresa', 'nombre');
        $this->addFilterSelect('ListAlmacen', 'idempresa', 'company', 'idempresa', $selectValues);

        /// transferences
        $this->addView('ListTransferenciaStock', 'TransferenciaStock', 'stock-transfers', 'fas fa-exchange-alt');
        $this->addSearchFields('ListTransferenciaStock', ['observaciones']);
        $this->addOrderBy('ListTransferenciaStock', ['codalmacenorigen'], 'origin-warehouse');
        $this->addOrderBy('ListTransferenciaStock', ['codalmacendestino'], 'destination-warehouse');
        $this->addOrderBy('ListTransferenciaStock', ['fecha'], 'date');
        $this->addOrderBy('ListTransferenciaStock', ['usuario'], 'user');

        $this->addFilterDatePicker('ListTransferenciaStock', 'fecha', 'date', 'fecha');
        $this->addFilterAutocomplete('ListTransferenciaStock', 'usuario', 'user', 'usuario', 'users', 'nick', 'nick');
    }
}
