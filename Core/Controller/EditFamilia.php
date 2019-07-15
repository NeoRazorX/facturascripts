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
 * Controller to edit a single item from the Familia model
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Artex Trading sa             <jcuello@artextrading.com>
 * @author Fco. Antonio Moreno Pérez    <famphuelva@gmail.com>
 */
class EditFamilia extends EditController
{

    /**
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'Familia';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'warehouse';
        $data['title'] = 'family';
        $data['icon'] = 'fas fa-sitemap';
        return $data;
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createFamilyView($viewName = 'ListFamilia')
    {
        $this->addListView($viewName, 'Familia', 'families', 'fas fa-level-down-alt');
        $this->views[$viewName]->addOrderBy(['codfamilia'], 'code');

        /// disable column
        $this->views[$viewName]->disableColumn('parent');

        /// disable button
        $this->setSettings($viewName, 'btnDelete', false);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createProductView($viewName = 'ListProducto')
    {
        $this->addListView($viewName, 'Producto', 'products', 'fas fa-cubes');
        $this->views[$viewName]->addOrderBy(['referencia'], 'reference', 1);
        $this->views[$viewName]->addOrderBy(['precio'], 'price');
        $this->views[$viewName]->addOrderBy(['stock'], 'stock');
        $this->views[$viewName]->searchFields = ['referencia', 'descripcion'];

        /// disable column
        $this->views[$viewName]->disableColumn('family');
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        /// more tabs
        $this->createProductView();
        $this->createFamilyView();
    }

    /**
     * Load view data procedure
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $codfamilia = $this->getViewModelValue($this->getMainViewName(), 'codfamilia');
        switch ($viewName) {
            case 'ListProducto':
                $where = [new DataBaseWhere('codfamilia', $codfamilia)];
                $view->loadData('', $where);
                break;

            case 'ListFamilia':
                $where = [new DataBaseWhere('madre', $codfamilia)];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }
}
