<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\DataSrc\Almacenes;
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
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'reports';
        $data['title'] = 'products';
        $data['icon'] = 'fa-solid fa-cubes';
        return $data;
    }

    private function addCommonFilters(string $viewName, string $dateField): void
    {
        $this->addFilterPeriod($viewName, 'fecha', 'date', $dateField);

        $warehouses = Almacenes::codeModel();
        if (count($warehouses) > 2) {
            $this->addFilterSelect($viewName, 'codalmacen', 'warehouse', 'codalmacen', $warehouses);
        } else {
            $this->views[$viewName]->disableColumn('warehouse');
        }

        $manufacturers = $this->codeModel->all('fabricantes', 'codfabricante', 'nombre');
        $this->addFilterSelect($viewName, 'codfabricante', 'manufacturer', 'codfabricante', $manufacturers);

        $families = $this->codeModel->all('familias', 'codfamilia', 'descripcion');
        $this->addFilterSelect($viewName, 'codfamilia', 'family', 'codfamilia', $families);
    }

    protected function createViews()
    {
        // needed dependencies
        new LineaFacturaCliente();
        new LineaFacturaProveedor();

        $this->createViewsSupplierDeliveryNotes();
        $this->createViewsSupplierInvoices();
        $this->createViewsCustomerDeliveryNotes();
        $this->createViewsCustomerInvoices();
    }

    protected function createViewsCustomerDeliveryNotes(string $viewName = 'FacturaClienteProducto-alb'): void
    {
        $this->addView($viewName, 'Join\AlbaranClienteProducto', 'customer-delivery-notes', 'fa-solid fa-shipping-fast');
        $this->addOrderBy($viewName, ['cantidad'], 'quantity-sold', 2);
        $this->addOrderBy($viewName, ['avgbeneficio'], 'unit-profit');
        $this->addOrderBy($viewName, ['avgprecio'], 'unit-sale-price');
        $this->addOrderBy($viewName, ['coste'], 'cost-price');
        $this->addOrderBy($viewName, ['precio'], 'price');
        $this->addOrderBy($viewName, ['stockfis'], 'stock');
        $this->addSearchFields($viewName, ['productos.descripcion', 'lineasalbaranescli.referencia']);

        // filtros
        $this->addCommonFilters($viewName, 'albaranescli.fecha');
        $this->addFilterAutocomplete($viewName, 'codcliente', 'customer', 'codcliente', 'Cliente', 'codcliente', 'nombre');

        // desactivamos columnas
        $this->disableButtons($viewName);
    }

    protected function createViewsCustomerInvoices(string $viewName = 'FacturaClienteProducto'): void
    {
        $this->addView($viewName, 'Join\FacturaClienteProducto', 'customer-invoices', 'fa-solid fa-shopping-cart');
        $this->addOrderBy($viewName, ['cantidad'], 'quantity-sold', 2);
        $this->addOrderBy($viewName, ['avgbeneficio'], 'unit-profit');
        $this->addOrderBy($viewName, ['avgprecio'], 'unit-sale-price');
        $this->addOrderBy($viewName, ['coste'], 'cost-price');
        $this->addOrderBy($viewName, ['precio'], 'price');
        $this->addOrderBy($viewName, ['stockfis'], 'stock');
        $this->addSearchFields($viewName, ['productos.descripcion', 'lineasfacturascli.referencia']);

        // filtros
        $this->addCommonFilters($viewName, 'facturascli.fecha');
        $this->addFilterAutocomplete($viewName, 'codcliente', 'customer', 'codcliente', 'Cliente', 'codcliente', 'nombre');

        // desactivamos columnas
        $this->disableButtons($viewName);
    }

    protected function createViewsSupplierDeliveryNotes(string $viewName = 'FacturaProveedorProducto-alb'): void
    {
        $this->addView($viewName, 'Join\AlbaranProveedorProducto', 'supplier-delivery-notes', 'fa-solid fa-copy');
        $this->addOrderBy($viewName, ['cantidad'], 'purchased-quantity', 2);
        $this->addOrderBy($viewName, ['avgcoste'], 'unit-purchase-price');
        $this->addOrderBy($viewName, ['coste'], 'cost-price');
        $this->addOrderBy($viewName, ['precio'], 'price');
        $this->addOrderBy($viewName, ['stockfis'], 'stock');
        $this->addSearchFields($viewName, ['productos.descripcion', 'lineasalbaranesprov.referencia']);

        // filtros
        $this->addCommonFilters($viewName, 'albaranesprov.fecha');
        $this->addFilterAutocomplete($viewName, 'codproveedor', 'supplier', 'codproveedor', 'Proveedor', 'codproveedor', 'nombre');

        // desactivamos columnas
        $this->disableButtons($viewName);
    }

    protected function createViewsSupplierInvoices(string $viewName = 'FacturaProveedorProducto'): void
    {
        $this->addView($viewName, 'Join\FacturaProveedorProducto', 'supplier-invoices', 'fa-solid fa-copy');
        $this->addOrderBy($viewName, ['cantidad'], 'quantity', 2);
        $this->addSearchFields($viewName, ['productos.descripcion', 'lineasfacturasprov.referencia']);

        // filtros
        $this->addCommonFilters($viewName, 'facturasprov.fecha');
        $this->addFilterAutocomplete($viewName, 'codproveedor', 'supplier', 'codproveedor', 'Proveedor', 'codproveedor', 'nombre');

        // desactivamos columnas
        $this->disableButtons($viewName);
    }

    private function disableButtons(string $viewName): void
    {
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'checkBoxes', false);
        $this->setSettings($viewName, 'clickable', false);
    }
}
