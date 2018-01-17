<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Lib\Accounting;

/**
 * Description of ProffitAndLoss
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Raul Jiménez <comercial@nazcanetworks.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ProffitAndLoss extends AccountingBase
{
    /**
     *
     * @var string
     */
    protected $dateFromPrev;

    /**
     *
     * @var string
     */
    protected $dateToPrev;

    /**
     *
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return array
     */
    public function generate($dateFrom, $dateTo)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->dateFromPrev = $this->addToDate($dateFrom, '-1 year');
        $this->dateToPrev = $this->addToDate($dateTo, '-1 year');

        $data = $this->getData();
        if (empty($data)) {
            return [];
        }

        /// every page is a table
        $pages = [$this->calcProffitAndLoss($data)];
        return $pages;
    }

    /**
     * Format de Proffit-Lost including then chapters.
     *
     * @param array $data
     *
     * @return array
     */
    private function calcProffitAndLoss($data)
    {
        $balanceCalculado = [];
        foreach ($data as $lineaBalance) {
            $this->processDescription($lineaBalance, $balanceCalculado, 'descripcion1');
            $this->processDescription($lineaBalance, $balanceCalculado, 'descripcion2');
            $this->processDescription($lineaBalance, $balanceCalculado, 'descripcion3');
            $this->processDescription($lineaBalance, $balanceCalculado, 'descripcion4');
        }

        $balanceFinal = [];
        foreach ($balanceCalculado as $lineaBalance) {
            $balanceFinal[] = $this->processLine($lineaBalance);
        }

        return $balanceFinal;
    }

    /**
     * Obtains the balances for each one of the sections of the balance sheet according to their assigned accounts.
     *
     * @return array
     */
    protected function getData()
    {
        $dateFrom = $this->dataBase->var2str($this->dateFrom);
        $dateTo = $this->dataBase->var2str($this->dateTo);
        $dateFromPrev = $this->dataBase->var2str($this->dateFromPrev);
        $dateToPrev = $this->dataBase->var2str($this->dateToPrev);

        $sql = 'select cb.codbalance,cb.naturaleza,cb.descripcion1,cb.descripcion2,cb.descripcion3,cb.descripcion4,ccb.codcuenta,'
            . ' SUM(CASE WHEN asto.fecha BETWEEN ' . $dateFrom . ' AND ' . $dateTo . ' THEN pa.debe - pa.haber ELSE 0 END) saldo,'
            . ' SUM(CASE WHEN asto.fecha BETWEEN ' . $dateFromPrev . ' AND ' . $dateToPrev . ' THEN pa.debe - pa.haber ELSE 0 END) saldoprev'
            . ' from co_cuentascbba ccb '
            . ' INNER JOIN co_codbalances08 cb ON ccb.codbalance = cb.codbalance '
            . ' INNER JOIN co_partidas pa ON substr(pa.codsubcuenta, 1, 1) between \'6\' and \'7\' and pa.codsubcuenta like concat(ccb.codcuenta,\'%\')'
            . ' INNER JOIN co_asientos asto on asto.idasiento = pa.idasiento and asto.fecha between ' . $dateFromPrev . ' and ' . $dateTo
            . ' where cb.naturaleza = \'PG\''
            . ' group by 1, 2, 3, 4, 5, 6, 7 '
            . ' ORDER BY cb.naturaleza, cb.nivel1, cb.nivel2, cb.orden3, cb.nivel4';

        return $this->dataBase->select($sql);
    }

    /**
     *
     * @param array  $linea
     * @param array  $balance
     * @param string $description
     */
    protected function processDescription(&$linea, &$balance, $description)
    {
        $index = $linea[$description];
        if (empty($index)) {
            return;
        }

        if (!array_key_exists($index, $balance)) {
            $balance[$index] = [
                'descripcion' => $index,
                'saldo' => $linea['saldo'],
                'saldoprev' => $linea['saldoprev'], ];
        } else {
            $balance[$index]['saldo'] += $linea['saldo'];
            $balance[$index]['saldoprev'] += $linea['saldoprev'];
        }
    }

    /**
     * Process the line data to use the appropiate formats.
     *
     * @param array $line
     *
     * @return array
     */
    protected function processLine($line)
    {
        $line['descripcion'] = $this->fixHtml($line['descripcion']);
        $line['saldo'] = $this->divisaTools->format($line['saldo'], FS_NF0, false);
        $line['saldoprev'] = $this->divisaTools->format($line['saldoprev'], FS_NF0, false);

        return $line;
    }
}
