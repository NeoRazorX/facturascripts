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

use FacturaScripts\Core\Lib\ExtendedController\ListController;

/**
 * Controller to list the items in the AgenciaTransporte model
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class ListAgenciaTransporte extends ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'warehouse';
        $data['title'] = 'carriers';
        $data['icon'] = 'fas fa-truck';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addView('ListAgenciaTransporte', 'AgenciaTransporte', 'carriers', 'fas fa-truck');
        $this->addSearchFields('ListAgenciaTransporte', ['nombre', 'web', 'codtrans']);
        $this->addOrderBy('ListAgenciaTransporte', ['codtrans'], 'code');
        $this->addOrderBy('ListAgenciaTransporte', ['nombre'], 'name');

        $this->addFilterCheckbox('ListAgenciaTransporte', 'activo', 'active', 'activo');
    }
}
