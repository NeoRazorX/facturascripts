<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\DataSrc\GruposClientes;
use FacturaScripts\Core\DataSrc\Impuestos;
use FacturaScripts\Core\DataSrc\Series;
use FacturaScripts\Core\Lib\InvoiceOperation;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\BusinessDocumentGenerator;
use FacturaScripts\Dinamic\Model\EstadoDocumento;

/**
 * Description of ListBusinessDocument
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class ListBusinessDocument extends ListController
{
    use ListBusinessActionTrait;

    protected function addColorStatus(string $viewName, string $modelName): void
    {
        $estadoDocumento = new EstadoDocumento();
        $where = [new DataBaseWhere('tipodoc', $modelName)];
        foreach ($estadoDocumento->all($where, [], 0, 0) as $status) {
            if ($status->color) {
                $this->addColor($viewName, 'idestado', $status->idestado, $status->color, $status->nombre);
            }
        }
    }

    protected function addCommonViewFilters(string $viewName, string $modelName): void
    {
        $this->addFilterPeriod($viewName, 'date', 'period', 'fecha');
        $this->addFilterNumber($viewName, 'min-total', 'total', 'total', '>=');
        $this->addFilterNumber($viewName, 'max-total', 'total', 'total', '<=');

        $where = [new DataBaseWhere('tipodoc', $modelName)];
        $statusValues = $this->codeModel->all('estados_documentos', 'idestado', 'nombre', true, $where);
        $this->addFilterSelect($viewName, 'idestado', 'state', 'idestado', $statusValues);

        if ($this->permissions->onlyOwnerData === false) {
            $users = $this->codeModel->all('users', 'nick', 'nick');
            if (count($users) > 1) {
                $this->addFilterSelect($viewName, 'nick', 'user', 'nick', $users);
            }
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

        $operations = [['code' => '', 'description' => '------']];
        foreach (InvoiceOperation::all() as $key => $value) {
            $operations[] = [
                'code' => $key,
                'description' => Tools::lang()->trans($value)
            ];
        }
        $this->addFilterSelect($viewName, 'operacion', 'operation', 'operacion', $operations);

        $payMethods = FormasPago::codeModel();
        if (count($payMethods) > 2) {
            $this->addFilterSelect($viewName, 'codpago', 'payment-method', 'codpago', $payMethods);
        }

        $currencies = Divisas::codeModel();
        if (count($currencies) > 2) {
            $this->addFilterSelect($viewName, 'coddivisa', 'currency', 'coddivisa', $currencies);
        }

        $this->addFilterCheckbox($viewName, 'totalrecargo', 'surcharge', 'totalrecargo', '!=', 0);
        $this->addFilterCheckbox($viewName, 'totalirpf', 'retention', 'totalirpf', '!=', 0);
        $this->addFilterCheckbox($viewName, 'totalsuplidos', 'supplied-amount', 'totalsuplidos', '!=', 0);
        $this->addFilterCheckbox($viewName, 'numdocs', 'has-attachments', 'numdocs', '!=', 0);
    }

    protected function createViewLines(string $viewName, string $modelName): void
    {
        $this->addView($viewName, $modelName, 'lines', 'fa-solid fa-list')
            ->addOrderBy(['referencia'], 'reference')
            ->addOrderBy(['cantidad'], 'quantity')
            ->addOrderBy(['servido'], 'quantity-served')
            ->addOrderBy(['descripcion'], 'description')
            ->addOrderBy(['pvptotal'], 'amount')
            ->addOrderBy(['idlinea'], 'code', 2)
            ->addSearchFields(['referencia', 'descripcion']);

        // filtros
        $this->addFilterAutocomplete($viewName, 'idproducto', 'product', 'idproducto', 'productos', 'idproducto', 'referencia');
        $this->addFilterAutocomplete($viewName, 'referencia', 'variant', 'referencia', 'variantes', 'referencia', 'referencia');
        $this->addFilterSelect($viewName, 'codimpuesto', 'tax', 'codimpuesto', Impuestos::codeModel());

        $stock = [
            ['code' => '', 'description' => '------'],
            ['code' => -2, 'description' => Tools::lang()->trans('book')],
            ['code' => -1, 'description' => Tools::lang()->trans('subtract')],
            ['code' => 0, 'description' => Tools::lang()->trans('do-nothing')],
            ['code' => 1, 'description' => Tools::lang()->trans('add')],
            ['code' => 2, 'description' => Tools::lang()->trans('foresee')]
        ];
        $this->addFilterSelect($viewName, 'actualizastock', 'stock', 'actualizastock', $stock);

        $this->addFilterNumber($viewName, 'cantidad-gt', 'quantity', 'cantidad');
        $this->addFilterNumber($viewName, 'cantidad-lt', 'quantity', 'cantidad', '<=');

        $this->addFilterNumber($viewName, 'servido-gt', 'quantity-served', 'servido');
        $this->addFilterNumber($viewName, 'servido-lt', 'quantity-served', 'servido', '<=');

        $this->addFilterNumber($viewName, 'dtopor-gt', 'discount', 'dtopor');
        $this->addFilterNumber($viewName, 'dtopor-lt', 'discount', 'dtopor', '<=');

        $this->addFilterNumber($viewName, 'pvpunitario-gt', 'pvp', 'pvpunitario');
        $this->addFilterNumber($viewName, 'pvpunitario-lt', 'pvp', 'pvpunitario', '<=');

        $this->addFilterNumber($viewName, 'pvptotal-gt', 'amount', 'pvptotal');
        $this->addFilterNumber($viewName, 'pvptotal-lt', 'amount', 'pvptotal', '<=');

        $this->addFilterCheckbox($viewName, 'no-ref', 'no-reference', 'referencia', 'IS', null);
        $this->addFilterCheckbox($viewName, 'recargo', 'surcharge', 'recargo', '!=', 0);
        $this->addFilterCheckbox($viewName, 'irpf', 'retention', 'irpf', '!=', 0);
        $this->addFilterCheckbox($viewName, 'suplido', 'supplied', 'suplido');

        // desactivamos los botones, checkboxes y mega-search
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'checkBoxes', false);
        $this->setSettings($viewName, 'megasearch', false);
    }

    protected function createViewPurchases(string $viewName, string $modelName, string $label)
    {
        $this->addView($viewName, $modelName, $label, 'fa-solid fa-copy')
            ->addOrderBy(['codigo'], 'code')
            ->addOrderBy(['fecha', $this->tableColToNumber('numero')], 'date', 2)
            ->addOrderBy([$this->tableColToNumber('numero')], 'number')
            ->addOrderBy(['numproveedor'], 'numsupplier')
            ->addOrderBy(['codproveedor'], 'supplier-code')
            ->addOrderBy(['total'], 'total')
            ->addSearchFields(['cifnif', 'codigo', 'nombre', 'numproveedor', 'observaciones']);

        // filtros
        $this->addCommonViewFilters($viewName, $modelName);
        $this->addFilterAutocomplete($viewName, 'codproveedor', 'supplier', 'codproveedor', 'Proveedor');
        $this->addFilterCheckbox($viewName, 'femail', 'email-not-sent', 'femail', 'IS', null);

        // asignamos los colores
        $this->addColorStatus($viewName, $modelName);
    }

    protected function createViewSales(string $viewName, string $modelName, string $label)
    {
        $this->addView($viewName, $modelName, $label, 'fa-solid fa-copy')
            ->addOrderBy(['codigo'], 'code')
            ->addOrderBy(['codcliente'], 'customer-code')
            ->addOrderBy(['fecha', $this->tableColToNumber('numero')], 'date', 2)
            ->addOrderBy([$this->tableColToNumber('numero')], 'number')
            ->addOrderBy(['numero2'], 'number2')
            ->addOrderBy(['total'], 'total')
            ->addSearchFields(['cifnif', 'codigo', 'codigoenv', 'nombrecliente', 'numero2', 'observaciones']);

        // filtros
        $this->addCommonViewFilters($viewName, $modelName);

        // filtramos por grupos de clientes
        $optionsGroup = [
            ['label' => Tools::lang()->trans('any-group'), 'where' => []],
            [
                'label' => Tools::lang()->trans('without-groups'),
                'where' => [new DataBaseWhere('codcliente', "SELECT DISTINCT codcliente FROM clientes WHERE codgrupo IS NULL", 'IN')]
            ],
            ['label' => '------', 'where' => []],
        ];
        foreach (GruposClientes::all() as $grupo) {
            $sqlGrupo = 'SELECT DISTINCT codcliente FROM clientes WHERE codgrupo = ' . $this->dataBase->var2str($grupo->codgrupo);
            $optionsGroup[] = [
                'label' => $grupo->nombre,
                'where' => [new DataBaseWhere('codcliente', $sqlGrupo, 'IN')]
            ];
        }
        if (count($optionsGroup) > 3) {
            $this->addFilterSelectWhere($viewName, 'codgrupo', $optionsGroup);
        }

        // filtramos por clientes y direcciones
        $this->addFilterAutocomplete($viewName, 'codcliente', 'customer', 'codcliente', 'Cliente');
        $this->addFilterAutocomplete($viewName, 'idcontactofact', 'billing-address', 'idcontactofact', 'contactos', 'idcontacto', 'direccion');
        $this->addFilterautocomplete($viewName, 'idcontactoenv', 'shipping-address', 'idcontactoenv', 'contactos', 'idcontacto', 'direccion');

        if ($this->permissions->onlyOwnerData === false) {
            $agents = Agentes::codeModel();
            if (count($agents) > 1) {
                $this->addFilterSelect($viewName, 'codagente', 'agent', 'codagente', $agents);
            }
        }

        $carriers = $this->codeModel->all('agenciastrans', 'codtrans', 'nombre');
        $this->addFilterSelect($viewName, 'codtrans', 'carrier', 'codtrans', $carriers);
        $this->addFilterCheckbox($viewName, 'femail', 'email-not-sent', 'femail', 'IS', null);

        // asignamos los colores
        $this->addColorStatus($viewName, $modelName);
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
        $codes = $this->request->request->getArray('codes');
        $model = $this->views[$this->active]->model;

        switch ($action) {
            case 'approve-document':
                return $this->approveDocumentAction($codes, $model, $allowUpdate, $this->dataBase);

            case 'approve-document-same-date':
                BusinessDocumentGenerator::setSameDate(true);
                return $this->approveDocumentAction($codes, $model, $allowUpdate, $this->dataBase);

            case 'generate-accounting-entries':
                return $this->generateAccountingEntriesAction($model, $allowUpdate, $this->dataBase);

            case 'group-document':
                return $this->groupDocumentAction($codes, $model);

            case 'lock-invoice':
                return $this->lockInvoiceAction($codes, $model, $allowUpdate, $this->dataBase);

            case 'pay-receipt':
                return $this->payReceiptAction($codes, $model, $allowUpdate, $this->dataBase, $this->user->nick);
        }

        return parent::execPreviousAction($action);
    }

    private function tableColToNumber(string $name): string
    {
        return strtolower(FS_DB_TYPE) == 'postgresql' ?
            'CAST(' . $name . ' as integer)' :
            'CAST(' . $name . ' as unsigned)';
    }
}
