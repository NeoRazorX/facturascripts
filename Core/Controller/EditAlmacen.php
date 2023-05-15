<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Controller to edit a single item from the Almacen model
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Francesc Pineda Segarra       <francesc.pineda.segarra@gmail.com>
 */
class EditAlmacen extends EditController
{
    public function getModelClassName(): string
    {
        return 'Almacen';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'warehouse';
        $data['title'] = 'warehouse';
        $data['icon'] = 'fas fa-warehouse';
        return $data;
    }

    protected function createStockView(string $viewName = 'ListStock')
    {
        $this->addListView($viewName, 'Join\StockProducto', 'stock', 'fas fa-dolly');
        $this->views[$viewName]->addOrderBy(['stocks.referencia'], 'reference');
        $this->views[$viewName]->addOrderBy(['stocks.cantidad'], 'quantity');
        $this->views[$viewName]->addOrderBy(['stocks.disponible'], 'available');
        $this->views[$viewName]->addOrderBy(['stocks.reservada'], 'reserved');
        $this->views[$viewName]->addOrderBy(['stocks.pterecibir'], 'pending-reception');
        $this->views[$viewName]->addOrderBy(['productos.descripcion', 'stocks.referencia'], 'product');
        $this->views[$viewName]->addSearchFields(['stocks.referencia', 'productos.descripcion']);

        // filtros
        $manufacturers = $this->codeModel->all('fabricantes', 'codfabricante', 'nombre');
        $this->views[$viewName]->addFilterSelect('manufacturer', 'manufacturer', 'manufacturer', $manufacturers);

        $families = $this->codeModel->all('familias', 'codfamilia', 'descripcion');
        $this->views[$viewName]->addFilterSelect('family', 'family', 'family', $families);

        // desactivamos la columna de almacén
        $this->views[$viewName]->disableColumn('warehouse');

        // desactivamos botones
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
    }

    /**
     * Add tabs or views.
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        // desactivamos la columna de empresa, si solo hay una
        if ($this->empresa->count() < 2) {
            $this->views[$this->getMainViewName()]->disableColumn('company');
        }

        $this->createStockView();
    }

    /**
     * Load data view procedure
     *
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListStock':
                $code = $this->getViewModelValue($this->getMainViewName(), 'codalmacen');
                $where = [new DataBaseWhere('codalmacen', $code)];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }
}
