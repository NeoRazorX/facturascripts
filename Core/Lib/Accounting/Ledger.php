<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
        $grouping = (isset($params['grouping']) && $params['grouping']);

        $results = $grouping ? $this->getDataGrouped() : $this->getData();
        if (empty($results)) {
            return [];
        }

        $ledger = [];
        $ledgerAccount = [];
        /// Process each line of the results
        foreach ($results as $line) {
            $account = ($grouping) ? $line['codcuenta'] : 0;
            if ($grouping) {
                $this->processHeader($ledgerAccount[$account], $line);
                $ledger[$account][0] = $this->processLine($ledgerAccount[$account], $grouping);
            }
            $ledger[$account][] = $this->processLine($line, $grouping);
        }

        /// every page is a table
        $pages = $ledger;
        return $pages;
    }

    /**
     * Return the appropiate data from database.
     *
     * @return array
     */
    protected function getData()
    {
        if (!$this->dataBase->tableExists('partidas')) {
            return [];
        }

        $sql = 'SELECT asto.fecha, asto.numero, subc.codcuenta, cuentas.descripcion '
            . ' as cuenta_descripcion, part.codsubcuenta, part.concepto, part.debe, part.haber '
            . ' FROM asientos as asto, partidas AS part, subcuentas as subc, cuentas '
            . ' WHERE asto.idasiento = part.idasiento '
            . ' AND asto.fecha >= ' . $this->dataBase->var2str($this->dateFrom)
            . ' AND asto.fecha <= ' . $this->dataBase->var2str($this->dateTo)
            . ' AND subc.codejercicio = asto.codejercicio '
            . ' AND cuentas.codejercicio = asto.codejercicio '
            . ' AND subc.codsubcuenta = part.codsubcuenta '
            . ' AND subc.idcuenta = cuentas.idcuenta '
            . ' ORDER BY asto.fecha, asto.numero ASC';
        return $this->dataBase->select($sql);
    }

    /**
     * Return the appropiate data from database.
     *
     * @return array
     */
    protected function getDataGrouped()
    {
        if (!$this->dataBase->tableExists('partidas')) {
            return [];
        }

        $sql = 'SELECT subc.codcuenta, cuentas.descripcion '
            . ' as cuenta_descripcion, part.codsubcuenta, subc.descripcion as concepto, '
            . ' sum(part.debe) as debe, sum(part.haber) as haber '
            . ' FROM asientos as asto, partidas AS part, subcuentas as subc, cuentas '
            . ' WHERE asto.idasiento = part.idasiento '
            . ' AND asto.fecha >= ' . $this->dataBase->var2str($this->dateFrom)
            . ' AND asto.fecha <= ' . $this->dataBase->var2str($this->dateTo)
            . ' AND subc.codejercicio = asto.codejercicio '
            . ' AND cuentas.codejercicio = asto.codejercicio '
            . ' AND subc.codsubcuenta = part.codsubcuenta '
            . ' AND subc.idcuenta = cuentas.idcuenta '
            . ' GROUP BY subc.codcuenta, cuentas.descripcion, part.codsubcuenta, subc.descripcion'
            . ' ORDER BY subc.codcuenta, part.codsubcuenta ASC';
        return $this->dataBase->select($sql);
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
     * If the $grouping variable is not equal to non-group
     * then we dont return the 'fecha' and 'numero' fields
     *
     * @param array $line
     * @param bool  $grouping
     *
     * @return array
     */
    protected function processLine($line, $grouping)
    {
        $item = $grouping ? [] : ['fecha' => $line['fecha'], 'numero' => $line['numero']];
        $item['cuenta'] = isset($line['cuenta']) ? $line['cuenta'] : $line['codsubcuenta'];
        $item['concepto'] = $this->toolBox()->utils()->fixHtml($line['concepto']);
        $item['debe'] = $this->toolBox()->coins()->format($line['debe'], FS_NF0, '');
        $item['haber'] = $this->toolBox()->coins()->format($line['haber'], FS_NF0, '');
        return $item;
    }
}
