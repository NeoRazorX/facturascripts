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
 * Controlador para la lista de albaranes de cliente
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ListAlbaranCliente extends ExtendedController\ListController
{

    /**
     * Devuelve los datos básicos de la página
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'delivery-notes';
        $pagedata['icon'] = 'fa-files-o';
        $pagedata['menu'] = 'sales';

        return $pagedata;
    }

    /**
     * Procedimiento encargado de insertar las vistas a visualizar
     */
    protected function createViews()
    {
        $this->addView('FacturaScripts\Core\Model\AlbaranCliente', 'ListAlbaranCliente');
        $this->addSearchFields('ListAlbaranCliente', ['codigo', 'numero2', 'nombrecliente', 'CAST(total as VARCHAR)', 'observaciones']);

        $this->addFilterSelect('ListAlbaranCliente', 'codalmacen', 'almacenes', '', 'nombre');
        $this->addFilterSelect('ListAlbaranCliente', 'codserie', 'series', '', 'descripcion');
        $this->addFilterSelect('ListAlbaranCliente', 'codpago', 'formaspago', '', 'descripcion');

        $this->addFilterCheckbox('ListAlbaranCliente', 'invoice', 'invoice', 'ptefactura', true);

        $this->addFilterDatePicker('ListAlbaranCliente', 'date', 'date', 'fecha');

        $this->addFilterNumber('ListAlbaranCliente', 'total', 'total');

        $this->addOrderBy('ListAlbaranCliente', 'codigo', 'code');
        $this->addOrderBy('ListAlbaranCliente', 'fecha', 'date');
        $this->addOrderBy('ListAlbaranCliente', 'total', 'amount');
    }
}
