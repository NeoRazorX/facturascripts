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

use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\Partida;

/**
 * Description of BalanceAmounts
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author nazca                <comercial@nazcanetworks.com>
 */
class BalanceAmounts extends AccountingBase
{

    /**
     * BalanceAmounts constructor.
     */
    public function __construct()
    {
        parent::__construct();

        /// needed dependencies
        new Partida();
    }

    /**
     * Generate the balance amounts between two dates.
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
        $level = (int) $params['level'] ?? 0;

        $results = $level > 0 ? $this->getDataGrouped($level, $params) : $this->getData($params);
        if (empty($results)) {
            return [];
        }

        /// we need this multidimensial array for printing support
        $totals = [['debe' => 0.00, 'haber' => 0.00, 'saldo' => 0.00]];

        $balance = [];
        foreach ($results as $line) {
            $balance[] = $this->processLine($line, $totals);
        }

        $totals[0]['debe'] = $this->toolBox()->coins()->format($totals[0]['debe'], FS_NF0, '');
        $totals[0]['haber'] = $this->toolBox()->coins()->format($totals[0]['haber'], FS_NF0, '');
        $totals[0]['saldo'] = $this->toolBox()->coins()->format($totals[0]['saldo'], FS_NF0, '');

        /// every page is a table
        return [$balance, $totals];
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

        $sql = 'SELECT partidas.codsubcuenta, subcuentas.descripcion,'
            . ' SUM(partidas.debe) AS debe,'
            . ' SUM(partidas.haber) AS haber'
            . ' FROM asientos'
            . ' INNER JOIN partidas ON partidas.idasiento = asientos.idasiento'
            . ' LEFT JOIN subcuentas ON subcuentas.idsubcuenta = partidas.idsubcuenta'
            . ' WHERE ' . $this->getDataWhere($params)
            . ' GROUP BY 1, 2'
            . ' ORDER BY 1 ASC';

        return $this->dataBase->select($sql);
    }

    /**
     * Return the appropiate data from database agrouped
     *
     * @param int   $level
     * @param array $params
     */
    protected function getDataGrouped(int $level, array $params = [])
    {
        if (!$this->dataBase->tableExists('partidas')) {
            return [];
        }

        $codeField = 'SUBSTR(partidas.codsubcuenta, 1, ' . $level . ')';
        $sql = 'SELECT ' . $codeField . ' codsubcuenta,'
            . ' cuentas.descripcion,'
            . ' SUM(partidas.debe) AS debe,'
            . ' SUM(partidas.haber) AS haber'
            . ' FROM asientos'
            . ' INNER JOIN partidas ON partidas.idasiento = asientos.idasiento'
            . ' LEFT JOIN cuentas ON cuentas.codejercicio = asientos.codejercicio AND cuentas.codcuenta = ' . $codeField
            . ' WHERE ' . $this->getDataWhere($params)
            . ' GROUP BY 1, 2'
            . ' ORDER BY 1 ASC';

        return $this->dataBase->select($sql);
    }

    /**
     *
     * @param array $params
     * @return string
     */
    protected function getDataWhere(array $params = [])
    {
        $where = 'asientos.codejercicio = ' . $this->dataBase->var2str($this->exercise->codejercicio)
            . ' AND asientos.fecha BETWEEN ' . $this->dataBase->var2str($this->dateFrom) . ' AND ' . $this->dataBase->var2str($this->dateTo);

        $channel = $params['channel'] ?? '';
        if (!empty($channel)) {
            $where .= ' AND asientos.canal = ' . $channel;
        }

        $ignoreRegularization = (bool) $params['ignoreregularization'] ?? false;
        if ($ignoreRegularization) {
            $where .= ' AND asientos.operacion <> \'' . Asiento::OPERATION_REGULARIZATION . '\'';
        }

        $ignoreClosure = (bool) $params['ignoreclosure'] ?? false;
        if ($ignoreClosure) {
            $where .= ' AND asientos.operacion <> \'' . Asiento::OPERATION_CLOSING . '\'';
        }

        $subaccountFrom = $params['subaccount-from'] ?? '';
        $subaccountTo = $params['subaccount-to'] ?? $subaccountFrom;
        if (!empty($subaccountFrom) || !empty($subaccountTo)) {
            $where .= ' AND partidas.codsubcuenta BETWEEN ' . $this->dataBase->var2str($subaccountFrom) . ' AND ' . $this->dataBase->var2str($subaccountTo);
        }

        return $where;
    }

    /**
     * Process the line data to use the appropiate formats.
     * Accumulate the line in the totals.
     *
     * @param array $line
     * @param array $totals
     *
     * @return array
     */
    private function processLine(&$line, &$totals)
    {
        $debe = (float) $line['debe'];
        $haber = (float) $line['haber'];
        $saldo = $debe - $haber;

        $totals[0]['debe'] += $debe;
        $totals[0]['haber'] += $haber;
        $totals[0]['saldo'] += $saldo;

        return [
            'cuenta' => $line['codsubcuenta'],
            'descripcion' => $line['descripcion'],
            'debe' => $this->toolBox()->coins()->format($debe, FS_NF0, ''),
            'haber' => $this->toolBox()->coins()->format($haber, FS_NF0, ''),
            'saldo' => $this->toolBox()->coins()->format($saldo, FS_NF0, '')
        ];
    }
}
