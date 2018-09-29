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
 * Controller to list the items in the PedidoCliente model
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author Raul Jimenez         <raul.jimenez@nazcanetworks.com>
 */
class ListPedidoCliente extends ExtendedController\ListController
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
        $pagedata['menu'] = 'sales';

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addView('ListPedidoCliente', 'PedidoCliente', 'orders', 'fa-copy');
        $this->addSearchFields('ListPedidoCliente', ['codigo', 'numero2', 'observaciones']);
        $this->addOrderBy('ListPedidoCliente', ['codigo'], 'code');
        $this->addOrderBy('ListPedidoCliente', ['fecha'], 'date', 2);
        $this->addOrderBy('ListPedidoCliente', ['total'], 'amount');

        $this->addFilterDatePicker('ListPedidoCliente', 'from-date', 'from-date', 'fecha', '>=');
        $this->addFilterDatePicker('ListPedidoCliente', 'until-date', 'until-date', 'fecha', '<=');
        $this->addFilterNumber('ListPedidoCliente', 'min-total', 'total', 'total', '>=');
        $this->addFilterNumber('ListPedidoCliente', 'max-total', 'total', 'total', '<=');

        $where = [new DataBaseWhere('tipodoc', 'PedidoCliente')];
        $statusValues = $this->codeModel->all('estados_documentos', 'idestado', 'nombre', true, $where);
        $this->addFilterSelect('ListPedidoCliente', 'idestado', 'state', 'idestado', $statusValues);

        $warehouseValues = $this->codeModel->all('almacenes', 'codalmacen', 'nombre');
        $this->addFilterSelect('ListPedidoCliente', 'codalmacen', 'warehouse', 'codalmacen', $warehouseValues);

        $serieValues = $this->codeModel->all('series', 'codserie', 'descripcion');
        $this->addFilterSelect('ListPedidoCliente', 'codserie', 'series', 'codserie', $serieValues);

        $paymentValues = $this->codeModel->all('formaspago', 'codpago', 'descripcion');
        $this->addFilterSelect('ListPedidoCliente', 'codpago', 'payment-method', 'codpago', $paymentValues);

        $this->addFilterAutocomplete('ListPedidoCliente', 'codcliente', 'customer', 'codcliente', 'Cliente');
        $this->addFilterCheckbox('ListPedidoCliente', 'femail', 'email-not-sent', 'femail', 'IS', null);

        // Delivery notes lines
        $this->createViewLines();
    }

    protected function createViewLines()
    {
        $this->addView('ListLineaPedidoCliente', 'LineaPedidoCliente', 'lines', 'fa-list');
        $this->addSearchFields('ListLineaPedidoCliente', ['referencia', 'descripcion']);
        $this->addOrderBy('ListLineaPedidoCliente', ['referencia'], 'reference');
        $this->addOrderBy('ListLineaPedidoCliente', ['cantidad'], 'quantity');
        $this->addOrderBy('ListLineaPedidoCliente', ['descripcion'], 'description');
        $this->addOrderBy('ListLineaPedidoCliente', ['pvptotal'], 'ammount');
        $this->addOrderBy('ListLineaPedidoCliente', ['idpedido'], 'code', 2);

        $taxValues = $this->codeModel->all('impuestos', 'codimpuesto', 'descripcion');
        $this->addFilterSelect('ListLineaPedidoCliente', 'codimpuesto', 'tax', 'codimpuesto', $taxValues);

        $this->addFilterNumber('ListLineaPedidoCliente', 'cantidad', 'quantity', 'cantidad');
        $this->addFilterNumber('ListLineaPedidoCliente', 'dtopor', 'discount', 'dtopor');
        $this->addFilterNumber('ListLineaPedidoCliente', 'pvpunitario', 'pvp', 'pvpunitario');
        $this->addFilterNumber('ListLineaPedidoCliente', 'pvptotal', 'ammount', 'pvptotal');

        /// disable megasearch for this view
        $this->setSettings('ListLineaPedidoCliente', 'megasearch', false);
    }
}
