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

    protected $dateFromPrev;
    protected $dateToPrev;

    /**
     * Constructor.
     * 
     * @param string $dateFrom
     * @param string $dateTo
     */
    public function __construct($dateFrom, $dateTo)
    {
        parent::__construct($dateFrom, $dateTo);

        $this->dateFromPrev = $this->addToDate($this->dateFrom, '-1 year');
        $this->dateToPrev = $this->addToDate($this->dateTo, '-1 year');
    }

    public static function generate($dateFrom, $dateTo)
    {
        $ProffitAndLoss = new ProffitAndLoss($dateFrom, $dateTo);
        $data = $ProffitAndLoss->getData();
        if (empty($data)) {
            return [];
        }

        $proffitLostFinal = $ProffitAndLoss->calcProffitAndLoss($data);
        return $proffitLostFinal;
    }

    /**
     * Format de Proffit-Lost including then chapters.
     * 
     * @param array $proffitLost
     * 
     * @return array
     */
    private function calcProffitAndLoss($proffitLost)
    {
        $balanceCalculado = [];

        if (!empty($proffitLost)) {
            foreach ($proffitLost as $lineaBalance) {
                $this->processDescription('descripcion1', $lineaBalance, $balanceCalculado);
                $this->processDescription('descripcion2', $lineaBalance, $balanceCalculado);
                $this->processDescription('descripcion3', $lineaBalance, $balanceCalculado);
                $this->processDescription('descripcion4', $lineaBalance, $balanceCalculado);
            }
        }

        $balanceFinal = [];
        foreach ($balanceCalculado as $lineaBalance) {
            $balanceFinal[] = $this->processLine($lineaBalance);
        }

        return($balanceFinal);
    }

    /**
     * Obtains the balances for each one of the sections of the balance sheet according to their assigned accounts.
     * 
     * @return array
     */
    protected function getData()
    {

        $dateFrom = $this->database->var2str($this->dateFrom);
        $dateTo = $this->database->var2str($this->dateTo);
        $dateFromPrev = $this->database->var2str($this->dateFromPrev);
        $dateToPrev = $this->database->var2str($this->dateToPrev);

        $sql = 'select cb.codbalance,cb.naturaleza,cb.descripcion1,cb.descripcion2,cb.descripcion3,cb.descripcion4,ccb.codcuenta,'
            . ' SUM(CASE WHEN asto.fecha BETWEEN ' . $dateFrom . ' AND ' . $dateTo . ' THEN pa.debe - pa.haber ELSE 0 END) saldo,'
            . ' SUM(CASE WHEN asto.fecha BETWEEN ' . $dateFromPrev . ' AND ' . $dateToPrev . ' THEN pa.debe - pa.haber ELSE 0 END) saldoPrev'
            . ' from co_cuentascbba ccb '
            . ' INNER JOIN co_codbalances08 cb ON ccb.codbalance = cb.codbalance '
            . ' INNER JOIN co_partidas pa ON substr(pa.codsubcuenta, 1, 1) between "6" and "7" and pa.codsubcuenta like concat(ccb.codcuenta,"%")'
            . ' INNER JOIN co_asientos asto on asto.idasiento = pa.idasiento and asto.fecha between ' . $dateFromPrev . ' and ' . $dateTo
            . ' where cb.naturaleza ="PG"'
            . ' group by 1, 2, 3, 4, 5, 6, 7 '
            . ' ORDER BY cb.naturaleza, cb.nivel1, cb.nivel2, cb.orden3, cb.nivel4';
        return $this->database->select($sql);
    }

    private function processDescription($description, &$linea, &$balance)
    {
        $index = $linea[$description];
        if (empty($index)) {
            return;
        }

        if (!array_key_exists($index, $balance)) {
            $balance[$index] = [
                'descripcion' => $index,
                'saldo' => $linea['saldo'],
                'saldoPrev' => $linea['saldoPrev']];
        } else {
            $balance[$index]['saldo'] += $linea['saldo'];
            $balance[$index]['saldoPrev'] += $linea['saldoPrev'];
        }
    }

    protected function processLine($line)
    {
        $line['saldo'] = $this->divisaTools->format($line['saldo'], FS_NF0, false);
        $line['saldoPrev'] = $this->divisaTools->format($line['saldoPrev'], FS_NF0, false);
        $line['descripcion'] = $this->fixHtml($line['descripcion']);
        return $line;
    }
}
