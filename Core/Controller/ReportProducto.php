<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Dinamic\Model\LineaFacturaCliente;
use FacturaScripts\Dinamic\Model\LineaFacturaProveedor;

/**
 * Description of ReportProducto
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ReportProducto extends ListController
{

    /**
     * 
     * @return array
     */
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'reports';
        $data['title'] = 'products';
        $data['icon'] = 'fas fa-cubes';
        return $data;
    }

    protected function createViews()
    {
        /// needed dependencies
        new LineaFacturaCliente();
        new LineaFacturaProveedor();

        $this->createViewsSupplierDeliveryNotes();
        $this->createViewsSupplierInvoices();
        $this->createViewsCustomerDeliveryNotes();
        $this->createViewsCustomerInvoices();
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewsCustomerDeliveryNotes(string $viewName = 'FacturaClienteProducto-alb')
    {
        $this->addView($viewName, 'Join\AlbaranClienteProducto', 'customer-delivery-notes', 'fas fa-shipping-fast');
        $this->addOrderBy($viewName, ['cantidad'], 'quantity-sold', 2);
        $this->addOrderBy($viewName, ['avgbeneficio'], 'unit-profit');
        $this->addOrderBy($viewName, ['avgprecio'], 'unit-sale-price');
        $this->addOrderBy($viewName, ['coste'], 'cost-price');
        $this->addOrderBy($viewName, ['precio'], 'price');
        $this->addOrderBy($viewName, ['stockfis'], 'stock');
        $this->addSearchFields($viewName, ['productos.descripcion', 'lineasalbaranescli.referencia']);

        $this->addFilterPeriod($viewName, 'fecha', 'date', 'albaranescli.fecha');
        $this->addCommonFilters($viewName);
        $this->disableButtons($viewName);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewsCustomerInvoices(string $viewName = 'FacturaClienteProducto')
    {
        $this->addView($viewName, 'Join\FacturaClienteProducto', 'customer-invoices', 'fas fa-shopping-cart');
        $this->addOrderBy($viewName, ['cantidad'], 'quantity-sold', 2);
        $this->addOrderBy($viewName, ['avgbeneficio'], 'unit-profit');
        $this->addOrderBy($viewName, ['avgprecio'], 'unit-sale-price');
        $this->addOrderBy($viewName, ['coste'], 'cost-price');
        $this->addOrderBy($viewName, ['precio'], 'price');
        $this->addOrderBy($viewName, ['stockfis'], 'stock');
        $this->addSearchFields($viewName, ['productos.descripcion', 'lineasfacturascli.referencia']);

        $this->addFilterPeriod($viewName, 'fecha', 'date', 'facturascli.fecha');
        $this->addCommonFilters($viewName);
        $this->disableButtons($viewName);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewsSupplierDeliveryNotes(string $viewName = 'FacturaProveedorProducto-alb')
    {
        $this->addView($viewName, 'Join\AlbaranProveedorProducto', 'supplier-delivery-notes', 'fas fa-copy');
        $this->addOrderBy($viewName, ['cantidad'], 'purchased-quantity', 2);
        $this->addOrderBy($viewName, ['avgcoste'], 'unit-purchase-price');
        $this->addOrderBy($viewName, ['coste'], 'cost-price');
        $this->addOrderBy($viewName, ['precio'], 'price');
        $this->addOrderBy($viewName, ['stockfis'], 'stock');
        $this->addSearchFields($viewName, ['productos.descripcion', 'lineasalbaranesprov.referencia']);

        $this->addFilterPeriod($viewName, 'fecha', 'date', 'albaranesprov.fecha');
        $this->addCommonFilters($viewName);
        $this->disableButtons($viewName);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewsSupplierInvoices(string $viewName = 'FacturaProveedorProducto')
    {
        $this->addView($viewName, 'Join\FacturaProveedorProducto', 'supplier-invoices', 'fas fa-copy');
        $this->addOrderBy($viewName, ['cantidad'], 'quantity', 2);
        $this->addSearchFields($viewName, ['productos.descripcion', 'lineasfacturasprov.referencia']);

        $this->addFilterPeriod($viewName, 'fecha', 'date', 'facturasprov.fecha');
        $this->addCommonFilters($viewName);
        $this->disableButtons($viewName);
    }

    /**
     * 
     * @param string $viewName
     */
    private function addCommonFilters(string $viewName)
    {
        $warehouses = $this->codeModel->all('almacenes', 'codalmacen', 'nombre');
        if (\count($warehouses) > 2) {
            $this->addFilterSelect($viewName, 'codalmacen', 'warehouse', 'codalmacen', $warehouses);
        } else {
            $this->views[$viewName]->disableColumn('warehouse');
        }

        $manufacturers = $this->codeModel->all('fabricantes', 'codfabricante', 'nombre');
        $this->addFilterSelect($viewName, 'codfabricante', 'manufacturer', 'codfabricante', $manufacturers);

        $families = $this->codeModel->all('familias', 'codfamilia', 'descripcion');
        $this->addFilterSelect($viewName, 'codfamilia', 'family', 'codfamilia', $families);
    }

    /**
     * 
     * @param string $viewName
     */
    private function disableButtons(string $viewName)
    {
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'checkBoxes', false);
        $this->setSettings($viewName, 'clickable', false);
    }
}
