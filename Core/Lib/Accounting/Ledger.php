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

namespace FacturaScripts\Core\Lib\Accounting;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\Partida;

/**
 * Description of Ledger
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author nazca                <comercial@nazcanetworks.com>
 */
class Ledger
{
    /** @var DataBase */
    protected $dataBase;

    /** @var string */
    protected $dateFrom;

    /** @var string */
    protected $dateTo;

    /** @var Ejercicio */
    protected $exercise;

    /** @var string */
    protected $format;

    public function __construct()
    {
        $this->dataBase = new DataBase();

        // needed dependencies
        new Partida();
    }

    public function generate(int $idcompany, string $dateFrom, string $dateTo, array $params = []): array
    {
        $this->exercise = new Ejercicio();
        $this->exercise->idempresa = $idcompany;
        if (false === $this->exercise->loadFromDate($dateFrom, false, false)) {
            return [];
        }

        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->format = $params['format'] ?? 'csv';
        $debe = $haber = 0.0;
        $ledger = [];

        switch ($params['grouped'] ?? '') {
            case 'C':
                // group by account
                $balances = [];
                foreach ($this->getDataGroupedByAccount($params) as $line) {
                    $this->processLineBalanceGroupedByAccount($balances, $ledger, $line);
                    $debe += (float)$line['debe'];
                    $haber += (float)$line['haber'];
                }
                $ledger['totals'] = [[
                    'debe' => $this->formatMoney($debe, true),
                    'haber' => $this->formatMoney($haber, true),
                    'saldo' => $this->formatMoney($debe - $haber, true)
                ]];
                break;

            case 'S':
                // group by subaccount
                $balances = [];
                foreach ($this->getDataGroupedBySubAccount($params) as $line) {
                    $this->processLineBalanceGroupedBySubAccount($balances, $ledger, $line);
                    $debe += (float)$line['debe'];
                    $haber += (float)$line['haber'];
                }
                $ledger['totals'] = [[
                    'debe' => $this->formatMoney($debe, true),
                    'haber' => $this->formatMoney($haber, true),
                    'saldo' => $this->formatMoney($debe - $haber, true)
                ]];
                break;

            default:
                // do not group data
                $ledger['lines'] = [];
                foreach ($this->getData($params) as $line) {
                    $this->processLine($ledger['lines'], $line, $params);
                    $debe += (float)$line['debe'];
                    $haber += (float)$line['haber'];
                }
                $ledger['lines'][] = [
                    'asiento' => '',
                    'fecha' => '',
                    'concepto' => '',
                    'debe' => $this->formatMoney($debe, true),
                    'haber' => $this->formatMoney($haber, true),
                    'saldo' => $this->formatMoney($debe - $haber, true)
                ];
                break;
        }

        return $ledger;
    }

    protected function formatMoney(float $value, bool $bold): string
    {
        $decimals = Tools::settings('default', 'decimals', 2);
        $decimalSep = Tools::settings('default', 'decimal_separator', ',');
        $thousandsSep = Tools::settings('default', 'thousands_separator', ' ');

        if ($this->format != 'PDF') {
            return number_format($value, $decimals, '.', '');
        }

        return $bold ?
            '<b>' . number_format($value, $decimals, $decimalSep, $thousandsSep) . '</b>' :
            number_format($value, $decimals, $decimalSep, $thousandsSep);
    }

    protected function getData(array $params = []): array
    {
        if (false === $this->dataBase->tableExists('partidas')) {
            return [];
        }

        $sql = 'SELECT asientos.numero, asientos.fecha, partidas.codsubcuenta,'
            . ' partidas.concepto, partidas.debe, partidas.haber, partidas.saldo,'
            . ' subcuentas.codcuenta, subcuentas.descripcion as subcuentadesc,'
            . ' cuentas.descripcion as cuentadesc'
            . ' FROM partidas'
            . ' LEFT JOIN asientos ON partidas.idasiento = asientos.idasiento'
            . ' LEFT JOIN subcuentas ON subcuentas.idsubcuenta = partidas.idsubcuenta'
            . ' LEFT JOIN cuentas ON cuentas.idcuenta = subcuentas.idcuenta'
            . ' WHERE ' . $this->getDataWhere($params)
            . ' ORDER BY asientos.numero, partidas.codsubcuenta ASC';
        return $this->dataBase->select($sql);
    }

    protected function getDataGroupedByAccount(array $params = []): array
    {
        if (false === $this->dataBase->tableExists('partidas')) {
            return [];
        }

        $sql = 'SELECT asientos.numero, asientos.fecha, partidas.codsubcuenta,'
            . ' partidas.concepto, partidas.debe, partidas.haber, partidas.saldo,'
            . ' subcuentas.codcuenta, subcuentas.descripcion as subcuentadesc,'
            . ' cuentas.descripcion as cuentadesc'
            . ' FROM partidas'
            . ' LEFT JOIN asientos ON partidas.idasiento = asientos.idasiento'
            . ' LEFT JOIN subcuentas ON subcuentas.idsubcuenta = partidas.idsubcuenta'
            . ' LEFT JOIN cuentas ON cuentas.idcuenta = subcuentas.idcuenta'
            . ' WHERE ' . $this->getDataWhere($params)
            . ' ORDER BY cuentas.codcuenta, asientos.numero ASC';
        return $this->dataBase->select($sql);
    }

    protected function getDataGroupedBySubAccount(array $params = []): array
    {
        if (false === $this->dataBase->tableExists('partidas')) {
            return [];
        }

        $sql = 'SELECT asientos.numero, asientos.fecha, partidas.codsubcuenta,'
            . ' partidas.concepto, partidas.debe, partidas.haber, partidas.saldo,'
            . ' subcuentas.codcuenta, subcuentas.descripcion as subcuentadesc,'
            . ' cuentas.descripcion as cuentadesc'
            . ' FROM partidas'
            . ' LEFT JOIN asientos ON partidas.idasiento = asientos.idasiento'
            . ' LEFT JOIN subcuentas ON subcuentas.idsubcuenta = partidas.idsubcuenta'
            . ' LEFT JOIN cuentas ON cuentas.idcuenta = subcuentas.idcuenta'
            . ' WHERE ' . $this->getDataWhere($params)
            . ' ORDER BY partidas.codsubcuenta, asientos.numero ASC';

        return $this->dataBase->select($sql);
    }

    protected function getDataWhere(array $params = []): string
    {
        $where = 'asientos.codejercicio = ' . $this->dataBase->var2str($this->exercise->codejercicio)
            . ' AND asientos.fecha BETWEEN ' . $this->dataBase->var2str($this->dateFrom)
            . ' AND ' . $this->dataBase->var2str($this->dateTo);

        $channel = $params['channel'] ?? '';
        if (!empty($channel)) {
            $where .= ' AND asientos.canal = ' . $this->dataBase->var2str($channel);
        }

        $accountFrom = $params['account-from'] ?? '';
        $accountTo = $params['account-to'] ?? $accountFrom;
        if (!empty($accountFrom) || !empty($accountTo)) {
            $where .= ' AND subcuentas.codcuenta BETWEEN ' . $this->dataBase->var2str($accountFrom)
                . ' AND ' . $this->dataBase->var2str($accountTo);
        }

        $subaccountFrom = $params['subaccount-from'] ?? '';
        $subaccountTo = $params['subaccount-to'] ?? $subaccountFrom;
        if (!empty($subaccountFrom) || !empty($subaccountTo)) {
            $where .= ' AND partidas.codsubcuenta BETWEEN ' . $this->dataBase->var2str($subaccountFrom)
                . ' AND ' . $this->dataBase->var2str($subaccountTo);
        }

        $entryFrom = $params['entry-from'] ?? '';
        $entryTo = $params['entry-to'] ?? $entryFrom;
        if (!empty($entryFrom) || !empty($entryTo)) {
            $where .= ' AND asientos.numero BETWEEN ' . $this->dataBase->var2str($entryFrom)
                . ' AND ' . $this->dataBase->var2str($entryTo);
        }

        return $where;
    }

    protected function getCuentaBalance(string $codcuenta): float
    {
        $sql = 'SELECT SUM(partidas.debe) as debe, SUM(partidas.haber) as haber'
            . ' FROM partidas'
            . ' LEFT JOIN asientos ON partidas.idasiento = asientos.idasiento'
            . ' LEFT JOIN subcuentas ON subcuentas.idsubcuenta = partidas.idsubcuenta'
            . ' LEFT JOIN cuentas ON cuentas.idcuenta = subcuentas.idcuenta'
            . ' WHERE cuentas.codcuenta = ' . $this->dataBase->var2str($codcuenta)
            . ' AND asientos.codejercicio = ' . $this->dataBase->var2str($this->exercise->codejercicio)
            . ' AND asientos.fecha < ' . $this->dataBase->var2str($this->dateFrom);
        foreach ($this->dataBase->select($sql) as $row) {
            return (float)$row['debe'] - (float)$row['haber'];
        }

        return 0.00;
    }

    protected function processLine(array &$ledger, array $line, array $params): void
    {
        $line = [
            'asiento' => $line['numero'],
            'fecha' => Tools::date($line['fecha']),
            'cuenta' => $line['codsubcuenta'],
            'concepto' => Tools::fixHtml($line['concepto']),
            'debe' => $this->formatMoney($line['debe'], false),
            'haber' => $this->formatMoney($line['haber'], false),
            'saldo' => $this->formatMoney($line['saldo'], false)
        ];

        // si estamos filtrando por subcuenta, quitamos la columna de cuenta
        if (!empty($params['subaccount-from'])) {
            unset($line['cuenta']);
        } else {
            // si no estamos filtrando por subcuenta, quitamos la columna de saldo
            unset($line['saldo']);
        }

        $ledger[] = $line;
    }

    protected function processLineBalanceGroupedByAccount(array &$balances, array &$ledger, array $line)
    {
        $codcuenta = $line['codcuenta'];
        if (!isset($balances[$codcuenta])) {
            $balances[$codcuenta] = $this->getCuentaBalance($codcuenta);
        }

        if (!isset($ledger[$codcuenta])) {
            $ledger[$codcuenta][] = [
                'asiento' => '',
                'fecha' => Tools::date($this->dateFrom),
                'cuenta' => $codcuenta,
                'concepto' => Tools::fixHtml($line['cuentadesc']),
                'debe' => $this->formatMoney(0, false),
                'haber' => $this->formatMoney(0, false),
                'saldo' => $this->formatMoney($balances[$codcuenta], false)
            ];
        }

        $balances[$codcuenta] += (float)$line['debe'] - (float)$line['haber'];
        $ledger[$codcuenta][] = [
            'asiento' => $line['numero'],
            'fecha' => Tools::date($line['fecha']),
            'cuenta' => $codcuenta,
            'concepto' => Tools::fixHtml($line['concepto']),
            'debe' => $this->formatMoney($line['debe'], false),
            'haber' => $this->formatMoney($line['haber'], false),
            'saldo' => $this->formatMoney($balances[$codcuenta], false)
        ];
    }

    protected function processLineBalanceGroupedBySubAccount(array &$balances, array &$ledger, array $line)
    {
        $codcuenta = $line['codsubcuenta'];
        if (!isset($balances[$codcuenta])) {
            $balances[$codcuenta] = $this->getCuentaBalance($codcuenta);
        }

        if (!isset($ledger[$codcuenta])) {
            $ledger[$codcuenta][] = [
                'asiento' => '',
                'fecha' => Tools::date($this->dateFrom),
                'cuenta' => $codcuenta,
                'concepto' => Tools::fixHtml($line['subcuentadesc']),
                'debe' => $this->formatMoney(0, false),
                'haber' => $this->formatMoney(0, false),
                'saldo' => $this->formatMoney($balances[$codcuenta], false)
            ];
        }

        $balances[$codcuenta] += (float)$line['debe'] - (float)$line['haber'];
        $ledger[$codcuenta][] = [
            'asiento' => $line['numero'],
            'fecha' => Tools::date($line['fecha']),
            'cuenta' => $codcuenta,
            'concepto' => Tools::fixHtml($line['concepto']),
            'debe' => $this->formatMoney($line['debe'], false),
            'haber' => $this->formatMoney($line['haber'], false),
            'saldo' => $this->formatMoney($balances[$codcuenta], false)
        ];
    }
}
