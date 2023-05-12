<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\Atributo;

/**
 * Controller to edit a single item from the EditProducto model
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Fco. Antonio Moreno Pérez    <famphuelva@gmail.com>
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

		if ($this->user->can('ListAlbaranProveedor')) {
			$this->addListView('ListLineaAlbaranProveedor', 'LineaAlbaranProveedor', 'supplier-delivery-notes', 'fas fa-cubes');
			$this->commonOptions('ListLineaAlbaranProveedor');
        }

		if ($this->user->can('ListFacturaProveedor')) {
            $this->addListView('ListLineaFacturaProveedor', 'LineaFacturaProveedor', 'supplier-invoices', 'fas fa-cubes');
			$this->commonOptions('ListLineaFacturaProveedor');
        }
		
		if ($this->user->can('ListAlbaranCliente')) {
            $this->addListView('ListLineaAlbaranCliente', 'LineaAlbaranCliente', 'customer-delivery-notes', 'fas fa-cubes');
			$this->commonOptions('ListLineaAlbaranCliente');
        }

		if ($this->user->can('ListFacturaCliente')) {
            $this->addListView('ListLineaFacturaCliente', 'LineaFacturaCliente', 'customer-invoices', 'fas fa-cubes');
			$this->commonOptions('ListLineaFacturaCliente');
        }
    }

	protected function commonOptions(string $viewName)
	{
		// sort options
        $this->views[$viewName]->addOrderBy(['referencia'], 'reference', 2);
        $this->views[$viewName]->addOrderBy(['cantidad'], 'quantity');
        $this->views[$viewName]->addOrderBy(['pvptotal'], 'amount');

        // search columns
        $this->views[$viewName]->addSearchFields(['referencia', 'descripcion']);

        // disable buttons
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'checkBoxes', false);
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

    /**
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

    protected function loadCustomAttributeWidgets(string $viewName)
    {
        $values = $this->codeModel->all('AtributoValor', 'id', '');
        foreach (['attribute-value-1', 'attribute-value-2', 'attribute-value-3', 'attribute-value-4'] as $colName) {
            $column = $this->views[$viewName]->columnForName($colName);
            if ($column && $column->widget->getType() === 'select') {
                $column->widget->setValuesFromCodeModel($values);
            }
        }
    }

    protected function loadCustomReferenceWidget(string $viewName)
    {
        $references = [];
        $idproducto = $this->getViewModelValue('EditProducto', 'idproducto');
        $where = [new DataBaseWhere('idproducto', $idproducto)];
        foreach ($this->codeModel->all('variantes', 'referencia', 'referencia', false, $where) as $code) {
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
        $idproducto = $this->getViewModelValue('EditProducto', 'idproducto');
        $where = [new DataBaseWhere('idproducto', $idproducto)];

        switch ($viewName) {
            case $this->getMainViewName():
                parent::loadData($viewName, $view);
                if (empty($view->model->primaryColumnValue())) {
                    $view->disableColumn('stock');
                }
                $this->loadCustomReferenceWidget('EditProductoProveedor');
                if ($view->model->nostock) {
                    $this->setSettings('EditStock', 'active', false);
                    break;
                }
                $this->loadCustomReferenceWidget('EditStock');
                break;

            case 'EditProductoImagen':
                $where = [new DataBaseWhere('idproducto', $idproducto)];
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
				
			case 'ListLineaAlbaranProveedor':
				$inSQL = 'SELECT idalbaran FROM albaranesprov WHERE idproducto = ' . $this->dataBase->var2str($idproducto);
				$where = [new DataBaseWhere('idalbaran', $inSQL, 'IN')];
                $view->loadData('', $where);
                break;
				
			case 'ListLineaFacturaProveedor':
                $inSQL = 'SELECT idfactura FROM facturasprov WHERE idproducto = ' . $this->dataBase->var2str($idproducto);
                $where = [new DataBaseWhere('idfactura', $inSQL, 'IN')];
                $view->loadData('', $where);
                break;
				
			case 'ListLineaAlbaranCliente':
				$inSQL = 'SELECT idalbaran FROM albaranescli WHERE idproducto = ' . $this->dataBase->var2str($idproducto);
				$where = [new DataBaseWhere('idalbaran', $inSQL, 'IN')];
                $view->loadData('', $where);
                break;
			
			case 'ListLineaFacturaCliente':
                $inSQL = 'SELECT idfactura FROM facturascli WHERE idproducto = ' . $this->dataBase->var2str($idproducto);
                $where = [new DataBaseWhere('idfactura', $inSQL, 'IN')];
                $view->loadData('', $where);
                break;
			
        }
    }
}
