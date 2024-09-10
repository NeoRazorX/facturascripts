<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2022-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\Export;

use FacturaScripts\Core\Lib\Widget\VisualItemLoadEngine;
use FacturaScripts\Core\Model\Asiento;
use FacturaScripts\Core\Model\PageOption;
use FacturaScripts\Core\Model\Partida;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\ExportManager;

class AsientoExport
{
    private static $debe = 0;
    private static $haber = 0;
    private static $saldo = 0;

    public static function show(Asiento $asiento, string $option, string $title, int $idformat, string $langcode, &$response): void
    {
        $exportManager = new ExportManager();
        $exportManager->newDoc($option, $title, $idformat, $langcode);

        // imprimimos la cabecera del asiento
        $pageOption = new PageOption();
        $columns = [];
        $modals = [];
        $rows = [];
        VisualItemLoadEngine::installXML('EditAsiento', $pageOption);
        VisualItemLoadEngine::loadArray($columns, $modals, $rows, $pageOption);
        $exportManager->addModelPage($asiento, $columns, $title);

        // imprimimos las líneas del asiento
        self::addLines($asiento, $exportManager);

        // imprimimos los datos de IVA
        self::addTaxData($asiento, $exportManager);

        // imprimimos los totales
        self::addTotals($exportManager);

        $exportManager->show($response);
    }

    /**
     * @param Asiento $asiento
     * @param ExportManager $exportManager
     * @return void
     */
    private static function addLines(Asiento $asiento, ExportManager $exportManager): void
    {
        $i18n = Tools::lang();
        $header = [
            $i18n->trans('subaccount'),
            $i18n->trans('concept'),
            $i18n->trans('debit'),
            $i18n->trans('credit'),
            $i18n->trans('balance'),
            $i18n->trans('counterpart'),
        ];
        $options = [
            $header[2] => ['display' => 'right', 'css' => 'nowrap'],
            $header[3] => ['display' => 'right', 'css' => 'nowrap'],
            $header[4] => ['display' => 'right', 'css' => 'nowrap'],
        ];

        self::$debe = 0.00;
        self::$haber = 0.00;
        self::$saldo = 0.00;
        $data = [];
        foreach ($asiento->getLines() as $line) {
            self::$saldo += $line->debe - $line->haber;
            $data[] = [
                $header[0] => $line->codsubcuenta,
                $header[1] => $line->concepto,
                $header[2] => Tools::money($line->debe) . ' ',
                $header[3] => Tools::money($line->haber) . ' ',
                $header[4] => Tools::money(self::$saldo, true) . ' ',
                $header[5] => $line->codcontrapartida,
            ];
            self::$debe += $line->debe;
            self::$haber += $line->haber;
        }

        $title = '<strong>' . $i18n->trans('lines') . '</strong>';
        $exportManager->addTablePage($header, $data, $options, $title);
    }

    private static function addTaxData(Asiento $asiento, ExportManager $exportManager)
    {
        $i18n = Tools::lang();
        $header = [
            $i18n->trans('serie'),
            $i18n->trans('invoice'),
            $i18n->trans('vat-document'),
            $i18n->trans('cifnif'),
            $i18n->trans('tax-base'),
            $i18n->trans('pct-vat'),
            $i18n->trans('imp-vat'),
            $i18n->trans('pct-surcharge'),
            $i18n->trans('imp-surcharge'),
        ];

        $data = [];
        foreach ($asiento->getLines() as $line) {
            if (false === self::hasVatRegister($line)) {
                continue;
            }
            $data[] = [
                $header[0] => $line->codserie,
                $header[1] => $line->factura,
                $header[2] => $line->documento,
                $header[3] => $line->cifnif,
                $header[4] => Tools::money($line->baseimponible),
                $header[5] => $line->iva,
                $header[6] => Tools::money($line->baseimponible * ($line->iva / 100)) . ' ',
                $header[7] => $line->recargo,
                $header[8] => Tools::money($line->baseimponible * ($line->recargo / 100)) . ' ',
            ];
        }
        if (empty($data)) {
            return;
        }

        $options = [
            $header[4] => ['display' => 'right', 'css' => 'nowrap'],
            $header[5] => ['display' => 'center', 'css' => 'nowrap'],
            $header[6] => ['display' => 'right', 'css' => 'nowrap'],
            $header[7] => ['display' => 'center', 'css' => 'nowrap'],
            $header[8] => ['display' => 'right', 'css' => 'nowrap'],
        ];

        $title = '<strong>' . $i18n->trans('VAT-register') . '</strong>';
        $exportManager->addTablePage($header, $data, $options, $title);
    }

    private static function addTotals(ExportManager &$exportManager)
    {
        $i18n = Tools::lang();
        $header = [
            $i18n->trans('debit'),
            $i18n->trans('credit'),
            $i18n->trans('difference'),
        ];
        $data = [
            $header[0] => Tools::money(self::$debe) . ' ',
            $header[1] => Tools::money(self::$haber) . ' ',
            $header[2] => Tools::money(self::$saldo) . ' '
        ];
        $options = [
            $header[0] => ['display' => 'center'],
            $header[1] => ['display' => 'center'],
            $header[2] => ['display' => 'center']
        ];
        $title = '<strong>' . $i18n->trans('totals') . '</strong>';
        $exportManager->addTablePage($header, [$data], $options, $title);
    }

    private static function hasVatRegister(Partida &$line): bool
    {
        return false === empty($line->baseimponible)
            || false === empty($line->iva)
            || false === empty($line->recargo)
            || false === empty($line->codserie)
            || false === empty($line->factura);
    }
}
