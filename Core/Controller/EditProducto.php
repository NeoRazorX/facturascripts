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
 * Controller to edit a single item from the EditProducto model
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Artex Trading sa             <jcuello@artextrading.com>
 * @author Fco. Antonio Moreno Pérez    <famphuelva@gmail.com>
 */
class EditProducto extends EditController
{

    /**
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'Producto';
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
        $data['title'] = 'product';
        $data['icon'] = 'fas fa-cube';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->addEditListView('EditVariante', 'Variante', 'variants', 'fas fa-project-diagram');
        $this->addEditListView('EditStock', 'Stock', 'stock', 'fas fa-dolly');
    }

    /**
     * 
     * @return bool
     */
    protected function insertAction()
    {
        if (parent::insertAction()) {
            return true;
        }

        if ($this->active === 'EditProducto') {
            $this->views['EditProducto']->disableColumn('reference', false, 'false');
        }

        return false;
    }

    /**
     * 
     * @param string $viewName
     */
    protected function loadCustomAttributeWidgets(string $viewName)
    {
        $values = $this->codeModel->all('AtributoValor', 'id', '');
        foreach (['attribute-value-1', 'attribute-value-2'] as $colName) {
            $column = $this->views[$viewName]->columnForName($colName);
            if ($column) {
                $column->widget->setValuesFromCodeModel($values);
            }
        }
    }

    /**
     * 
     * @param string $viewName
     */
    protected function loadCustomStockWidget(string $viewName)
    {
        $references = [];
        $idproducto = $this->getViewModelValue('EditProducto', 'idproducto');
        $where = [new DataBaseWhere('idproducto', $idproducto)];
        foreach ($this->codeModel->all('variantes', 'referencia', 'referencia', false, $where) as $code) {
            $references[] = ['value' => $code->code, 'title' => $code->description];
        }

        $column = $this->views[$viewName]->columnForName('reference');
        if ($column) {
            $column->widget->setValuesFromArray($references, false);
        }
    }

    /**
     * Load view data procedure
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $idproducto = $this->getViewModelValue('EditProducto', 'idproducto');
        $where = [new DataBaseWhere('idproducto', $idproducto)];

        switch ($viewName) {
            case 'EditProducto':
                parent::loadData($viewName, $view);
                if ($view->model->nostock) {
                    $this->setSettings('EditStock', 'active', false);
                } else {
                    $this->loadCustomStockWidget('EditStock');
                }
                break;

            case 'EditVariante':
                $view->loadData('', $where, ['idvariante' => 'DESC']);
                $this->loadCustomAttributeWidgets($viewName);
                break;

            case 'EditStock':
                $view->loadData('', $where, ['idstock' => 'DESC']);
                break;
        }
    }
}
