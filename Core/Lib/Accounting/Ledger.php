<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Dinamic\Model\Partida;

/**
 * Description of Ledger
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author nazca                <comercial@nazcanetworks.com>
 */
class Ledger extends AccountingBase
{

    /**
     * Ledger constructor class
     */
    public function __construct()
    {
        parent::__construct();

        // needed dependencies
        new Partida();
    }

    /**
     * Generate the ledger between two dates.
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param array $params
     *
     * @return array
     */
    public function generate(string $dateFrom, string $dateTo, array $params = [])
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $debe = $haber = 0.0;
        $ledger = [];

        switch ($params['grouped']) {
            case 'C':
                // group by account
                $balances = [];
                foreach ($this->getDataGroupedByAccount($params) as $line) {
                    $this->processLineBalanceGroupedByAccount($balances, $ledger, $line);
                    $debe += (float)$line['debe'];
                    $haber += (float)$line['haber'];
                }
                $ledger['totals'] = [[
                    'debe' => '<b>' . $this->toolBox()->coins()->format($debe, FS_NF0, '') . '</b>',
                    'haber' => '<b>' . $this->toolBox()->coins()->format($haber, FS_NF0, '') . '</b>',
                    'saldo' => '<b>' . $this->toolBox()->coins()->format($debe - $haber, FS_NF0, '') . '</b>'
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
                    'debe' => '<b>' . $this->toolBox()->coins()->format($debe, FS_NF0, '') . '</b>',
                    'haber' => '<b>' . $this->toolBox()->coins()->format($haber, FS_NF0, '') . '</b>',
                    'saldo' => '<b>' . $this->toolBox()->coins()->format($debe - $haber, FS_NF0, '') . '</b>'
                ]];
                break;

            default:
                // do not group data
                foreach ($this->getData($params) as $line) {
                    $this->processLine($ledger['lines'], $line);
                    $debe += (float)$line['debe'];
                    $haber += (float)$line['haber'];
                }
                $ledger['lines'][] = [
                    'asiento' => '',
                    'fecha' => '',
                    'cuenta' => '',
                    'concepto' => '',
                    'debe' => '<b>' . $this->toolBox()->coins()->format($debe, FS_NF0, '') . '</b>',
                    'haber' => '<b>' . $this->toolBox()->coins()->format($haber, FS_NF0, '') . '</b>'
                ];
                break;
        }

        return $ledger;
    }

    /**
     * Config options for create a ledger button
     *
     * @param string $type
     * @param string $action
     *
     * @return array
     */
    public static function getButton(string $type, string $action = 'ledger'): array
    {
        return [
            'action' => $action,
            'color' => 'info',
            'icon' => 'fas fa-book fa-fw',
            'label' => 'ledger',
            'type' => $type
        ];
    }

    /**
     * Return the appropriate data from database.
     *
     * @return array
     */
    protected function getData(array $params = [])
    {
        if (false === $this->dataBase->tableExists('partidas')) {
            return [];
        }

        $sql = 'SELECT asientos.numero, asientos.fecha, partidas.codsubcuenta,'
            . ' partidas.concepto, partidas.debe, partidas.haber,'
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

    /**
     * Return the appropriate data from database.
     *
     * @param array $params
     *
     * @return array
     */
    protected function getDataGroupedByAccount(array $params = []): array
    {
        if (false === $this->dataBase->tableExists('partidas')) {
            return [];
        }

        $sql = 'SELECT asientos.numero, asientos.fecha, partidas.codsubcuenta,'
            . ' partidas.concepto, partidas.debe, partidas.haber,'
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

    /**
     * Return the appropriate data from database.
     *
     * @param array $params
     *
     * @return array
     */
    protected function getDataGroupedBySubAccount(array $params = []): array
    {
        if (false === $this->dataBase->tableExists('partidas')) {
            return [];
        }

        $sql = 'SELECT asientos.numero, asientos.fecha, partidas.codsubcuenta,'
            . ' partidas.concepto, partidas.debe, partidas.haber,'
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

    /**
     * @param array $params
     *
     * @return string
     */
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

    /**
     * @param string $codcuenta
     *
     * @return float
     */
    protected function getCuentaBalance($codcuenta): float
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

    /**
     * @param array $ledger
     * @param array $line
     */
    protected function processLine(&$ledger, $line)
    {
        $ledger[] = [
            'asiento' => $line['numero'],
            'fecha' => date(Partida::DATE_STYLE, strtotime($line['fecha'])),
            'cuenta' => $line['codsubcuenta'],
            'concepto' => $this->toolBox()->utils()->fixHtml($line['concepto']),
            'debe' => $this->toolBox()->coins()->format($line['debe'], FS_NF0, ''),
            'haber' => $this->toolBox()->coins()->format($line['haber'], FS_NF0, '')
        ];
    }

    /**
     * @param array $balances
     * @param array $ledger
     * @param array $line
     */
    protected function processLineBalanceGroupedByAccount(&$balances, &$ledger, $line)
    {
        $codcuenta = $line['codcuenta'];
        if (!isset($balances[$codcuenta])) {
            $balances[$codcuenta] = $this->getCuentaBalance($codcuenta);
        }

        if (!isset($ledger[$codcuenta])) {
            $ledger[$codcuenta][] = [
                'asiento' => '',
                'fecha' => date(Partida::DATE_STYLE, strtotime($this->dateFrom)),
                'cuenta' => $codcuenta,
                'concepto' => $this->toolBox()->utils()->fixHtml($line['cuentadesc']),
                'debe' => $this->toolBox()->coins()->format(0, FS_NF0, ''),
                'haber' => $this->toolBox()->coins()->format(0, FS_NF0, ''),
                'saldo' => $this->toolBox()->coins()->format($balances[$codcuenta], FS_NF0, '')
            ];
        }

        $balances[$codcuenta] += (float)$line['debe'] - (float)$line['haber'];
        $ledger[$codcuenta][] = [
            'asiento' => $line['numero'],
            'fecha' => date(Partida::DATE_STYLE, strtotime($line['fecha'])),
            'cuenta' => $codcuenta,
            'concepto' => $this->toolBox()->utils()->fixHtml($line['concepto']),
            'debe' => $this->toolBox()->coins()->format($line['debe'], FS_NF0, ''),
            'haber' => $this->toolBox()->coins()->format($line['haber'], FS_NF0, ''),
            'saldo' => $this->toolBox()->coins()->format($balances[$codcuenta], FS_NF0, '')
        ];
    }

    /**
     * @param array $balances
     * @param array $ledger
     * @param array $line
     */
    protected function processLineBalanceGroupedBySubAccount(&$balances, &$ledger, $line)
    {
        $codcuenta = $line['codsubcuenta'];
        if (!isset($balances[$codcuenta])) {
            $balances[$codcuenta] = $this->getCuentaBalance($codcuenta);
        }

        if (!isset($ledger[$codcuenta])) {
            $ledger[$codcuenta][] = [
                'asiento' => '',
                'fecha' => date(Partida::DATE_STYLE, strtotime($this->dateFrom)),
                'cuenta' => $codcuenta,
                'concepto' => $this->toolBox()->utils()->fixHtml($line['cuentadesc']),
                'debe' => $this->toolBox()->coins()->format(0, FS_NF0, ''),
                'haber' => $this->toolBox()->coins()->format(0, FS_NF0, ''),
                'saldo' => $this->toolBox()->coins()->format($balances[$codcuenta], FS_NF0, '')
            ];
        }

        $balances[$codcuenta] += (float)$line['debe'] - (float)$line['haber'];
        $ledger[$codcuenta][] = [
            'asiento' => $line['numero'],
            'fecha' => date(Partida::DATE_STYLE, strtotime($line['fecha'])),
            'cuenta' => $codcuenta,
            'concepto' => $this->toolBox()->utils()->fixHtml($line['concepto']),
            'debe' => $this->toolBox()->coins()->format($line['debe'], FS_NF0, ''),
            'haber' => $this->toolBox()->coins()->format($line['haber'], FS_NF0, ''),
            'saldo' => $this->toolBox()->coins()->format($balances[$codcuenta], FS_NF0, '')
        ];
    }
}
