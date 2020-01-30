<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2019 Carlos García Gómez <carlos@facturascripts.com>
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

    /**
     * Returns the model name.
     *
     * @return string
     */
    public function getModelClassName()
    {
        return 'Provincia';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'province';
        $data['icon'] = 'fas fa-map-signs';
        return $data;
    }

    /**
     * Create tabs or views.
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        $this->createCityView();
    }

    /**
     *
     * @param string $viewName
     */
    protected function createCityView($viewName = 'ListCiudad')
    {
        $this->addListView($viewName, 'Ciudad', 'cities');
        $this->views[$viewName]->addOrderBy(['ciudad'], 'name', 1);
        $this->views[$viewName]->searchFields = ['ciudad'];

        /// disable column
        $this->views[$viewName]->disableColumn('province');
    }

    /**
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListCiudad':
                $idprovincia = $this->getViewModelValue($this->getMainViewName(), 'idprovincia');
                $where = [new DataBaseWhere('idprovincia', $idprovincia)];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }
}
