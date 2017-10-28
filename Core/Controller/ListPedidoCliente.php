<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\ExtendedController;

/**
 * Controlador para la lista de pedidos de cliente
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ListPedidoCliente extends ExtendedController\ListController
{
    /**
     * Devuelve los datos básicos de la página
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

    /**
     * Procedimiento encargado de insertar las vistas a visualizar
     */
    protected function createViews()
    {
        $this->addView('FacturaScripts\Core\Model\PedidoCliente', 'ListPedidoCliente');
        $this->addSearchFields('ListPedidoCliente', ['codigo', 'numero2', 'observaciones']);
        
        $this->addFilterSelect('ListPedidoCliente', 'codalmacen', 'almacenes', '', 'nombre');
        $this->addFilterSelect('ListPedidoCliente', 'codserie', 'series', '', 'descripcion');
        $this->addFilterSelect('ListPedidoCliente', 'codpago', 'formaspago', '', 'descripcion');
        
        $this->addFilterDatePicker('ListPedidoCliente', 'date1', 'date', 'fecha', '>=');
        $this->addFilterDatePicker('ListPedidoCliente', 'date2', 'date', 'fecha', '<=');

        $this->addOrderBy('ListPedidoCliente', 'codigo', 'code');
        $this->addOrderBy('ListPedidoCliente', 'fecha', 'date');
        $this->addOrderBy('ListPedidoCliente', 'total', 'amount');
    }
}
