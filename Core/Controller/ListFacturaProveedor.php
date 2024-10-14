<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

    protected function createViews()
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

    protected function createViewPurchases(string $viewName, string $modelName, string $label)
    {
        parent::createViewPurchases($viewName, $modelName, $label);
        $this->addSearchFields($viewName, ['codigorect']);

        // filtros
        $i18n = Tools::lang();
        $this->addFilterSelectWhere($viewName, 'status', [
            ['label' => $i18n->trans('paid-or-unpaid'), 'where' => []],
            ['label' => $i18n->trans('paid'), 'where' => [new DataBaseWhere('pagada', true)]],
            ['label' => $i18n->trans('unpaid'), 'where' => [new DataBaseWhere('pagada', false)]],
            ['label' => $i18n->trans('expired-receipt'), 'where' => [new DataBaseWhere('vencida', true)]],
        ]);
        $this->addFilterCheckbox($viewName, 'idasiento', 'invoice-without-acc-entry', 'idasiento', 'IS', null);

        // botones
        $this->addButtonLockInvoice($viewName);
        $this->addButtonGenerateAccountingInvoices($viewName);

        if ($this->user->admin) {
            $this->addButton($viewName, [
                'action' => 'renumber-invoices',
                'icon' => 'fa-solid fa-sort-numeric-down',
                'label' => 'renumber',
                'type' => 'modal'
            ]);
        }
    }

    protected function createViewReceipts(string $viewName = 'ListReciboProveedor')
    {
        $this->addView($viewName, 'ReciboProveedor', 'receipts', 'fa-solid fa-dollar-sign');
        $this->addOrderBy($viewName, ['codproveedor'], 'supplier-code');
        $this->addOrderBy($viewName, ['fecha', 'idrecibo'], 'date');
        $this->addOrderBy($viewName, ['fechapago'], 'payment-date');
        $this->addOrderBy($viewName, ['vencimiento'], 'expiration', 2);
        $this->addOrderBy($viewName, ['importe'], 'amount');
        $this->addSearchFields($viewName, ['codigofactura', 'observaciones']);

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

        $i18n = Tools::lang();
        $this->addFilterSelectWhere($viewName, 'status', [
            ['label' => $i18n->trans('paid-or-unpaid'), 'where' => []],
            ['label' => $i18n->trans('paid'), 'where' => [new DataBaseWhere('pagado', true)]],
            ['label' => $i18n->trans('unpaid'), 'where' => [new DataBaseWhere('pagado', false)]],
            ['label' => $i18n->trans('expired-receipt'), 'where' => [new DataBaseWhere('vencido', true)]],
        ]);
        $this->addFilterPeriod($viewName, 'payment-date', 'payment-date', 'fechapago');

        // botones
        $this->addButtonPayReceipt($viewName);

        // desactivamos el botón nuevo
        $this->setSettings($viewName, 'btnNew', false);
    }

    protected function createViewRefunds(string $viewName = 'ListFacturaProveedor-rect')
    {
        $this->addView($viewName, 'FacturaProveedor', 'refunds', 'fa-solid fa-share-square');
        $this->addSearchFields($viewName, ['codigo', 'codigorect', 'numproveedor', 'observaciones']);
        $this->addOrderBy($viewName, ['fecha', 'idfactura'], 'date', 2);
        $this->addOrderBy($viewName, ['total'], 'total');

        // filtro de fecha
        $this->addFilterPeriod($viewName, 'date', 'period', 'fecha');

        // añadimos un filtro select where para forzar las que tienen idfacturarect
        $this->addFilterSelectWhere($viewName, 'idfacturarect', [
            [
                'label' => Tools::lang()->trans('rectified-invoices'),
                'where' => [new DataBaseWhere('idfacturarect', null, 'IS NOT')]
            ]
        ]);

        // desactivamos el botón nuevo
        $this->setSettings($viewName, 'btnNew', false);

        // mostramos la columna original
        $this->views[$viewName]->disableColumn('original', false);
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

        $codejercicio = $this->request->request->get('exercise');
        if (FacturaProveedorRenumber::run($codejercicio)) {
            Tools::log('facturasprov')->notice('renumber-invoices-success', ['%exercise%' => $codejercicio]);
            Tools::log()->notice('renumber-invoices-success', ['%exercise%' => $codejercicio]);
            return;
        }

        Tools::log()->warning('record-save-error');
    }
}
