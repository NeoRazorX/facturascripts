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
 * Controller to list the items in the PresupuestoProveedor model
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author Raul Jimenez         <raul.jimenez@nazcanetworks.com>
 * @author Cristo M. Estévez Hernández <cristom.estevez@gmail.com>
 */
class ListPresupuestoProveedor extends ExtendedController\ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'estimations';
        $pagedata['icon'] = 'fas fa-copy';
        $pagedata['menu'] = 'purchases';

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addView('ListPresupuestoProveedor', 'PresupuestoProveedor', 'estimations', 'fas fa-copy');
        $this->addSearchFields('ListPresupuestoProveedor', ['codigo', 'numproveedor', 'observaciones']);
        $this->addOrderBy('ListPresupuestoProveedor', ['codigo'], 'code');
        $this->addOrderBy('ListPresupuestoProveedor', ['fecha'], 'date', 2);
        $this->addOrderBy('ListPresupuestoProveedor', ['total'], 'amount');

        $this->addFilterPeriod('ListPresupuestoProveedor', 'date', 'period', 'fecha');
        $this->addFilterNumber('ListPresupuestoProveedor', 'min-total', 'total', 'total', '>=');
        $this->addFilterNumber('ListPresupuestoProveedor', 'max-total', 'total', 'total', '<=');

        $where = [new DataBaseWhere('tipodoc', 'PresupuestoProveedor')];
        $statusValues = $this->codeModel->all('estados_documentos', 'idestado', 'nombre', true, $where);
        $this->addFilterSelect('ListPresupuestoProveedor', 'idestado', 'state', 'idestado', $statusValues);

        $warehouseValues = $this->codeModel->all('almacenes', 'codalmacen', 'nombre');
        $this->addFilterSelect('ListPresupuestoProveedor', 'codalmacen', 'warehouse', 'codalmacen', $warehouseValues);

        $serieValues = $this->codeModel->all('series', 'codserie', 'descripcion');
        $this->addFilterSelect('ListPresupuestoProveedor', 'codserie', 'series', 'codserie', $serieValues);

        $paymentValues = $this->codeModel->all('formaspago', 'codpago', 'descripcion');
        $this->addFilterSelect('ListPresupuestoProveedor', 'codpago', 'payment-method', 'codpago', $paymentValues);

        $this->addFilterAutocomplete('ListPresupuestoProveedor', 'codproveedor', 'supplier', 'codproveedor', 'Proveedor');
        $this->addFilterCheckbox('ListPresupuestoProveedor', 'femail', 'email-not-sent', 'femail', 'IS', null);

        // Delivery notes lines
        $this->createViewLines();
    }

    protected function createViewLines()
    {
        $this->addView('ListLineaPresupuestoProveedor', 'LineaPresupuestoProveedor', 'lines', 'fas fa-list');
        $this->addSearchFields('ListLineaPresupuestoProveedor', ['referencia', 'descripcion']);
        $this->addOrderBy('ListLineaPresupuestoProveedor', ['referencia'], 'reference');
        $this->addOrderBy('ListLineaPresupuestoProveedor', ['cantidad'], 'quantity');
        $this->addOrderBy('ListLineaPresupuestoProveedor', ['descripcion'], 'description');
        $this->addOrderBy('ListLineaPresupuestoProveedor', ['pvptotal'], 'ammount');
        $this->addOrderBy('ListLineaPresupuestoProveedor', ['idpresupuesto'], 'code', 2);

        $taxValues = $this->codeModel->all('impuestos', 'codimpuesto', 'descripcion');
        $this->addFilterSelect('ListLineaPresupuestoProveedor', 'codimpuesto', 'tax', 'codimpuesto', $taxValues);

        $this->addFilterNumber('ListLineaPresupuestoProveedor', 'cantidad', 'quantity', 'cantidad');
        $this->addFilterNumber('ListLineaPresupuestoProveedor', 'dtopor', 'discount', 'dtopor');
        $this->addFilterNumber('ListLineaPresupuestoProveedor', 'pvpunitario', 'pvp', 'pvpunitario');
        $this->addFilterNumber('ListLineaPresupuestoProveedor', 'pvptotal', 'ammount', 'pvptotal');

        /// disable megasearch for this view
        $this->setSettings('ListLineaPresupuestoProveedor', 'megasearch', false);
        $this->setSettings('ListLineaPresupuestoProveedor', 'btnNew', false);
        $this->setSettings('ListLineaPresupuestoProveedor', 'btnDelete', false);
    }
}
