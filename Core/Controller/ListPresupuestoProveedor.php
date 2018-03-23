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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController;

/**
 * Controller to list the items in the PresupuestoProveedor model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ListPresupuestoProveedor extends ExtendedController\ListController
{

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addView('PresupuestoProveedor', 'ListPresupuestoProveedor');
        $this->addSearchFields('ListPresupuestoProveedor', ['codigo', 'numproveedor', 'observaciones']);

        $this->addFilterDatePicker('ListPresupuestoProveedor', 'date', 'date', 'fecha');
        $this->addFilterNumber('ListPresupuestoProveedor', 'total', 'total');

        $where = [new DataBaseWhere('tipodoc', 'PresupuestoProveedor')];
        $this->addFilterSelect('ListPresupuestoProveedor', 'idestado', 'estados_documentos', 'idestado', 'nombre', $where);

        $this->addFilterSelect('ListPresupuestoProveedor', 'codalmacen', 'almacenes', 'codalmacen', 'nombre');
        $this->addFilterSelect('ListPresupuestoProveedor', 'codserie', 'series', 'codserie', 'descripcion');
        $this->addFilterSelect('ListPresupuestoProveedor', 'codpago', 'formaspago', 'codpago', 'descripcion');
        $this->addFilterAutocomplete('ListPresupuestoProveedor', 'codproveedor', 'proveedores', 'codproveedor', 'nombre');

        $this->addOrderBy('ListPresupuestoProveedor', 'codigo', 'code');
        $this->addOrderBy('ListPresupuestoProveedor', 'fecha', 'date', 2);
        $this->addOrderBy('ListPresupuestoProveedor', 'total', 'amount');
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'estimations';
        $pagedata['icon'] = 'fa-files-o';
        $pagedata['menu'] = 'purchases';

        return $pagedata;
    }
}
