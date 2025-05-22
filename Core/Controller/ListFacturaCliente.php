<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\DataSrc\FormasPago;
use FacturaScripts\Core\DataSrc\Series;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\ExtendedController\ListBusinessDocument;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\SecuenciaDocumento;

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
        $data['icon'] = 'fa-solid fa-file-invoice-dollar';
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
        $this->addView($viewName, 'ReciboCliente', 'receipts', 'fa-solid fa-dollar-sign')
            ->addOrderBy(['codcliente'], 'customer-code')
            ->addOrderBy(['fecha', 'idrecibo'], 'date')
            ->addOrderBy(['fechapago'], 'payment-date')
            ->addOrderBy(['vencimiento'], 'expiration', 2)
            ->addOrderBy(['importe'], 'amount')
            ->addSearchFields(['codigofactura', 'observaciones']);

        // filtros
        $this->addFilterPeriod($viewName, 'expiration', 'expiration', 'vencimiento');
        $this->addFilterAutocomplete($viewName, 'codcliente', 'customer', 'codcliente', 'Cliente');
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

    protected function createViewRefunds(string $viewName = 'ListFacturaCliente-rect')
    {
        $this->addView($viewName, 'FacturaCliente', 'refunds', 'fa-solid fa-share-square')
            ->addSearchFields(['codigo', 'codigorect', 'numero2', 'observaciones'])
            ->addOrderBy(['fecha', 'idfactura'], 'date', 2)
            ->addOrderBy(['total'], 'total');

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

    protected function createViewSales(string $viewName, string $modelName, string $label)
    {
        parent::createViewSales($viewName, $modelName, $label);

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

        // añadimos botón de bloquear facturas
        $this->addButtonLockInvoice($viewName);
        $this->addButtonGenerateAccountingInvoices($viewName);

        // añadimos botón para buscar huecos en las facturas, si el usuario tiene permiso
        if (false === $this->permissions->onlyOwnerData) {
            $this->addButton($viewName, [
                'action' => 'look-for-gaps',
                'icon' => 'fa-solid fa-exclamation-triangle',
                'label' => 'look-for-gaps'
            ]);
        }
    }

    protected function execAfterAction($action)
    {
        parent::execAfterAction($action);
        if ($action === 'look-for-gaps') {
            $this->lookForGapsAction();
        }
    }

    protected function lookForGaps(SecuenciaDocumento $sequence): array
    {
        $gaps = [];
        $number = $sequence->inicio;

        // buscamos todas las facturas de cliente de la secuencia
        $invoiceModel = new FacturaCliente();
        $where = [
            new DataBaseWhere('codserie', $sequence->codserie),
            new DataBaseWhere('idempresa', $sequence->idempresa)
        ];
        if ($sequence->codejercicio) {
            $where[] = new DataBaseWhere('codejercicio', $sequence->codejercicio);
        }
        $orderBy = strtolower(FS_DB_TYPE) == 'postgresql' ?
            ['CAST(numero as integer)' => 'ASC'] :
            ['CAST(numero as unsigned)' => 'ASC'];
        foreach ($invoiceModel->all($where, $orderBy, 0, 0) as $invoice) {
            // si el número de la factura es menor que el de la secuencia, saltamos
            if ($invoice->numero < $sequence->inicio) {
                continue;
            }

            // si el número de la factura es el esperado, actualizamos el número esperado
            if ($invoice->numero == $number) {
                $number++;
                continue;
            }

            // si el número de la factura es mayor que el esperado, añadimos huecos hasta el número
            while ($invoice->numero > $number) {
                $gaps[] = [
                    'codserie' => $invoice->codserie,
                    'numero' => $number,
                    'fecha' => $invoice->fecha,
                    'idempresa' => $invoice->idempresa
                ];
                $number++;
            }
            $number++;
        }

        return $gaps;
    }

    protected function lookForGapsAction(): void
    {
        $gaps = [];

        // buscamos todas las secuencias de facturas de cliente que usen huecos
        $sequenceModel = new SecuenciaDocumento();
        $where = [
            new DataBaseWhere('tipodoc', 'FacturaCliente'),
            new DataBaseWhere('usarhuecos', true)
        ];
        foreach ($sequenceModel->all($where, [], 0, 0) as $sequence) {
            $gaps = array_merge($gaps, $this->lookForGaps($sequence));
        }

        // si no hemos encontrado huecos, mostramos un mensaje
        if (empty($gaps)) {
            Tools::log()->notice('no-gaps-found');
            return;
        }

        // si hemos encontrado huecos, los mostramos uno a uno
        foreach ($gaps as $gap) {
            Tools::log()->warning('gap-found', [
                '%codserie%' => Series::get($gap['codserie'])->descripcion,
                '%numero%' => $gap['numero'],
                '%fecha%' => $gap['fecha'],
                '%idempresa%' => Empresas::get($gap['idempresa'])->nombrecorto
            ]);
        }
    }
}
