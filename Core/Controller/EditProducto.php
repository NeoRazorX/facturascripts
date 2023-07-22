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
use FacturaScripts\Core\DataSrc\Almacenes;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Core\Lib\ExtendedController\ProductImagesTrait;
use FacturaScripts\Core\Lib\ProductType;
use FacturaScripts\Dinamic\Lib\RegimenIVA;
use FacturaScripts\Dinamic\Model\Atributo;

/**
 * Controller to edit a single item from the EditProducto model
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Fco. Antonio Moreno Pérez     <famphuelva@gmail.com>
 */
class EditProducto extends EditController
{
    use ProductImagesTrait;

    public function getModelClassName(): string
    {
        return 'Producto';
    }

    public function getPageData(): array
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
        $this->createViewsVariants();
        $this->createViewsProductImages();
        $this->createViewsStock();
        $this->createViewsSuppliers();
    }

    protected function createViewsStock(string $viewName = 'EditStock')
    {
        $this->addEditListView($viewName, 'Stock', 'stock', 'fas fa-dolly');

        // si solamente hay un almacén, ocultamos la columna
        if (count(Almacenes::all()) <= 1) {
            $this->views[$viewName]->disableColumn('warehouse');
        }
    }

    protected function createViewsSuppliers(string $viewName = 'EditProductoProveedor')
    {
        $this->addEditListView($viewName, 'ProductoProveedor', 'suppliers', 'fas fa-users');
    }

    protected function createViewsVariants(string $viewName = 'EditVariante')
    {
        $this->addEditListView($viewName, 'Variante', 'variants', 'fas fa-project-diagram');

        $attribute = new Atributo();
        $attCount = $attribute->count();
        if ($attCount < 4) {
            $this->views[$viewName]->disableColumn('attribute-value-4');
        }
        if ($attCount < 3) {
            $this->views[$viewName]->disableColumn('attribute-value-3');
        }
        if ($attCount < 2) {
            $this->views[$viewName]->disableColumn('attribute-value-2');
        }
        if ($attCount < 1) {
            $this->views[$viewName]->disableColumn('attribute-value-1');
        }
    }

    /**
     * Run the actions that alter data before reading it.
     *
     * @param string $action
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'add-image':
                return $this->addImageAction();

            case 'delete-image':
                return $this->deleteImageAction();
        }

        return parent::execPreviousAction($action);
    }

    protected function insertAction(): bool
    {
        if (parent::insertAction()) {
            return true;
        }

        if ($this->active === 'EditProducto') {
            $this->views['EditProducto']->disableColumn('reference', false, 'false');
        }

        return false;
    }

    protected function loadCustomAttributeWidgets(string $viewName): void
    {
        $values = $this->codeModel->all('AtributoValor', 'id', '');
        foreach (['attribute-value-1', 'attribute-value-2', 'attribute-value-3', 'attribute-value-4'] as $colName) {
            $column = $this->views[$viewName]->columnForName($colName);
            if ($column && $column->widget->getType() === 'select') {
                $column->widget->setValuesFromCodeModel($values);
            }
        }
    }

    protected function loadCustomReferenceWidget(string $viewName): void
    {
        $references = [];
        $id = $this->getViewModelValue('EditProducto', 'idproducto');
        $where = [new DataBaseWhere('idproducto', $id)];
        $values = $this->codeModel->all('variantes', 'referencia', 'referencia', false, $where);
        foreach ($values as $code) {
            $references[] = ['value' => $code->code, 'title' => $code->description];
        }

        $column = $this->views[$viewName]->columnForName('reference');
        if ($column && $column->widget->getType() === 'select') {
            $column->widget->setValuesFromArray($references, false);
        }
    }

    /**
     * Load view data procedure
     *
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $id = $this->getViewModelValue('EditProducto', 'idproducto');
        $where = [new DataBaseWhere('idproducto', $id)];

        switch ($viewName) {
            case $this->getMainViewName():
                parent::loadData($viewName, $view);
                $this->loadTypes($viewName);
                $this->loadExceptionVat($viewName);
                if (empty($view->model->primaryColumnValue())) {
                    $view->disableColumn('stock');
                }
                if ($view->model->nostock) {
                    $this->setSettings('EditStock', 'active', false);
                }
                $this->loadCustomReferenceWidget('EditProductoProveedor');
                $this->loadCustomReferenceWidget('EditStock');
                if (false === empty($view->model->primaryColumnValue())) {
                    $this->addButton($viewName, [
                        'action' => 'CopyModel?model=' . $this->getModelClassName() . '&code=' . $view->model->primaryColumnValue(),
                        'icon' => 'fas fa-cut',
                        'label' => 'copy',
                        'type' => 'link'
                    ]);
                }
                break;

            case 'EditProductoImagen':
                $where = [new DataBaseWhere('idproducto', $id)];
                $orderBy = ['referencia' => 'ASC', 'id' => 'ASC'];
                $view->loadData('', $where, $orderBy);
                break;

            case 'EditVariante':
                $view->loadData('', $where, ['idvariante' => 'DESC']);
                $this->loadCustomAttributeWidgets($viewName);
                break;

            case 'EditStock':
                $view->loadData('', $where, ['idstock' => 'DESC']);
                break;

            case 'EditProductoProveedor':
                $view->loadData('', $where, ['id' => 'DESC']);
                break;
        }
    }

    protected function loadTypes(string $viewName): void
    {
        $column = $this->views[$viewName]->columnForName('type');
        if ($column && $column->widget->getType() === 'select') {
            $column->widget->setValuesFromArrayKeys(ProductType::all(), true, true);
        }
    }

    protected function loadExceptionVat(string $viewName): void
    {
        $column = $this->views[$viewName]->columnForName('vat-exception');
        if ($column && $column->widget->getType() === 'select') {
            $column->widget->setValuesFromArrayKeys(RegimenIVA::allExceptions(), true, true);
        }
    }
}
