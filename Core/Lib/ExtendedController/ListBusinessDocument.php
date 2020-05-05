<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
     *
     * @param string $viewName
     * @param string $model
     */
    protected function addCommonViewFilters($viewName, $model)
    {
        $this->addFilterPeriod($viewName, 'date', 'period', 'fecha');
        $this->addFilterNumber($viewName, 'min-total', 'total', 'total', '>=');
        $this->addFilterNumber($viewName, 'max-total', 'total', 'total', '<=');

        $where = [new DataBaseWhere('tipodoc', $model)];
        $statusValues = $this->codeModel->all('estados_documentos', 'idestado', 'nombre', true, $where);
        $this->addFilterSelect($viewName, 'idestado', 'state', 'idestado', $statusValues);

        $users = $this->codeModel->all('users', 'nick', 'nick');
        if (\count($users) > 2) {
            $this->addFilterSelect($viewName, 'nick', 'user', 'nick', $users);
        }

        $companies = $this->codeModel->all('empresas', 'idempresa', 'nombrecorto');
        if (\count($companies) > 2) {
            $this->addFilterSelect($viewName, 'idempresa', 'company', 'idempresa', $companies);
        }

        $warehouseValues = $this->codeModel->all('almacenes', 'codalmacen', 'nombre');
        if (\count($warehouseValues) > 2) {
            $this->addFilterSelect($viewName, 'codalmacen', 'warehouse', 'codalmacen', $warehouseValues);
        }

        $serieValues = $this->codeModel->all('series', 'codserie', 'descripcion');
        if (\count($serieValues) > 2) {
            $this->addFilterSelect($viewName, 'codserie', 'series', 'codserie', $serieValues);
        }

        $paymentValues = $this->codeModel->all('formaspago', 'codpago', 'descripcion');
        $this->addFilterSelect($viewName, 'codpago', 'payment-method', 'codpago', $paymentValues);

        $currencies = $this->codeModel->all('divisas', 'coddivisa', 'descripcion');
        $this->addFilterSelect($viewName, 'coddivisa', 'currency', 'coddivisa', $currencies);
    }

    /**
     *
     * @param string $viewName
     * @param string $model
     */
    protected function createViewLines($viewName, $model)
    {
        $this->addView($viewName, $model, 'lines', 'fas fa-list');
        $this->addSearchFields($viewName, ['referencia', 'descripcion']);
        $this->addOrderBy($viewName, ['referencia'], 'reference');
        $this->addOrderBy($viewName, ['cantidad'], 'quantity');
        $this->addOrderBy($viewName, ['descripcion'], 'description');
        $this->addOrderBy($viewName, ['pvptotal'], 'amount');
        $this->addOrderBy($viewName, ['idlinea'], 'code', 2);

        /// filters
        $this->addFilterAutocomplete($viewName, 'idproducto', 'product', 'idproducto', 'productos', 'idproducto', 'referencia');

        $taxValues = $this->codeModel->all('impuestos', 'codimpuesto', 'descripcion');
        $this->addFilterSelect($viewName, 'codimpuesto', 'tax', 'codimpuesto', $taxValues);

        $this->addFilterNumber($viewName, 'cantidad', 'quantity', 'cantidad');
        $this->addFilterNumber($viewName, 'dtopor', 'discount', 'dtopor');
        $this->addFilterNumber($viewName, 'pvpunitario', 'pvp', 'pvpunitario');
        $this->addFilterNumber($viewName, 'pvptotal', 'amount', 'pvptotal');
        $this->addFilterCheckbox($viewName, 'suplido', 'supplied', 'suplido');

        /// disable megasearch for this view
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'checkBoxes', false);
        $this->setSettings($viewName, 'megasearch', false);
    }

    /**
     *
     * @param string $viewName
     * @param string $model
     * @param string $label
     */
    protected function createViewPurchases($viewName, $model, $label)
    {
        $this->addView($viewName, $model, $label, 'fas fa-copy');
        $this->addSearchFields($viewName, ['codigo', 'nombre', 'numproveedor', 'observaciones']);
        $this->addOrderBy($viewName, ['codigo'], 'code');
        $this->addOrderBy($viewName, ['fecha', 'hora', 'codigo'], 'date', 2);
        $this->addOrderBy($viewName, ['numero'], 'number');
        $this->addOrderBy($viewName, ['numproveedor'], 'numsupplier');
        $this->addOrderBy($viewName, ['total'], 'total');

        /// filters
        $this->addCommonViewFilters($viewName, $model);
        $this->addFilterAutocomplete($viewName, 'codproveedor', 'supplier', 'codproveedor', 'Proveedor');
        $this->addFilterCheckbox($viewName, 'femail', 'email-not-sent', 'femail', 'IS', null);
    }

    /**
     *
     * @param string $viewName
     * @param string $model
     * @param string $label
     */
    protected function createViewSales($viewName, $model, $label)
    {
        $this->addView($viewName, $model, $label, 'fas fa-copy');
        $this->addSearchFields($viewName, ['codigo', 'nombrecliente', 'numero2', 'observaciones']);
        $this->addOrderBy($viewName, ['codigo'], 'code');
        $this->addOrderBy($viewName, ['fecha', 'codigo'], 'date', 2);
        $this->addOrderBy($viewName, ['numero'], 'number');
        $this->addOrderBy($viewName, ['numero2'], 'number2');
        $this->addOrderBy($viewName, ['total'], 'total');

        /// filters
        $this->addCommonViewFilters($viewName, $model);
        $this->addFilterAutocomplete($viewName, 'codcliente', 'customer', 'codcliente', 'Cliente');
        $this->addFilterAutocomplete($viewName, 'idcontactofact', 'billing-address', 'idcontacto', 'contacto');
        $this->addFilterautocomplete($viewName, 'idcontactoenv', 'shipping-address', 'idcontacto', 'contacto');

        $agents = $this->codeModel->all('agentes', 'codagente', 'nombre');
        if (\count($agents) > 0) {
            $this->addFilterSelect($viewName, 'codagente', 'agent', 'codagente', $agents);
        }

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
}
