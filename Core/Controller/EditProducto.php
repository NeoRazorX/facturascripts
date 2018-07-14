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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController;

/**
 * Controller to edit a single item from the EditProducto model
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 * @author Fco. Antonio Moreno Pérez <famphuelva@gmail.com>
 */
class EditProducto extends ExtendedController\PanelController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'product';
        $pagedata['icon'] = 'fa-cube';
        $pagedata['menu'] = 'warehouse';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addEditView('EditProducto', 'Producto', 'product', 'fa-cube');
        $this->addEditListView('EditVariante', 'Variante', 'variants', 'fa-code-fork');
        $this->addEditListView('EditStock', 'Stock', 'stock', 'fa-tasks');

        $this->loadCustomStockWidget();
    }

    /**
     * 
     */
    protected function loadCustomStockWidget()
    {
        $references = [];
        $where = [new DataBaseWhere('idproducto', $this->request->get('code'))];
        foreach ($this->codeModel->all('variantes', 'referencia', 'referencia', false, $where) as $code) {
            $references[] = ['value' => $code->code, 'title' => $code->description];
        }

        $columnReference = $this->views['EditStock']->columnForName('reference');
        $columnReference->widget->setValuesFromArray($references, false);
    }

    /**
     * Load view data procedure
     *
     * @param string                      $viewName
     * @param ExtendedController\EditView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'EditProducto':
                $code = $this->request->get('code');
                $view->loadData($code);
                if ($view->model->nostock) {
                    unset($this->views['EditStock']);
                }
                break;

            case 'EditVariante':
            case 'EditStock':
                $idproducto = $this->getViewModelValue('EditProducto', 'idproducto');
                $where = [new DataBaseWhere('idproducto', $idproducto)];
                $view->loadData('', $where, ['referencia' => 'ASC'], 0, 0);
                break;
        }
    }
}
