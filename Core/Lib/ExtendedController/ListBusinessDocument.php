<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib\ExtendedController;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Description of ListBusinessDocument
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class ListBusinessDocument extends ListController
{

    /**
     *
     * @param string $name
     * @param string $model
     */
    protected function addCommonViewFilters($name, $model)
    {
        $this->addFilterPeriod($name, 'date', 'period', 'fecha');
        $this->addFilterNumber($name, 'min-total', 'total', 'total', '>=');
        $this->addFilterNumber($name, 'max-total', 'total', 'total', '<=');

        $where = [new DataBaseWhere('tipodoc', $model)];
        $statusValues = $this->codeModel->all('estados_documentos', 'idestado', 'nombre', true, $where);
        $this->addFilterSelect($name, 'idestado', 'state', 'idestado', $statusValues);

        $users = $this->codeModel->all('users', 'nick', 'nick');
        if (count($users) > 2) {
            $this->addFilterSelect($name, 'nick', 'user', 'nick', $users);
        }

        $companies = $this->codeModel->all('empresas', 'idempresa', 'nombrecorto');
        if (count($companies) > 2) {
            $this->addFilterSelect($name, 'idempresa', 'company', 'idempresa', $companies);
        }

        $warehouseValues = $this->codeModel->all('almacenes', 'codalmacen', 'nombre');
        if (count($warehouseValues) > 2) {
            $this->addFilterSelect($name, 'codalmacen', 'warehouse', 'codalmacen', $warehouseValues);
        }

        $serieValues = $this->codeModel->all('series', 'codserie', 'descripcion');
        if (count($serieValues) > 2) {
            $this->addFilterSelect($name, 'codserie', 'series', 'codserie', $serieValues);
        }

        $paymentValues = $this->codeModel->all('formaspago', 'codpago', 'descripcion');
        $this->addFilterSelect($name, 'codpago', 'payment-method', 'codpago', $paymentValues);
    }

    /**
     *
     * @param string $name
     * @param string $model
     */
    protected function createViewLines($name, $model)
    {
        $this->addView($name, $model, 'lines', 'fas fa-list');
        $this->addSearchFields($name, ['referencia', 'descripcion']);
        $this->addOrderBy($name, ['referencia'], 'reference');
        $this->addOrderBy($name, ['cantidad'], 'quantity');
        $this->addOrderBy($name, ['descripcion'], 'description');
        $this->addOrderBy($name, ['pvptotal'], 'ammount');
        $this->addOrderBy($name, ['idlinea'], 'code', 2);

        /// filters
        $taxValues = $this->codeModel->all('impuestos', 'codimpuesto', 'descripcion');
        $this->addFilterSelect($name, 'codimpuesto', 'tax', 'codimpuesto', $taxValues);

        $this->addFilterNumber($name, 'cantidad', 'quantity', 'cantidad');
        $this->addFilterNumber($name, 'dtopor', 'discount', 'dtopor');
        $this->addFilterNumber($name, 'pvpunitario', 'pvp', 'pvpunitario');
        $this->addFilterNumber($name, 'pvptotal', 'ammount', 'pvptotal');

        /// disable megasearch for this view
        $this->setSettings($name, 'megasearch', false);
        $this->setSettings($name, 'btnNew', false);
        $this->setSettings($name, 'btnDelete', false);
    }

    /**
     *
     * @param string $name
     * @param string $model
     * @param string $label
     */
    protected function createViewPurchases($name, $model, $label)
    {
        $this->addView($name, $model, $label, 'fas fa-copy');
        $this->addSearchFields($name, ['codigo', 'numproveedor', 'observaciones']);
        $this->addOrderBy($name, ['codigo'], 'code');
        $this->addOrderBy($name, ['fecha', 'hora'], 'date', 2);
        $this->addOrderBy($name, ['total'], 'amount');

        /// filters
        $this->addCommonViewFilters($name, $model);
        $this->addFilterAutocomplete($name, 'codproveedor', 'supplier', 'codproveedor', 'Proveedor');
        $this->addFilterCheckbox($name, 'femail', 'email-not-sent', 'femail', 'IS', null);
        $this->addFilterCheckbox($name, 'paid', 'paid', 'pagado');
    }

    /**
     *
     * @param string $name
     * @param string $model
     * @param string $label
     */
    protected function createViewSales($name, $model, $label)
    {
        $this->addView($name, $model, $label, 'fas fa-copy');
        $this->addSearchFields($name, ['codigo', 'numero2', 'observaciones']);
        $this->addOrderBy($name, ['codigo'], 'code');
        $this->addOrderBy($name, ['fecha', 'hora'], 'date', 2);
        $this->addOrderBy($name, ['total'], 'amount');

        /// filters
        $this->addCommonViewFilters($name, $model);
        $this->addFilterAutocomplete($name, 'codcliente', 'customer', 'codcliente', 'Cliente');
        $this->addFilterAutocomplete($name, 'idcontactofact', 'billing-address', 'idcontacto', 'contacto');
        $this->addFilterautocomplete($name, 'idcontactoenv', 'shipping-address', 'idcontacto', 'contacto');
        $this->addFilterCheckbox($name, 'femail', 'email-not-sent', 'femail', 'IS', null);
        $this->addFilterCheckbox($name, 'paid', 'paid', 'pagado');
    }
}
