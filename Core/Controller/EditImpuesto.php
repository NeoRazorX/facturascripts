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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Controller to edit a single item from the Impuesto model
 *
 * @author Carlos García Gómez              <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal    <yopli2000@gmail.com>
 * @author Francesc Pineda Segarra          <francesc.pineda.segarra@gmail.com>
 */
class EditImpuesto extends EditController
{

    /**
     * Returns the model name.
     *
     * @return string
     */
    public function getModelClassName()
    {
        return 'Impuesto';
    }

    /**
     * Returns basic page attributes.
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'tax';
        $data['icon'] = 'fas fa-plus-square';
        return $data;
    }

    /**
     * Create the view to display.
     */
    protected function createViews()
    {
        parent::createViews();
        $this->createViewsZones();
        $this->createViewsProducts();
        $this->setTabsPosition('bottom');
    }

    /**
     *
     * @param string $viewName
     */
    protected function createViewsProducts(string $viewName = 'ListProducto')
    {
        $this->addListView($viewName, 'Producto', 'products', 'fas fa-cubes');
        $this->views[$viewName]->addOrderBy(['referencia'], 'reference', 1);
        $this->views[$viewName]->addOrderBy(['precio'], 'price');
        $this->views[$viewName]->addOrderBy(['stockfis'], 'stock');
        $this->views[$viewName]->addSearchFields(['referencia', 'descripcion', 'observaciones']);

        /// disable buttons
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'btnDelete', false);
    }

    /**
     *
     * @param string $viewName
     */
    protected function createViewsZones(string $viewName = 'EditImpuestoZona')
    {
        $this->addEditListView($viewName, 'ImpuestoZona', 'exceptions', 'fas fa-globe-americas');
        $this->views[$viewName]->disableColumn('tax');
        $this->views[$viewName]->setInLine(true);
    }

    /**
     * Loads the data to display.
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'EditImpuestoZona':
            case 'ListProducto':
                $codimpuesto = $this->getViewModelValue('EditImpuesto', 'codimpuesto');
                $where = [new DataBaseWhere('codimpuesto', $codimpuesto)];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
        }
    }
}
