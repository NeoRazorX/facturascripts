<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Lib\ExtendedController;

/**
 * Controller to list the items in the PedidoCliente model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ListPedidoCliente extends ExtendedController\ListController
{

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addView('\FacturaScripts\Dinamic\Model\PedidoCliente', 'ListPedidoCliente');
        $this->addSearchFields('ListPedidoCliente', ['codigo', 'numero2', 'observaciones']);

        $this->addFilterDatePicker('ListPedidoCliente', 'date', 'date', 'fecha');
        $this->addFilterNumber('ListPedidoCliente', 'total', 'total');
        $this->addFilterSelect('ListPedidoCliente', 'codalmacen', 'almacenes', 'codalmacen', 'nombre');
        $this->addFilterSelect('ListPedidoCliente', 'codserie', 'series', 'codserie', 'descripcion');
        $this->addFilterSelect('ListPedidoCliente', 'codpago', 'formaspago', 'codpago', 'descripcion');
        $this->addFilterAutocomplete('ListPedidoCliente', 'codcliente', 'clientes', 'codcliente', 'nombre');

        $this->addOrderBy('ListPedidoCliente', 'codigo', 'code');
        $this->addOrderBy('ListPedidoCliente', 'fecha', 'date', 2);
        $this->addOrderBy('ListPedidoCliente', 'total', 'amount');
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'orders';
        $pagedata['icon'] = 'fa-files-o';
        $pagedata['menu'] = 'sales';

        return $pagedata;
    }
}
