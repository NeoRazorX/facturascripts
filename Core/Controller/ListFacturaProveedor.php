<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * Controller to list the items in the FacturaProveedor model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ListFacturaProveedor extends ExtendedController\ListController
{

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addView('\FacturaScripts\Dinamic\Model\FacturaProveedor', 'ListFacturaProveedor');
        $this->addSearchFields('ListFacturaProveedor', ['codigo', 'numproveedor', 'observaciones']);

        $this->addFilterDatePicker('ListFacturaProveedor', 'date', 'date', 'fecha');
        $this->addFilterNumber('ListFacturaProveedor', 'total', 'total');
        $this->addFilterSelect('ListFacturaProveedor', 'codalmacen', 'almacenes', 'codalmacen', 'nombre');
        $this->addFilterSelect('ListFacturaProveedor', 'codserie', 'series', 'codserie', 'descripcion');
        $this->addFilterSelect('ListFacturaProveedor', 'codpago', 'formaspago', 'codpago', 'descripcion');
        $this->addFilterAutocomplete('ListFacturaProveedor', 'codproveedor', 'proveedores', 'codproveedor', 'nombre');

        $this->addOrderBy('ListFacturaProveedor', 'codigo', 'code');
        $this->addOrderBy('ListFacturaProveedor', 'fecha', 'date', 2);
        $this->addOrderBy('ListFacturaProveedor', 'total', 'amount');
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'invoices';
        $pagedata['icon'] = 'fa-files-o';
        $pagedata['menu'] = 'purchases';

        return $pagedata;
    }
}
