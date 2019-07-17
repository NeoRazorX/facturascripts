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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Controller to edit a single item from the Pais model
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class EditPais extends EditController
{

    /**
     * Returns the model name.
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'Pais';
    }

    /**
     * Returns basic page attributes.
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'country';
        $data['icon'] = 'fas fa-globe-americas';
        return $data;
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createProvinceView($viewName = 'ListProvincia')
    {
        $this->addListView($viewName, 'Provincia', 'provinces');
        $this->views[$viewName]->addOrderBy(['provincia'], 'name', 1);
        $this->views[$viewName]->searchFields = ['provincia'];

        /// disable column
        $this->views[$viewName]->disableColumn('country');
    }

    /**
     * Create tabs or views.
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        $this->createProvinceView();
    }

    /**
     * 
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListProvincia':
                $codpais = $this->getViewModelValue($this->getMainViewName(), 'codpais');
                $where = [new DataBaseWhere('codpais', $codpais)];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
        }
    }
}
