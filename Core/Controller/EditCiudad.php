<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * EditCiudad
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Frank Aguirre        <faguirre@soenac.com>
 */
class EditCiudad extends EditController
{
    public function getModelClassName(): string
    {
        return 'Ciudad';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'city';
        $data['icon'] = 'fa-solid fa-city';
        return $data;
    }

    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        $this->createViewsPOI();
    }

    protected function createViewsPOI(string $viewName = 'ListPuntoInteresCiudad'): void
    {
        $this->addListView($viewName, 'PuntoInteresCiudad', 'points-of-interest', 'fa-solid fa-location-dot')
            ->addOrderBy(['name'], 'name')
            ->addOrderBy(['idciudad'], 'city')
            ->addSearchFields(['name', 'alias'])
            ->addFilterAutocomplete('idciudad', 'city', 'idciudad', 'ciudades', 'idciudad', 'ciudad')
            ->disableColumn('city');
    }

    /**
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListPuntoInteresCiudad':
                $id_ciudad = $this->getViewModelValue($this->getMainViewName(), 'idciudad');
                $where = [new DataBaseWhere('idciudad', $id_ciudad)];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }
}
