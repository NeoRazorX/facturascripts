<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Lib\ExtendedController\ListBusinessDocument;

/**
 * Controller to list the items in the FacturaCliente model
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Raul Jimenez                  <raul.jimenez@nazcanetworks.com>
 * @author Cristo M. Estévez Hernández   <cristom.estevez@gmail.com>
 */
class ListFacturaCliente extends ListBusinessDocument
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'invoices';
        $data['icon'] = 'fas fa-file-invoice-dollar';
        return $data;
    }

    protected function createViews()
    {
        // listado de facturas de cliente
        $this->createViewSales('ListFacturaCliente', 'FacturaCliente', 'invoices');

        // si el usuario solamente tiene permiso para ver lo suyo, no añadimos el resto de pestañas
        if ($this->permissions->onlyOwnerData) {
            return;
        }

        // líneas de facturas de cliente
        $this->createViewLines('ListLineaFacturaCliente', 'LineaFacturaCliente');

        // recibos de cliente
        $this->createViewReceipts();

        // facturas rectificativas
        $this->createViewRefunds();
    }

    protected function createViewReceipts(string $viewName = 'ListReciboCliente')
    {
        $this->addView($viewName, 'ReciboCliente', 'receipts', 'fas fa-dollar-sign');
        $this->addOrderBy($viewName, ['fecha', 'idrecibo'], 'date', 2);
        $this->addOrderBy($viewName, ['fechapago'], 'payment-date');
        $this->addOrderBy($viewName, ['vencimiento'], 'expiration');
        $this->addOrderBy($viewName, ['importe'], 'amount');
        $this->addSearchFields($viewName, ['codigofactura', 'observaciones']);

        // filtros
        $this->addFilterPeriod($viewName, 'date', 'period', 'fecha');
        $this->addFilterAutocomplete($viewName, 'codcliente', 'customer', 'codcliente', 'Cliente');
        $this->addFilterNumber($viewName, 'min-total', 'amount', 'importe', '>=');
        $this->addFilterNumber($viewName, 'max-total', 'amount', 'importe', '<=');
        $this->addFilterCheckbox($viewName, 'pagado', 'unpaid', '', '!=');

        $currencies = Divisas::codeModel();
        if (count($currencies) > 2) {
            $this->addFilterSelect($viewName, 'coddivisa', 'currency', 'coddivisa', $currencies);
        }

        $payMethods = FormasPago::codeModel();
        if (count($payMethods) > 2) {
            $this->addFilterSelect($viewName, 'codpago', 'payment-method', 'codpago', $payMethods);
        }

        // botones
        $this->addButtonPayReceipt($viewName);

        // desactivamos el botón nuevo
        $this->setSettings($viewName, 'btnNew', false);
    }

    protected function createViewRefunds(string $viewName = 'ListFacturaCliente-rect')
    {
        $this->addView($viewName, 'FacturaCliente', 'refunds', 'fas fa-share-square');
        $this->addSearchFields($viewName, ['codigo', 'codigorect', 'numero2', 'observaciones']);
        $this->addOrderBy($viewName, ['fecha', 'idfactura'], 'date', 2);
        $this->addOrderBy($viewName, ['total'], 'total');

        // filtro de fecha
        $this->addFilterPeriod($viewName, 'date', 'period', 'fecha');

        // añadimos un filtro select where para forzar las que tienen idfacturarect
        $this->addFilterSelectWhere($viewName, 'idfacturarect', [
            [
                'label' => self::toolBox()::i18n()->trans('rectified-invoices'),
                'where' => [new DataBaseWhere('idfacturarect', null, 'IS NOT')]
            ]
        ]);

        // desactivamos el botón nuevo
        $this->setSettings($viewName, 'btnNew', false);

        // mostramos la columna original
        $this->views[$viewName]->disableColumn('original', false);
    }

    protected function createViewSales(string $viewName, string $modelName, string $label)
    {
        parent::createViewSales($viewName, $modelName, $label);
        $this->addSearchFields($viewName, ['codigorect']);

        // filtros
        $this->addFilterCheckbox('ListFacturaCliente', 'pagada', 'unpaid', 'pagada', '=', false);
        $this->addFilterCheckbox('ListFacturaCliente', 'idasiento', 'invoice-without-acc-entry', 'idasiento', 'IS', null);
        $this->addButtonLockInvoice('ListFacturaCliente');
    }
}
