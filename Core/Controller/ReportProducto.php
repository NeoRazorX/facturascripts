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

use FacturaScripts\Core\DataSrc\Agentes;
use FacturaScripts\Core\DataSrc\Almacenes;
use FacturaScripts\Core\DataSrc\Divisas;
use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\DataSrc\Series;
use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Core\Lib\InvoiceOperation;
use FacturaScripts\Core\Tools;
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

    /**
     * Filtros comunes a todos los documentos
     */
    private function addCommonFilters(string $viewName, string $dateField): void
    {
        // periodo
        $this->addFilterPeriod($viewName, 'fecha', 'date', $dateField);

        // usuarios
        $users = $this->codeModel->all('users', 'nick', 'nick');
        if (count($users) > 1) {
            $this->addFilterSelect($viewName, 'nick', 'user', 'nick', $users);
        }

        // agente
        $agents = Agentes::codeModel();
        if (count($agents) > 1) {
            $this->addFilterSelect($viewName, 'codagente', 'agent', 'codagente', $agents);
        }

        // empresa
        $companies = Empresas::codeModel();
        if (count($companies) > 2) {
            $this->addFilterSelect($viewName, 'idempresa', 'company', 'idempresa', $companies);
        }

        // almacén
        $warehouses = Almacenes::codeModel();
        if (count($warehouses) > 2) {
            $this->addFilterSelect($viewName, 'codalmacen', 'warehouse', 'codalmacen', $warehouses);
        } else {
            $this->views[$viewName]->disableColumn('warehouse');
        }

        // serie
        $series = Series::codeModel();
        if (count($series) > 2) {
            $this->addFilterSelect($viewName, 'codserie', 'series', 'codserie', $series);
        }

        // operaciones
        $operations = [['code' => '', 'description' => '------']];
        foreach (InvoiceOperation::all() as $key => $value) {
            $operations[] = [
                'code' => $key,
                'description' => Tools::trans($value)
            ];
        }
        $this->addFilterSelect($viewName, 'operacion', 'operation', 'operacion', $operations);

        // divisas
        $currencies = Divisas::codeModel();
        if (count($currencies) > 2) {
            $this->addFilterSelect($viewName, 'coddivisa', 'currency', 'coddivisa', $currencies);
        }

        // fabricante
        $manufacturers = $this->codeModel->all('fabricantes', 'codfabricante', 'nombre');
        $this->addFilterSelect($viewName, 'codfabricante', 'manufacturer', 'codfabricante', $manufacturers);

        // familia
        $families = $this->codeModel->all('familias', 'codfamilia', 'descripcion');
        $this->addFilterSelect($viewName, 'codfamilia', 'family', 'codfamilia', $families);
    }

    /**
     * Handler de la creación de vistas
     */
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
        $this->addView($viewName, 'Join\AlbaranClienteProducto', 'customer-delivery-notes', 'fa-solid fa-shipping-fast')
            ->addSearchFields(['productos.descripcion', 'lineasalbaranescli.referencia'])
            ->addOrderBy(['cantidad'], 'quantity-sold', 2)
            ->addOrderBy(['avgbeneficio'], 'unit-profit')
            ->addOrderBy(['avgprecio'], 'unit-sale-price')
            ->addOrderBy(['coste'], 'cost-price')
            ->addOrderBy(['precio'], 'price')
            ->addOrderBy(['stockfis'], 'stock');

        // filtros
        $this->addCommonFilters($viewName, 'albaranescli.fecha');

        // cliente
        $this->addFilterAutocomplete($viewName, 'codcliente', 'customer', 'codcliente', 'Cliente', 'codcliente', 'nombre');

        // dirección y envío
        $this->addFilterAutocomplete($viewName, 'idcontactofact', 'billing-address', 'idcontactofact', 'contactos', 'idcontacto', 'direccion');
        $this->addFilterautocomplete($viewName, 'idcontactoenv', 'shipping-address', 'idcontactoenv', 'contactos', 'idcontacto', 'direccion');

        // desactivamos columnas
        $this->disableButtons($viewName);
    }

    protected function createViewsCustomerInvoices(string $viewName = 'FacturaClienteProducto'): void
    {
        $this->addView($viewName, 'Join\FacturaClienteProducto', 'customer-invoices', 'fa-solid fa-shopping-cart')
            ->addSearchFields(['productos.descripcion', 'lineasfacturascli.referencia'])
            ->addOrderBy(['cantidad'], 'quantity-sold', 2)
            ->addOrderBy(['avgbeneficio'], 'unit-profit')
            ->addOrderBy(['avgprecio'], 'unit-sale-price')
            ->addOrderBy(['coste'], 'cost-price')
            ->addOrderBy(['precio'], 'price')
            ->addOrderBy(['stockfis'], 'stock');

        // filtros
        $this->addCommonFilters($viewName, 'facturascli.fecha');

        // cliente
        $this->addFilterAutocomplete($viewName, 'codcliente', 'customer', 'codcliente', 'Cliente', 'codcliente', 'nombre');

        // dirección y envio
        $this->addFilterAutocomplete($viewName, 'idcontactofact', 'billing-address', 'idcontactofact', 'contactos', 'idcontacto', 'direccion');
        $this->addFilterautocomplete($viewName, 'idcontactoenv', 'shipping-address', 'idcontactoenv', 'contactos', 'idcontacto', 'direccion');

        // desactivamos columnas
        $this->disableButtons($viewName);
    }

    protected function createViewsSupplierDeliveryNotes(string $viewName = 'FacturaProveedorProducto-alb'): void
    {
        $this->addView($viewName, 'Join\AlbaranProveedorProducto', 'supplier-delivery-notes', 'fa-solid fa-copy')
            ->addSearchFields(['productos.descripcion', 'lineasalbaranesprov.referencia'])
            ->addOrderBy(['cantidad'], 'purchased-quantity', 2)
            ->addOrderBy(['avgcoste'], 'unit-purchase-price')
            ->addOrderBy(['coste'], 'cost-price')
            ->addOrderBy(['precio'], 'price')
            ->addOrderBy(['stockfis'], 'stock');

        // filtros
        $this->addCommonFilters($viewName, 'albaranesprov.fecha');

        // proveedor
        $this->addFilterAutocomplete($viewName, 'codproveedor', 'supplier', 'codproveedor', 'Proveedor', 'codproveedor', 'nombre');

        // desactivamos columnas
        $this->disableButtons($viewName);
    }

    protected function createViewsSupplierInvoices(string $viewName = 'FacturaProveedorProducto'): void
    {
        $this->addView($viewName, 'Join\FacturaProveedorProducto', 'supplier-invoices', 'fa-solid fa-copy')
            ->addSearchFields(['productos.descripcion', 'lineasfacturasprov.referencia'])
            ->addOrderBy(['cantidad'], 'quantity', 2);

        // filtros
        $this->addCommonFilters($viewName, 'facturasprov.fecha');

        // proveedor
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
