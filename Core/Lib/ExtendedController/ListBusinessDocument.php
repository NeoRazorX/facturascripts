<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\DataSrc\Agentes;
use FacturaScripts\Core\DataSrc\Almacenes;
use FacturaScripts\Core\DataSrc\Divisas;
use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\DataSrc\FormasPago;
use FacturaScripts\Core\DataSrc\Impuestos;
use FacturaScripts\Core\DataSrc\Series;
use FacturaScripts\Dinamic\Lib\BusinessDocumentGenerator;

/**
 * Description of ListBusinessDocument
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class ListBusinessDocument extends ListController
{

    use ListBusinessActionTrait;

    /**
     * @param string $viewName
     * @param string $modelName
     */
    protected function addCommonViewFilters(string $viewName, string $modelName)
    {
        $this->addFilterPeriod($viewName, 'date', 'period', 'fecha');
        $this->addFilterNumber($viewName, 'min-total', 'total', 'total', '>=');
        $this->addFilterNumber($viewName, 'max-total', 'total', 'total', '<=');

        $where = [new DataBaseWhere('tipodoc', $modelName)];
        $statusValues = $this->codeModel->all('estados_documentos', 'idestado', 'nombre', true, $where);
        $this->addFilterSelect($viewName, 'idestado', 'state', 'idestado', $statusValues);

        $users = $this->codeModel->all('users', 'nick', 'nick');
        if (count($users) > 2) {
            $this->addFilterSelect($viewName, 'nick', 'user', 'nick', $users);
        }

        $companies = Empresas::codeModel();
        if (count($companies) > 2) {
            $this->addFilterSelect($viewName, 'idempresa', 'company', 'idempresa', $companies);
        }

        $warehouses = Almacenes::codeModel();
        if (count($warehouses) > 2) {
            $this->addFilterSelect($viewName, 'codalmacen', 'warehouse', 'codalmacen', $warehouses);
        }

        $series = Series::codeModel();
        if (count($series) > 2) {
            $this->addFilterSelect($viewName, 'codserie', 'series', 'codserie', $series);
        }

        $paymethods = FormasPago::codeModel();
        if (count($paymethods) > 2) {
            $this->addFilterSelect($viewName, 'codpago', 'payment-method', 'codpago', $paymethods);
        }

        $currencies = Divisas::codeModel();
        if (count($currencies) > 2) {
            $this->addFilterSelect($viewName, 'coddivisa', 'currency', 'coddivisa', $currencies);
        }

        $this->addFilterCheckbox($viewName, 'totalrecargo', 'surcharge', 'totalrecargo', '!=', 0);
        $this->addFilterCheckbox($viewName, 'totalirpf', 'retention', 'totalirpf', '!=', 0);
        $this->addFilterCheckbox($viewName, 'totalsuplidos', 'supplied-amount', 'totalsuplidos', '!=', 0);
    }

    /**
     * @param string $viewName
     * @param string $modelName
     */
    protected function createViewLines(string $viewName, string $modelName)
    {
        $this->addView($viewName, $modelName, 'lines', 'fas fa-list');
        $this->addSearchFields($viewName, ['referencia', 'descripcion']);
        $this->addOrderBy($viewName, ['referencia'], 'reference');
        $this->addOrderBy($viewName, ['cantidad'], 'quantity');
        $this->addOrderBy($viewName, ['descripcion'], 'description');
        $this->addOrderBy($viewName, ['pvptotal'], 'amount');
        $this->addOrderBy($viewName, ['idlinea'], 'code', 2);

        // filters
        $this->addFilterAutocomplete($viewName, 'idproducto', 'product', 'idproducto', 'productos', 'idproducto', 'referencia');
        $this->addFilterSelect($viewName, 'codimpuesto', 'tax', 'codimpuesto', Impuestos::codeModel());
        $this->addFilterNumber($viewName, 'cantidad', 'quantity', 'cantidad');
        $this->addFilterNumber($viewName, 'dtopor', 'discount', 'dtopor');
        $this->addFilterNumber($viewName, 'pvpunitario', 'pvp', 'pvpunitario');
        $this->addFilterNumber($viewName, 'pvptotal', 'amount', 'pvptotal');
        $this->addFilterCheckbox($viewName, 'recargo', 'surcharge', 'recargo', '!=', 0);
        $this->addFilterCheckbox($viewName, 'irpf', 'retention', 'irpf', '!=', 0);
        $this->addFilterCheckbox($viewName, 'suplido', 'supplied', 'suplido');

        // settings
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'checkBoxes', false);
        $this->setSettings($viewName, 'megasearch', false);
    }

    /**
     * @param string $viewName
     * @param string $modelName
     * @param string $label
     */
    protected function createViewPurchases(string $viewName, string $modelName, string $label)
    {
        $this->addView($viewName, $modelName, $label, 'fas fa-copy');
        $this->addSearchFields($viewName, ['codigo', 'nombre', 'numproveedor', 'observaciones']);
        $this->addOrderBy($viewName, ['codigo'], 'code');
        $this->addOrderBy($viewName, ['fecha', $this->tableColToNumber('numero')], 'date', 2);
        $this->addOrderBy($viewName, [$this->tableColToNumber('numero')], 'number');
        $this->addOrderBy($viewName, ['numproveedor'], 'numsupplier');
        $this->addOrderBy($viewName, ['codproveedor'], 'supplier-code');
        $this->addOrderBy($viewName, ['total'], 'total');

        // filters
        $this->addCommonViewFilters($viewName, $modelName);
        $this->addFilterAutocomplete($viewName, 'codproveedor', 'supplier', 'codproveedor', 'Proveedor');
        $this->addFilterCheckbox($viewName, 'femail', 'email-not-sent', 'femail', 'IS', null);
    }

    /**
     * @param string $viewName
     * @param string $modelName
     * @param string $label
     */
    protected function createViewSales(string $viewName, string $modelName, string $label)
    {
        $this->addView($viewName, $modelName, $label, 'fas fa-copy');
        $this->addSearchFields($viewName, ['codigo', 'codigoenv', 'nombrecliente', 'numero2', 'observaciones']);
        $this->addOrderBy($viewName, ['codigo'], 'code');
        $this->addOrderBy($viewName, ['codcliente'], 'customer-code');
        $this->addOrderBy($viewName, ['fecha', $this->tableColToNumber('numero')], 'date', 2);
        $this->addOrderBy($viewName, [$this->tableColToNumber('numero')], 'number');
        $this->addOrderBy($viewName, ['numero2'], 'number2');
        $this->addOrderBy($viewName, ['total'], 'total');

        // filters
        $this->addCommonViewFilters($viewName, $modelName);
        $this->addFilterAutocomplete($viewName, 'codcliente', 'customer', 'codcliente', 'Cliente');
        $this->addFilterAutocomplete($viewName, 'idcontactofact', 'billing-address', 'idcontactofact', 'contactos', 'idcontacto', 'direccion');
        $this->addFilterautocomplete($viewName, 'idcontactoenv', 'shipping-address', 'idcontactoenv', 'contactos', 'idcontacto', 'direccion');

        $agents = Agentes::codeModel();
        if (count($agents) > 2) {
            $this->addFilterSelect($viewName, 'codagente', 'agent', 'codagente', $agents);
        }

        $carriers = $this->codeModel->all('agenciastrans', 'codtrans', 'nombre');
        $this->addFilterSelect($viewName, 'codtrans', 'carrier', 'codtrans', $carriers);

        $this->addFilterCheckbox($viewName, 'femail', 'email-not-sent', 'femail', 'IS', null);
    }

    /**
     * Run the actions that alter data before reading it.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        $allowUpdate = $this->permissions->allowUpdate;
        $codes = $this->request->request->get('code');
        $model = $this->views[$this->active]->model;

        switch ($action) {
            case 'approve-document':
                return $this->approveDocumentAction($codes, $model, $allowUpdate, $this->dataBase);

            case 'approve-document-same-date':
                BusinessDocumentGenerator::setSameDate(true);
                return $this->approveDocumentAction($codes, $model, $allowUpdate, $this->dataBase);

            case 'group-document':
                return $this->groupDocumentAction($codes, $model);

            case 'lock-invoice':
                return $this->lockInvoiceAction($codes, $model, $allowUpdate, $this->dataBase);

            case 'pay-receipt':
                return $this->payReceiptAction($codes, $model, $allowUpdate, $this->dataBase, $this->user->nick);
        }

        return parent::execPreviousAction($action);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function tableColToNumber(string $name): string
    {
        return strtolower(FS_DB_TYPE) == 'postgresql' ? 'CAST(' . $name . ' as integer)' : 'CAST(' . $name . ' as unsigned)';
    }
}
