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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\Balance;
use FacturaScripts\Dinamic\Model\BalanceCuenta;
use FacturaScripts\Dinamic\Model\BalanceCuentaA;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\Partida;

/**
 * Description of ProfitAndLoss
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Raul Jiménez         <comercial@nazcanetworks.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class ProfitAndLoss extends AccountingBase
{

    /**
     * Date from for filter
     *
     * @var string
     */
    protected $dateFromPrev;

    /**
     * Date to for filter
     *
     * @var string
     */
    protected $dateToPrev;

    /**
     * 
     * @var Ejercicio
     */
    protected $exercisePrev;

    /**
     * ProfitAndLoss class constructor
     */
    public function __construct()
    {
        parent::__construct();

        /// needed dependencies
        new Partida();
        new BalanceCuenta();
        new BalanceCuentaA();
    }

    /**
     * Generate the data results.
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
        $this->dateFromPrev = $this->addToDate($dateFrom, '-1 year');
        $this->dateToPrev = $this->addToDate($dateTo, '-1 year');

        $this->exercisePrev = new Ejercicio();
        $where = [
            new DataBaseWhere('fechainicio', $this->dateFromPrev, '<='),
            new DataBaseWhere('fechafin', $this->dateToPrev, '>='),
            new DataBaseWhere('idempresa', $this->exercise->idempresa)
        ];
        $this->exercisePrev->loadFromCode('', $where);

        return [$this->getData('PG', $params)];
    }

    /**
     * 
     * @param array     $rows
     * @param Balance[] $balances
     * @param string    $code1
     * @param array     $amouns1
     * @param string    $code2
     * @param array     $amouns2
     */
    protected function addTotalsRow(&$rows, $balances, $code1, $amouns1, $code2, $amouns2)
    {
        $rows[] = ['descripcion' => '', $code1 => '', $code2 => ''];

        $levels = [];
        $total1 = $total2 = 0.00;
        foreach ($balances as $bal) {
            if (isset($levels[$bal->nivel1])) {
                continue;
            }

            $levels[$bal->nivel1] = $bal->nivel1;
            $total1 += $amouns1[$bal->nivel1];
            $total2 += $amouns2[$bal->nivel1];
        }

        $rows[] = [
            'descripcion' => '<b>Total (' . \implode('+', $levels) . ')</b>',
            $code1 => '<b>' . $this->toolBox()->coins()->format($total1, FS_NF0, '') . '</b>',
            $code2 => '<b>' . $this->toolBox()->coins()->format($total2, FS_NF0, '') . '</b>'
        ];
    }

    /**
     * 
     * @param Balance $balance
     * @param string  $codejercicio
     * @param array   $params
     *
     * @return float
     */
    protected function getAmounts($balance, $codejercicio, $params): float
    {
        $total = 0.00;
        $balAccount = new BalanceCuentaA();
        $where = [new DataBaseWhere('codbalance', $balance->codbalance)];
        foreach ($balAccount->all($where, [], 0, 0) as $model) {
            $sql = "SELECT SUM(partidas.debe) AS debe, SUM(partidas.haber) AS haber"
                . " FROM partidas"
                . " LEFT JOIN asientos ON partidas.idasiento = asientos.idasiento"
                . " WHERE asientos.codejercicio = " . $this->dataBase->var2str($codejercicio)
                . " AND partidas.codsubcuenta LIKE '" . $model->codcuenta . "%';";

            if ($model->codcuenta === '129') {
                $sql = "SELECT SUM(partidas.debe) as debe, SUM(partidas.haber) as haber"
                    . " FROM partidas"
                    . " LEFT JOIN subcuentas ON partidas.idsubcuenta = subcuentas.idsubcuenta"
                    . " LEFT JOIN cuentas ON subcuentas.idcuenta = cuentas.idcuenta"
                    . " WHERE subcuentas.codejercicio = " . $this->dataBase->var2str($codejercicio)
                    . " AND (subcuentas.codcuenta LIKE '6%' OR subcuentas.codcuenta LIKE '7%')";
            }

            foreach ($this->dataBase->select($sql) as $row) {
                $total += $balance->naturaleza === 'A' ?
                    (float) $row['debe'] - (float) $row['haber'] :
                    (float) $row['haber'] - (float) $row['debe'];
            }
        }

        return $total;
    }

    /**
     * 
     * @param string $nature
     * @param array  $params
     *
     * @return array
     */
    protected function getData($nature = 'A', $params = [])
    {
        $rows = [];
        $code1 = $this->exercise->codejercicio;
        $code2 = $this->exercisePrev->codejercicio;

        /// get balance codes
        $balance = new Balance();
        $where = [
            new DataBaseWhere('naturaleza', $nature),
            new DataBaseWhere('nivel1', '', '!=')
        ];
        $order = ['nivel1' => 'ASC', 'nivel2' => 'ASC', 'nivel3' => 'ASC', 'nivel4' => 'ASC'];
        $balances = $balance->all($where, $order, 0, 0);

        /// get amounts
        $amountsE1 = [];
        $amountsE2 = [];
        $amountsNE1 = [];
        $amountsNE2 = [];
        foreach ($balances as $bal) {
            $this->sumAmounts($amountsE1, $amountsNE1, $bal, $code1, $params);
            $this->sumAmounts($amountsE2, $amountsNE2, $bal, $code2, $params);
        }

        /// add to table
        $coins = $this->toolBox()->coins();
        $nivel1 = $nivel2 = $nivel3 = $nivel4 = '';
        foreach ($balances as $bal) {
            if ($bal->nivel1 != $nivel1 && !empty($bal->nivel1)) {
                $nivel1 = $bal->nivel1;
                $rows[] = ['descripcion' => '', $code1 => '', $code2 => ''];
                $rows[] = [
                    'descripcion' => '<b>' . $bal->descripcion1 . '</b>',
                    $code1 => '<b>' . $coins->format($amountsNE1[$bal->nivel1], FS_NF0, '') . '</b>',
                    $code2 => '<b>' . $coins->format($amountsNE2[$bal->nivel1], FS_NF0, '') . '</b>'
                ];
            }

            if ($bal->nivel2 != $nivel2 && !empty($bal->nivel2)) {
                $nivel2 = $bal->nivel2;
                $rows[] = [
                    'descripcion' => '  ' . $bal->descripcion2,
                    $code1 => $coins->format($amountsNE1[$bal->nivel1 . '-' . $bal->nivel2], FS_NF0, ''),
                    $code2 => $coins->format($amountsNE2[$bal->nivel1 . '-' . $bal->nivel2], FS_NF0, '')
                ];
            }

            if ($bal->nivel3 != $nivel3 && !empty($bal->nivel3)) {
                $nivel3 = $bal->nivel3;
                $rows[] = [
                    'descripcion' => '    ' . $bal->descripcion3,
                    $code1 => $coins->format($amountsNE1[$bal->nivel1 . '-' . $bal->nivel2 . '-' . $bal->nivel3], FS_NF0, ''),
                    $code2 => $coins->format($amountsNE2[$bal->nivel1 . '-' . $bal->nivel2 . '-' . $bal->nivel3], FS_NF0, '')
                ];
            }

            if ($bal->nivel4 != $nivel4 && !empty($bal->nivel4)) {
                $nivel4 = $bal->nivel4;
                if (empty($amountsE1[$bal->codbalance]) && empty($amountsE2[$bal->codbalance])) {
                    continue;
                }

                $rows[] = [
                    'descripcion' => '      ' . $bal->descripcion4,
                    $code1 => $coins->format($amountsE1[$bal->codbalance], FS_NF0, ''),
                    $code2 => $coins->format($amountsE2[$bal->codbalance], FS_NF0, '')
                ];
            }
        }

        $this->addTotalsRow($rows, $balances, $code1, $amountsNE1, $code2, $amountsNE2);
        return $rows;
    }

    /**
     * 
     * @param array   $amounts
     * @param array   $amountsN
     * @param Balance $balance
     * @param string  $codejercicio
     * @param array   $params
     */
    protected function sumAmounts(&$amounts, &$amountsN, $balance, $codejercicio, $params)
    {
        $amounts[$balance->codbalance] = $total = $this->getAmounts($balance, $codejercicio, $params);

        if (isset($amountsN[$balance->nivel1])) {
            $amountsN[$balance->nivel1] += $total;
        } else {
            $amountsN[$balance->nivel1] = $total;
        }

        if (isset($amountsN[$balance->nivel1 . '-' . $balance->nivel2])) {
            $amountsN[$balance->nivel1 . '-' . $balance->nivel2] += $total;
        } else {
            $amountsN[$balance->nivel1 . '-' . $balance->nivel2] = $total;
        }

        if (isset($amountsN[$balance->nivel1 . '-' . $balance->nivel2 . '-' . $balance->nivel3])) {
            $amountsN[$balance->nivel1 . '-' . $balance->nivel2 . '-' . $balance->nivel3] += $total;
        } else {
            $amountsN[$balance->nivel1 . '-' . $balance->nivel2 . '-' . $balance->nivel3] = $total;
        }
    }
}
