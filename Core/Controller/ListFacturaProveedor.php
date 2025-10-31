<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\DataSrc\Divisas;
use FacturaScripts\Core\DataSrc\FormasPago;
use FacturaScripts\Core\Lib\FacturaProveedorRenumber;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\ExtendedController\ListBusinessDocument;

/**
 * Controller to list the items in the FacturaProveedor model
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Raul Jimenez                  <raul.jimenez@nazcanetworks.com>
 * @author Cristo M. Estévez Hernández   <cristom.estevez@gmail.com>
 */
class ListFacturaProveedor extends ListBusinessDocument
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'purchases';
        $data['title'] = 'invoices';
        $data['icon'] = 'fa-solid fa-file-invoice-dollar';
        return $data;
    }

    protected function createViews(): void
    {
        // listado de facturas de proveedor
        $this->createViewPurchases('ListFacturaProveedor', 'FacturaProveedor', 'invoices');

        // si el usuario solamente tiene permiso para ver lo suyo, no añadimos el resto de pestañas
        if ($this->permissions->onlyOwnerData) {
            return;
        }

        // líneas de facturas de proveedor
        $this->createViewLines('ListLineaFacturaProveedor', 'LineaFacturaProveedor');

        // recibos de proveedor
        $this->createViewReceipts();

        // facturas rectificativas
        $this->createViewRefunds();
    }

    protected function createViewPurchases(string $viewName, string $modelName, string $label): void
    {
        parent::createViewPurchases($viewName, $modelName, $label);

        $this->listView($viewName)->addSearchFields(['codigorect']);

        // filtros
        $this->addFilterSelectWhere($viewName, 'status', [
            ['label' => Tools::trans('paid-or-unpaid'), 'where' => []],
            ['label' => Tools::trans('paid'), 'where' => [new DataBaseWhere('pagada', true)]],
            ['label' => Tools::trans('unpaid'), 'where' => [new DataBaseWhere('pagada', false)]],
            ['label' => Tools::trans('expired-receipt'), 'where' => [new DataBaseWhere('vencida', true)]],
        ]);
        $this->addFilterCheckbox($viewName, 'idasiento', 'invoice-without-acc-entry', 'idasiento', 'IS', null);

        // botones
        $this->addButtonLockInvoice($viewName);
        $this->addButtonGenerateAccountingInvoices($viewName);
        $this->addButtonPayInvoice($viewName);

        if ($this->user->admin) {
            $this->addButton($viewName, [
                'action' => 'renumber-invoices',
                'icon' => 'fa-solid fa-sort-numeric-down',
                'label' => 'renumber',
                'type' => 'modal'
            ]);
        }
    }

    protected function createViewReceipts(string $viewName = 'ListReciboProveedor'): void
    {
        $this->addView($viewName, 'ReciboProveedor', 'receipts', 'fa-solid fa-dollar-sign')
            ->addOrderBy(['codproveedor'], 'supplier-code')
            ->addOrderBy(['fecha', 'idrecibo'], 'date')
            ->addOrderBy(['fechapago'], 'payment-date')
            ->addOrderBy(['vencimiento'], 'expiration', 2)
            ->addOrderBy(['importe'], 'amount')
            ->addSearchFields(['codigofactura', 'observaciones'])
            ->setSettings('btnNew', false);

        // filtros
        $this->addFilterPeriod($viewName, 'expiration', 'expiration', 'vencimiento');
        $this->addFilterAutocomplete($viewName, 'codproveedor', 'supplier', 'codproveedor', 'Proveedor');
        $this->addFilterNumber($viewName, 'min-total', 'amount', 'importe', '>=');
        $this->addFilterNumber($viewName, 'max-total', 'amount', 'importe', '<=');

        $currencies = Divisas::codeModel();
        if (count($currencies) > 2) {
            $this->addFilterSelect($viewName, 'coddivisa', 'currency', 'coddivisa', $currencies);
        }

        $payMethods = FormasPago::codeModel();
        if (count($payMethods) > 2) {
            $this->addFilterSelect($viewName, 'codpago', 'payment-method', 'codpago', $payMethods);
        }

        $this->addFilterSelectWhere($viewName, 'status', [
            ['label' => Tools::trans('paid-or-unpaid'), 'where' => []],
            ['label' => Tools::trans('paid'), 'where' => [new DataBaseWhere('pagado', true)]],
            ['label' => Tools::trans('unpaid'), 'where' => [new DataBaseWhere('pagado', false)]],
            ['label' => Tools::trans('expired-receipt'), 'where' => [new DataBaseWhere('vencido', true)]],
        ]);
        $this->addFilterPeriod($viewName, 'payment-date', 'payment-date', 'fechapago');

        // botones
        $this->addButtonPayReceipt($viewName);
    }

    protected function createViewRefunds(string $viewName = 'ListFacturaProveedor-rect'): void
    {
        $this->addView($viewName, 'FacturaProveedor', 'refunds', 'fa-solid fa-share-square')
            ->addSearchFields(['codigo', 'codigorect', 'numproveedor', 'observaciones'])
            ->addOrderBy(['fecha', 'idfactura'], 'date', 2)
            ->addOrderBy(['total'], 'total')
            ->disableColumn('original', false)
            ->setSettings('btnNew', false);

        // filtro de fecha
        $this->addFilterPeriod($viewName, 'date', 'period', 'fecha');

        // añadimos un filtro select where para forzar las que tienen idfacturarect
        $this->addFilterSelectWhere($viewName, 'idfacturarect', [
            [
                'label' => Tools::trans('rectified-invoices'),
                'where' => [new DataBaseWhere('idfacturarect', null, 'IS NOT')]
            ]
        ]);
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
        if ($action == 'renumber-invoices') {
            $this->renumberInvoicesAction();
            return true;
        }

        return parent::execPreviousAction($action);
    }

    protected function renumberInvoicesAction(): void
    {
        if (false === $this->user->admin) {
            Tools::log()->warning('not-allowed-modify');
            return;
        } elseif (false === $this->validateFormToken()) {
            return;
        }

        $codejercicio = $this->request->input('exercise');
        if (FacturaProveedorRenumber::run($codejercicio)) {
            Tools::log('facturasprov')->notice('renumber-invoices-success', ['%exercise%' => $codejercicio]);
            Tools::log()->notice('renumber-invoices-success', ['%exercise%' => $codejercicio]);
            return;
        }

        Tools::log()->warning('record-save-error');
    }
}
