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
 * Controller to list the items in the Impuesto model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 * @author Rafael San José Tovar <rafael.sanjose@x-netdigital.com>
 */
class ListImpuesto extends ExtendedController\ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['menu'] = 'accounting';
        $pagedata['submenu'] = 'taxes';
        $pagedata['title'] = 'taxes';
        $pagedata['icon'] = 'fas fa-plus-square';

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        /// Taxes
        $this->addView('ListImpuesto', 'Impuesto', 'taxes', 'fas fa-plus-square');
        $this->addSearchFields('ListImpuesto', ['descripcion', 'codimpuesto']);

        $this->addOrderBy('ListImpuesto', ['codimpuesto'], 'code');
        $this->addOrderBy('ListImpuesto', ['descripcion'], 'description');

        /// Withholdings
        $this->addView('ListRetencion', 'Retencion', 'retentions', 'fas fa-plus-square');
        $this->addSearchFields('ListRetencion', ['descripcion', 'codretencion']);

        $this->addOrderBy('ListRetencion', ['codretencion'], 'code');
        $this->addOrderBy('ListRetencion', ['descripcion'], 'description');

        /// Tax areas
        $this->addView('ListImpuestoZona', 'ImpuestoZona', 'tax-area', 'fas fa-dollar-sign');
        $this->addSearchFields('ListImpuestoZona', ['codpais']);

        $this->addOrderBy('ListImpuestoZona', ['codimpuesto'], 'tax');
        $this->addOrderBy('ListImpuestoZona', ['codpais'], 'country');
        $this->addOrderBy('ListImpuestoZona', ['codisopro'], 'province');
        $this->addOrderBy('ListImpuestoZona', ['codimpuestosel'], 'applied-tax');
    }
}
