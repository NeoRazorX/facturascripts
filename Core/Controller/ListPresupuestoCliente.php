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
 * Controller to list the items in the PresupuestoCliente model
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author Raul Jimenez         <raul.jimenez@nazcanetworks.com>
 */
class ListPresupuestoCliente extends ExtendedController\ListController
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
        $pagedata['icon'] = 'fa-copy';
        $pagedata['menu'] = 'sales';

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addView('ListPresupuestoCliente', 'PresupuestoCliente', 'estimations', 'fa-copy');
        $this->addSearchFields('ListPresupuestoCliente', ['codigo', 'numero2', 'observaciones']);
        $this->addOrderBy('ListPresupuestoCliente', ['codigo'], 'code');
        $this->addOrderBy('ListPresupuestoCliente', ['fecha'], 'date', 2);
        $this->addOrderBy('ListPresupuestoCliente', ['total'], 'amount');

        $this->addFilterDatePicker('ListPresupuestoCliente', 'from-date', 'from-date', 'fecha', '>=');
        $this->addFilterDatePicker('ListPresupuestoCliente', 'until-date', 'until-date', 'fecha', '<=');
        $this->addFilterNumber('ListPresupuestoCliente', 'min-total', 'total', 'total', '>=');
        $this->addFilterNumber('ListPresupuestoCliente', 'max-total', 'total', 'total', '<=');

        $where = [new DataBaseWhere('tipodoc', 'PresupuestoCliente')];
        $statusValues = $this->codeModel->all('estados_documentos', 'idestado', 'nombre', true, $where);
        $this->addFilterSelect('ListPresupuestoCliente', 'idestado', 'state', 'idestado', $statusValues);

        $warehouseValues = $this->codeModel->all('almacenes', 'codalmacen', 'nombre');
        $this->addFilterSelect('ListPresupuestoCliente', 'codalmacen', 'warehouse', 'codalmacen', $warehouseValues);

        $serieValues = $this->codeModel->all('series', 'codserie', 'descripcion');
        $this->addFilterSelect('ListPresupuestoCliente', 'codserie', 'series', 'codserie', $serieValues);

        $paymentValues = $this->codeModel->all('formaspago', 'codpago', 'descripcion');
        $this->addFilterSelect('ListPresupuestoCliente', 'codpago', 'payment-method', 'codpago', $paymentValues);

        $this->addFilterAutocomplete('ListPresupuestoCliente', 'codcliente', 'customer', 'codcliente', 'Cliente');
        $this->addFilterCheckbox('ListPresupuestoCliente', 'femail', 'email-not-sent', 'femail', 'IS', null);

        // Delivery notes lines
        $this->createViewLines();
    }

    protected function createViewLines()
    {
        $this->addView('ListLineaPresupuestoCliente', 'LineaPresupuestoCliente', 'lines', 'fa-list');
        $this->addSearchFields('ListLineaPresupuestoCliente', ['referencia', 'descripcion']);
        $this->addOrderBy('ListLineaPresupuestoCliente', ['referencia'], 'reference');
        $this->addOrderBy('ListLineaPresupuestoCliente', ['cantidad'], 'quantity');
        $this->addOrderBy('ListLineaPresupuestoCliente', ['descripcion'], 'description');
        $this->addOrderBy('ListLineaPresupuestoCliente', ['pvptotal'], 'ammount');
        $this->addOrderBy('ListLineaPresupuestoCliente', ['idpresupuesto'], 'code', 2);

        $taxValues = $this->codeModel->all('impuestos', 'codimpuesto', 'descripcion');
        $this->addFilterSelect('ListLineaPresupuestoCliente', 'codimpuesto', 'tax', 'codimpuesto', $taxValues);

        $this->addFilterNumber('ListLineaPresupuestoCliente', 'cantidad', 'quantity', 'cantidad');
        $this->addFilterNumber('ListLineaPresupuestoCliente', 'dtopor', 'discount', 'dtopor');
        $this->addFilterNumber('ListLineaPresupuestoCliente', 'pvpunitario', 'pvp', 'pvpunitario');
        $this->addFilterNumber('ListLineaPresupuestoCliente', 'pvptotal', 'ammount', 'pvptotal');

        /// disable megasearch for this view
        $this->setSettings('ListLineaPresupuestoCliente', 'megasearch', false);
    }
}
