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
 * Controller to list the items in the AlbaranCliente model
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author Cristo M. Estévez Hernández <cristom.estevez@gmail.com>
 */
class ListAlbaranCliente extends ExtendedController\ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'delivery-notes';
        $pagedata['icon'] = 'fas fa-copy';
        $pagedata['menu'] = 'sales';

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        // Delivery notes
        $this->addView('ListAlbaranCliente', 'AlbaranCliente', 'delivery-notes', 'fas fa-copy');
        $this->addSearchFields('ListAlbaranCliente', ['codigo', 'numero2', 'nombrecliente', 'observaciones']);
        $this->addOrderBy('ListAlbaranCliente', ['codigo'], 'code');
        $this->addOrderBy('ListAlbaranCliente', ['fecha'], 'date', 2);
        $this->addOrderBy('ListAlbaranCliente', ['total'], 'amount');

        $this->addFilterDatePicker('ListAlbaranCliente', 'from-date', 'from-date', 'fecha', '>=');
        $this->addFilterDatePicker('ListAlbaranCliente', 'until-date', 'until-date', 'fecha', '<=');
        $this->addFilterNumber('ListAlbaranCliente', 'min-total', 'total', 'total', '>=');
        $this->addFilterNumber('ListAlbaranCliente', 'max-total', 'total', 'total', '<=');

        $where = [new DataBaseWhere('tipodoc', 'AlbaranCliente')];
        $statusValues = $this->codeModel->all('estados_documentos', 'idestado', 'nombre', true, $where);
        $this->addFilterSelect('ListAlbaranCliente', 'idestado', 'state', 'idestado', $statusValues);

        $warehouseValues = $this->codeModel->all('almacenes', 'codalmacen', 'nombre');
        $this->addFilterSelect('ListAlbaranCliente', 'codalmacen', 'warehouse', 'codalmacen', $warehouseValues);

        $serieValues = $this->codeModel->all('series', 'codserie', 'descripcion');
        $this->addFilterSelect('ListAlbaranCliente', 'codserie', 'series', 'codserie', $serieValues);

        $paymentValues = $this->codeModel->all('formaspago', 'codpago', 'descripcion');
        $this->addFilterSelect('ListAlbaranCliente', 'codpago', 'payment-method', 'codpago', $paymentValues);

        $this->addFilterAutocomplete('ListAlbaranCliente', 'codcliente', 'customer', 'codcliente', 'Cliente');
        $this->addFilterCheckbox('ListAlbaranCliente', 'femail', 'email-not-sent', 'femail', 'IS', null);

        // Delivery notes lines
        $this->createViewLines();
    }

    protected function createViewLines()
    {
        $this->addView('ListLineaAlbaranCliente', 'LineaAlbaranCliente', 'lines', 'fas fa-list');
        $this->addSearchFields('ListLineaAlbaranCliente', ['referencia', 'descripcion']);
        $this->addOrderBy('ListLineaAlbaranCliente', ['referencia'], 'reference');
        $this->addOrderBy('ListLineaAlbaranCliente', ['cantidad'], 'quantity');
        $this->addOrderBy('ListLineaAlbaranCliente', ['descripcion'], 'description');
        $this->addOrderBy('ListLineaAlbaranCliente', ['pvptotal'], 'ammount');
        $this->addOrderBy('ListLineaAlbaranCliente', ['idalbaran'], 'delivery-note', 2);

        $taxValues = $this->codeModel->all('impuestos', 'codimpuesto', 'descripcion');
        $this->addFilterSelect('ListLineaAlbaranCliente', 'codimpuesto', 'tax', 'codimpuesto', $taxValues);

        $this->addFilterNumber('ListLineaAlbaranCliente', 'cantidad', 'quantity', 'cantidad');
        $this->addFilterNumber('ListLineaAlbaranCliente', 'dtopor', 'discount', 'dtopor');
        $this->addFilterNumber('ListLineaAlbaranCliente', 'pvpunitario', 'pvp', 'pvpunitario');
        $this->addFilterNumber('ListLineaAlbaranCliente', 'pvptotal', 'ammount', 'pvptotal');

        /// disable megasearch for this view
        $this->setSettings('ListLineaAlbaranCliente', 'megasearch', false);
        $this->setSettings('ListLineaAlbaranCliente', 'btnNew', false);
        $this->setSettings('ListLineaAlbaranCliente', 'btnDelete', false);
    }
}
