<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Controller to list the items in the Impuesto model
 *
 * @author Carlos García Gómez              <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal    <yopli2000@gmail.com>
 * @author Rafael San José Tovar            <rafael.sanjose@x-netdigital.com>
 */
class ListImpuesto extends ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'taxes';
        $data['icon'] = 'fas fa-plus-square';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewsTax();
        $this->createViewsRetention();
    }

    /**
     *
     * @param string $viewName
     */
    protected function createViewsRetention(string $viewName = 'ListRetencion')
    {
        $this->addView($viewName, 'Retencion', 'retentions', 'fas fa-plus-square');
        $this->addOrderBy($viewName, ['codretencion'], 'code');
        $this->addOrderBy($viewName, ['descripcion'], 'description');
        $this->addSearchFields($viewName, ['descripcion', 'codretencion']);
    }

    /**
     *
     * @param string $viewName
     */
    protected function createViewsTax(string $viewName = 'ListImpuesto')
    {
        $this->addView($viewName, 'Impuesto', 'taxes', 'fas fa-plus-square');
        $this->addOrderBy($viewName, ['codimpuesto'], 'code');
        $this->addOrderBy($viewName, ['descripcion'], 'description');
        $this->addSearchFields($viewName, ['descripcion', 'codimpuesto']);
    }
}
