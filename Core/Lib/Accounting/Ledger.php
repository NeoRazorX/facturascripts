<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

        /// needed dependecies
        new Partida();
    }

    /**
     * Generate the ledger between two dates.
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param array  $params
     *
     * @return array
     */
    public function generate(string $dateFrom, string $dateTo, array $params = [])
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $grouped = (bool) $params['grouped'] ?? false;

        $results = $grouped ? $this->getDataGrouped($params) : $this->getData($params);
        if (empty($results)) {
            return [];
        }

        $ledger = [];
        $ledgerAccount = [];
        /// Process each line of the results
        foreach ($results as $line) {
            $account = $grouped ? $line['codcuenta'] : 0;
            if ($grouped) {
                $this->processHeader($ledgerAccount[$account], $line);
                $ledger[$account][0] = $this->processLine($ledgerAccount[$account], $grouped);
            }
            $ledger[$account][] = $this->processLine($line, $grouped);
        }

        /// every page is a table
        $pages = $ledger;
        return $pages;
    }

    /**
     * Config options for create a ledger button
     *
     * @param string $type
     * @param string $action
     * @return array
     */
    public static function getButton($type, $action = 'ledger')
    {
        return [
            'color' => 'info',
            'icon' => 'fas fa-book fa-fw',
            'label' => 'ledger',
            'action' => $action,
            'type' => $type,
        ];
    }

    /**
     * Return the appropiate data from database.
     *
     * @return array
     */
    protected function getData(array $params = [])
    {
        if (!$this->dataBase->tableExists('partidas')) {
            return [];
        }

        $sql = 'SELECT asientos.fecha, asientos.numero,'
            . ' partidas.codsubcuenta, partidas.concepto, partidas.debe, partidas.haber, '
            . ' subcuentas.codcuenta,'
            . ' cuentas.descripcion as cuenta_descripcion'
            . ' FROM asientos'
            . ' INNER JOIN partidas ON partidas.idasiento = asientos.idasiento'
            . ' INNER JOIN subcuentas ON subcuentas.idsubcuenta = partidas.idsubcuenta'
            . ' INNER JOIN cuentas ON cuentas.idcuenta = subcuentas.idcuenta'
            . ' WHERE ' . $this->getDataWhere($params)
            . ' ORDER BY asientos.fecha, asientos.numero ASC';
        return $this->dataBase->select($sql);
    }

    /**
     * Return the appropiate data from database.
     *
     * @return array
     */
    protected function getDataGrouped(array $params = [])
    {
        if (!$this->dataBase->tableExists('partidas')) {
            return [];
        }

        $sql = 'SELECT subcuentas.codcuenta, subcuentas.descripcion as concepto,'
            . ' cuentas.descripcion as cuenta_descripcion,'
            . ' partidas.codsubcuenta,'
            . ' sum(partidas.debe) as debe, sum(partidas.haber) as haber '
            . ' FROM asientos'
            . ' INNER JOIN partidas ON partidas.idasiento = asientos.idasiento'
            . ' INNER JOIN subcuentas ON subcuentas.idsubcuenta = partidas.idsubcuenta'
            . ' INNER JOIN cuentas ON cuentas.idcuenta = subcuentas.idcuenta'
            . ' WHERE ' . $this->getDataWhere($params)
            . ' GROUP BY subcuentas.codcuenta, cuentas.descripcion, partidas.codsubcuenta, subcuentas.descripcion'
            . ' ORDER BY subcuentas.codcuenta, partidas.codsubcuenta ASC';
        return $this->dataBase->select($sql);
    }

    /**
     *
     * @param array $params
     * @return string
     */
    protected function getDataWhere(array $params = [])
    {
        $where = 'asientos.fecha BETWEEN ' . $this->dataBase->var2str($this->dateFrom)
            . ' AND ' . $this->dataBase->var2str($this->dateTo);

        $channel = $params['channel'] ?? '';
        if (!empty($channel)) {
            $where .= ' AND asientos.canal = ' . $channel;
        }

        $subaccountFrom = $params['subaccount-from'] ?? '';
        $subaccountTo = $params['subaccount-to'] ?? $subaccountFrom;
        if (!empty($subaccountFrom) || (!empty($subaccountTo))) {
            $where .= ' AND partidas.codsubcuenta BETWEEN ' . $this->dataBase->var2str($subaccountFrom)
                . ' AND ' . $this->dataBase->var2str($subaccountTo);
        }

        $accountFrom = $params['account-from'] ?? '';
        $accountTo = $params['account-to'] ?? $accountFrom;
        if (!empty($accountFrom) || (!empty($accountTo))) {
            $where .= ' AND subcuentas.codcuenta BETWEEN ' . $this->dataBase->var2str($accountFrom)
                . ' AND ' . $this->dataBase->var2str($accountTo);
        }

        return $where;
    }

    /**
     * Process the header data to use the appropiate formats.
     *
     * @param array $line
     *
     * @return array
     */
    protected function processHeader(&$ledgerAccount, $line)
    {
        $ledgerAccount['fecha'] = false;
        $ledgerAccount['numero'] = false;
        $ledgerAccount['cuenta'] = $line['codcuenta'];
        $ledgerAccount['concepto'] = $line['cuenta_descripcion'];
        if (!isset($ledgerAccount['debe'])) {
            $ledgerAccount['debe'] = 0;
            $ledgerAccount['haber'] = 0;
        }
        $ledgerAccount['debe'] += $line['debe'];
        $ledgerAccount['haber'] += $line['haber'];
    }

    /**
     * Process the line data to use the appropiate formats.
     * If the $grouped variable is not equal to non-group
     * then we dont return the 'fecha' and 'numero' fields
     *
     * @param array $line
     * @param bool  $grouped
     *
     * @return array
     */
    protected function processLine($line, $grouped)
    {
        $item = $grouped ? [] : ['fecha' => $line['fecha'], 'numero' => $line['numero']];
        $item['cuenta'] = isset($line['cuenta']) ? $line['cuenta'] : $line['codsubcuenta'];
        $item['concepto'] = $this->toolBox()->utils()->fixHtml($line['concepto']);
        $item['debe'] = $this->toolBox()->coins()->format($line['debe'], FS_NF0, '');
        $item['haber'] = $this->toolBox()->coins()->format($line['haber'], FS_NF0, '');
        return $item;
    }
}
