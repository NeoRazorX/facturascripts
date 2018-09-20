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
 * Controller to list the items in the PedidoProveedor model
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author Raul Jimenez         <raul.jimenez@nazcanetworks.com>
 */
class ListPedidoProveedor extends ExtendedController\ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'orders';
        $pagedata['icon'] = 'fa-copy';
        $pagedata['menu'] = 'purchases';

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addView('ListPedidoProveedor', 'PedidoProveedor', 'orders', 'fa-copy');
        $this->addSearchFields('ListPedidoProveedor', ['codigo', 'numproveedor', 'observaciones']);
        $this->addOrderBy('ListPedidoProveedor', ['codigo'], 'code');
        $this->addOrderBy('ListPedidoProveedor', ['fecha'], 'date', 2);
        $this->addOrderBy('ListPedidoProveedor', ['total'], 'amount');

        $this->addFilterDatePicker('ListPedidoProveedor', 'fecha', 'date', 'fecha');
        $this->addFilterNumber('ListPedidoProveedor', 'total', 'total', 'total');

        $where = [new DataBaseWhere('tipodoc', 'PedidoProveedor')];
        $statusValues = $this->codeModel->all('estados_documentos', 'idestado', 'nombre', true, $where);
        $this->addFilterSelect('ListPedidoProveedor', 'idestado', 'state', 'idestado', $statusValues);

        $warehouseValues = $this->codeModel->all('almacenes', 'codalmacen', 'nombre');
        $this->addFilterSelect('ListPedidoProveedor', 'codalmacen', 'warehouse', 'codalmacen', $warehouseValues);

        $serieValues = $this->codeModel->all('series', 'codserie', 'descripcion');
        $this->addFilterSelect('ListPedidoProveedor', 'codserie', 'series', 'codserie', $serieValues);

        $paymentValues = $this->codeModel->all('formaspago', 'codpago', 'descripcion');
        $this->addFilterSelect('ListPedidoProveedor', 'codpago', 'payment-method', 'codpago', $paymentValues);

        $this->addFilterAutocomplete('ListPedidoProveedor', 'codproveedor', 'supplier', 'codproveedor', 'Proveedor');
        $this->addFilterCheckbox('ListPedidoProveedor', 'femail', 'email-not-sent', 'femail', 'IS', null);

        // Delivery notes lines
        $this->createViewLines();
    }

    protected function createViewLines()
    {
        $this->addView('ListLineaPedidoProveedor', 'LineaPedidoProveedor', 'lines', 'fa-list');
        $this->addSearchFields('ListLineaPedidoProveedor', ['referencia', 'descripcion']);
        $this->addOrderBy('ListLineaPedidoProveedor', ['referencia'], 'reference');
        $this->addOrderBy('ListLineaPedidoProveedor', ['cantidad'], 'quantity');
        $this->addOrderBy('ListLineaPedidoProveedor', ['descripcion'], 'description');
        $this->addOrderBy('ListLineaPedidoProveedor', ['pvptotal'], 'ammount');
        $this->addOrderBy('ListLineaPedidoProveedor', ['idpedido'], 'code', 2);

        $taxValues = $this->codeModel->all('impuestos', 'codimpuesto', 'descripcion');
        $this->addFilterSelect('ListLineaPedidoProveedor', 'codimpuesto', 'tax', 'codimpuesto', $taxValues);

        $this->addFilterNumber('ListLineaPedidoProveedor', 'cantidad', 'quantity', 'cantidad');
        $this->addFilterNumber('ListLineaPedidoProveedor', 'dtopor', 'discount', 'dtopor');
        $this->addFilterNumber('ListLineaPedidoProveedor', 'pvpunitario', 'pvp', 'pvpunitario');
        $this->addFilterNumber('ListLineaPedidoProveedor', 'pvptotal', 'ammount', 'pvptotal');

        /// disable megasearch for this view
        $this->setSettings('ListLineaPedidoProveedor', 'megasearch', false);
    }
}
