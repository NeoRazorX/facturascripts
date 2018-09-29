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
 * Controller to list the items in the FacturaProveedor model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 * @author Raul Jimenez <raul.jimenez@nazcanetworks.com>
 */
class ListFacturaProveedor extends ExtendedController\ListController
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
        $pagedata['menu'] = 'purchases';

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addView('ListFacturaProveedor', 'FacturaProveedor', 'invoices', 'fa-copy');
        $this->addSearchFields('ListFacturaProveedor', ['codigo', 'numproveedor', 'observaciones']);
        $this->addOrderBy('ListFacturaProveedor', ['codigo'], 'code');
        $this->addOrderBy('ListFacturaProveedor', ['fecha'], 'date', 2);
        $this->addOrderBy('ListFacturaProveedor', ['total'], 'amount');

        $this->addFilterDatePicker('ListFacturaProveedor', 'from-date', 'from-date', 'fecha', '>=');
        $this->addFilterDatePicker('ListFacturaProveedor', 'until-date', 'until-date', 'fecha', '<=');
        $this->addFilterNumber('ListFacturaProveedor', 'min-total', 'total', 'total', '>=');
        $this->addFilterNumber('ListFacturaProveedor', 'max-total', 'total', 'total', '<=');

        $where = [new DataBaseWhere('tipodoc', 'FacturaProveedor')];
        $statusValues = $this->codeModel->all('estados_documentos', 'idestado', 'nombre', true, $where);
        $this->addFilterSelect('ListFacturaProveedor', 'idestado', 'state', 'idestado', $statusValues);

        $warehouseValues = $this->codeModel->all('almacenes', 'codalmacen', 'nombre');
        $this->addFilterSelect('ListFacturaProveedor', 'codalmacen', 'warehouse', 'codalmacen', $warehouseValues);

        $serieValues = $this->codeModel->all('series', 'codserie', 'descripcion');
        $this->addFilterSelect('ListFacturaProveedor', 'codserie', 'series', 'codserie', $serieValues);

        $paymentValues = $this->codeModel->all('formaspago', 'codpago', 'descripcion');
        $this->addFilterSelect('ListFacturaProveedor', 'codpago', 'payment-method', 'codpago', $paymentValues);

        $this->addFilterAutocomplete('ListFacturaProveedor', 'codproveedor', 'supplier', 'codproveedor', 'Proveedor');
        $this->addFilterCheckbox('ListFacturaProveedor', 'paid', 'paid', 'pagada');
        $this->addFilterCheckbox('ListFacturaProveedor', 'femail', 'email-not-sent', 'femail', 'IS', null);

        // Delivery notes lines
        $this->createViewLines();
    }

    protected function createViewLines()
    {
        $this->addView('ListLineaFacturaProveedor', 'LineaFacturaProveedor', 'lines', 'fa-list');
        $this->addSearchFields('ListLineaFacturaProveedor', ['referencia', 'descripcion']);
        $this->addOrderBy('ListLineaFacturaProveedor', ['referencia'], 'reference');
        $this->addOrderBy('ListLineaFacturaProveedor', ['cantidad'], 'quantity');
        $this->addOrderBy('ListLineaFacturaProveedor', ['descripcion'], 'description');
        $this->addOrderBy('ListLineaFacturaProveedor', ['pvptotal'], 'ammount');
        $this->addOrderBy('ListLineaFacturaProveedor', ['idfactura'], 'code', 2);

        $taxValues = $this->codeModel->all('impuestos', 'codimpuesto', 'descripcion');
        $this->addFilterSelect('ListLineaFacturaProveedor', 'codimpuesto', 'tax', 'codimpuesto', $taxValues);

        $this->addFilterNumber('ListLineaFacturaProveedor', 'cantidad', 'quantity', 'cantidad');
        $this->addFilterNumber('ListLineaFacturaProveedor', 'dtopor', 'discount', 'dtopor');
        $this->addFilterNumber('ListLineaFacturaProveedor', 'pvpunitario', 'pvp', 'pvpunitario');
        $this->addFilterNumber('ListLineaFacturaProveedor', 'pvptotal', 'ammount', 'pvptotal');

        /// disable megasearch for this view
        $this->setSettings('ListLineaFacturaProveedor', 'megasearch', false);
    }
}
