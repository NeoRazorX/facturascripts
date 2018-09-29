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
 * Controller to list the items in the FacturaCliente model
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author Raul Jimenez         <raul.jimenez@nazcanetworks.com>
 */
class ListFacturaCliente extends ExtendedController\ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'invoices';
        $pagedata['icon'] = 'fa-copy';
        $pagedata['menu'] = 'sales';

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addView('ListFacturaCliente', 'FacturaCliente', 'invoices', 'fa-copy');
        $this->addSearchFields('ListFacturaCliente', ['codigo', 'numero2', 'observaciones']);
        $this->addOrderBy('ListFacturaCliente', ['codigo'], 'code');
        $this->addOrderBy('ListFacturaCliente', ['fecha'], 'date', 2);
        $this->addOrderBy('ListFacturaCliente', ['total'], 'amount');

        $this->addFilterDatePicker('ListFacturaCliente', 'from-date', 'from-date', 'fecha', '>=');
        $this->addFilterDatePicker('ListFacturaCliente', 'until-date', 'until-date', 'fecha', '<=');
        $this->addFilterNumber('ListFacturaCliente', 'min-total', 'total', 'total', '>=');
        $this->addFilterNumber('ListFacturaCliente', 'max-total', 'total', 'total', '<=');

        $where = [new DataBaseWhere('tipodoc', 'FacturaCliente')];
        $statusValues = $this->codeModel->all('estados_documentos', 'idestado', 'nombre', true, $where);
        $this->addFilterSelect('ListFacturaCliente', 'idestado', 'state', 'idestado', $statusValues);

        $warehouseValues = $this->codeModel->all('almacenes', 'codalmacen', 'nombre');
        $this->addFilterSelect('ListFacturaCliente', 'codalmacen', 'warehouse', 'codalmacen', $warehouseValues);

        $serieValues = $this->codeModel->all('series', 'codserie', 'descripcion');
        $this->addFilterSelect('ListFacturaCliente', 'codserie', 'series', 'codserie', $serieValues);

        $paymentValues = $this->codeModel->all('formaspago', 'codpago', 'descripcion');
        $this->addFilterSelect('ListFacturaCliente', 'codpago', 'payment-method', 'codpago', $paymentValues);

        $this->addFilterAutocomplete('ListFacturaCliente', 'codcliente', 'customer', 'codcliente', 'Cliente');
        $this->addFilterCheckbox('ListFacturaCliente', 'paid', 'paid', 'pagada');
        $this->addFilterCheckbox('ListFacturaCliente', 'femail', 'email-not-sent', 'femail', 'IS', null);

        // Delivery notes lines
        $this->createViewLines();
    }

    protected function createViewLines()
    {
        $this->addView('ListLineaFacturaCliente', 'LineaFacturaCliente', 'lines', 'fa-list');
        $this->addSearchFields('ListLineaFacturaCliente', ['referencia', 'descripcion']);
        $this->addOrderBy('ListLineaFacturaCliente', ['referencia'], 'reference');
        $this->addOrderBy('ListLineaFacturaCliente', ['cantidad'], 'quantity');
        $this->addOrderBy('ListLineaFacturaCliente', ['descripcion'], 'description');
        $this->addOrderBy('ListLineaFacturaCliente', ['pvptotal'], 'ammount');
        $this->addOrderBy('ListLineaFacturaCliente', ['idfactura'], 'code', 2);

        $taxValues = $this->codeModel->all('impuestos', 'codimpuesto', 'descripcion');
        $this->addFilterSelect('ListLineaFacturaCliente', 'codimpuesto', 'tax', 'codimpuesto', $taxValues);

        $this->addFilterNumber('ListLineaFacturaCliente', 'cantidad', 'quantity', 'cantidad');
        $this->addFilterNumber('ListLineaFacturaCliente', 'dtopor', 'discount', 'dtopor');
        $this->addFilterNumber('ListLineaFacturaCliente', 'pvpunitario', 'pvp', 'pvpunitario');
        $this->addFilterNumber('ListLineaFacturaCliente', 'pvptotal', 'ammount', 'pvptotal');

        /// disable megasearch for this view
        $this->setSettings('ListLineaFacturaCliente', 'megasearch', false);
    }
}
