<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2024 Carlos García Gómez <carlos@facturascripts.com>
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
 * Controlador para la edición de un registro del modelo de Provincia
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 */
class EditProvincia extends EditController
{
    public function getModelClassName(): string
    {
        return 'Provincia';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'province';
        $data['icon'] = 'fa-solid fa-map-signs';
        return $data;
    }

    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        $this->createViewsCities();
    }

    protected function createViewsCities(string $viewName = 'ListCiudad'): void
    {
        $this->addListView($viewName, 'Ciudad', 'cities', 'fa-solid fa-city')
            ->addOrderBy(['ciudad'], 'name')
            ->addOrderBy(['idprovincia'], 'province')
            ->addSearchFields(['ciudad', 'alias'])
            ->disableColumn('province');
    }

    /**
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListCiudad':
                $id_provincia = $this->getViewModelValue($this->getMainViewName(), 'idprovincia');
                $where = [new DataBaseWhere('idprovincia', $id_provincia)];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }
}
