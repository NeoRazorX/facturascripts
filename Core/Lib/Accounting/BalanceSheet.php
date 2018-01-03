<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\Database;
use FacturaScripts\Core\Base\DivisaTools;
use FacturaScripts\Core\Base\Utils;

/**
 * Description of BalanceSheet
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Raul Jiménez <comercial@nazcanetworks.com>
 */
class BalanceSheet
{

    public function generate($dateFrom, $dateTo)
    {
        $saldos = [];
        $saldos_1 = [];

        $saldos = $this->getBalance($dateFrom, $dateTo, TRUE);
        $saldos_1 = $this->getBalance($dateFrom, $dateTo, FALSE);
        $sheetBalance = $this->getSheetBalance($saldos, $saldos_1);

        $balanceFinal = $this->calcSheetBalance($sheetBalance);

        return $balanceFinal;
    }

    /**
     * Return balance group by account according to level
     *
     * @dateFrom string
     * @dateTo  string
     * @currentYear boolean
     *
     * return array
     *
     * @author Nazca Networks <comercial@nazcanetworks.com>
     */
    private function getBalance($dateFrom, $dateTo, $currentYear = TRUE)
    {
        $dataBase = new DataBase();
        $saldos = [];
        $largos = [];
        $sql = 'select length(codcuenta) largo from co_cuentascb group by length(codcuenta)';
        $largos = $dataBase->select($sql);
        if ($currentYear == FALSE) {
            $dateFrom = date('d-m-Y', strtotime('-1 year', strtotime($dateFrom)));
            $dateTo = date('d-m-Y', strtotime('-1 year', strtotime($dateTo)));
        }

        $sql = '';
        for ($conta = 0; $conta < count($largos); $conta++) {
            $sql .= 'SELECT  substr(partida.codsubcuenta,1,' . $largos[$conta]["largo"] . ') cuenta, sum(partida.debe) - sum(partida.haber) saldo ' .
                ' FROM co_partidas partida,co_asientos asiento ' .
                ' WHERE asiento.idasiento = partida.idasiento ' .
                ' AND asiento.fecha >=' . $dataBase->var2str($dateFrom) .
                ' AND asiento.fecha <=' . $dataBase->var2str($dateTo) .
                ' and substr(partida.codsubcuenta,1,1) in (1,2,3,4,5) ' .
                ' GROUP BY substr(partida.codsubcuenta,1,' . $largos[$conta]["largo"] . ') ';
            if ($conta < count($largos) - 1)
                $sql .= ' UNION ';
        }

        $saldosCuentas = $dataBase->select($sql);
        if (!empty($saldosCuentas)) {
            foreach ($saldosCuentas as $lineaSaldo) {
                if ($currentYear == TRUE)
                    $saldos[$lineaSaldo['cuenta']] = $lineaSaldo['saldo'];
                else
                    $saldos[$lineaSaldo['cuenta']] = $lineaSaldo['saldo'];
            }
        }
        return $saldos;
    }

    /**
     *
     * calculate the balances of the epigraphs of the balance
     * @param array $saldos
     * @param array $saldos_1
     * @return array
     */
    private function getSheetBalance($saldos, $saldos_1)
    {
        $dataBase = new DataBase();
        $sheetBalance = [];
        $sql = 'SELECT *,0 saldo,0 saldo_1 FROM `co_codbalances08` ' .
            'where naturaleza in ("A","P") ' .
            ' ORDER BY naturaleza,nivel1,descripcion1,orden3,nivel4 asc';
        $balance = $dataBase->select($sql);
        if (!empty($balance)) {
            foreach ($balance as $lineabal) {
                $sql2 = 'select codcuenta from co_cuentascbba where codbalance="' . $lineabal['codbalance'] . '"';
                $cuentas = $dataBase->select($sql2);
                if (!empty($cuentas)) {
                    foreach ($cuentas as $cuenta) {

                        if (array_key_exists($cuenta['codcuenta'], $saldos)) {
                            if (array_key_exists($lineabal['codbalance'], $sheetBalance))
                                $sheetBalance[$lineabal['codbalance']]['saldo'] += $saldos[$cuenta['codcuenta']];
                            else {
                                $lineabal['saldo'] = $saldos[$cuenta['codcuenta']];
                                $sheetBalance[$lineabal['codbalance']] = $lineabal;
                            }
                        }
                        if (array_key_exists($cuenta['codcuenta'], $saldos_1)) {
                            if (array_key_exists($lineabal['codbalance'], $sheetBalance))
                                $sheetBalance[$lineabal['codbalance']]['saldo_1'] += $saldos_1[$cuenta['codcuenta']];
                            else {
                                $lineabal['saldo_1'] = $saldos_1[$cuenta['codcuenta']];
                                $sheetBalance[$lineabal['codbalance']] = $lineabal;
                            }
                        }
                    }
                }
            }
        }
        return $sheetBalance;
    }

    /**
     * Format de balance including then chapters
     * @param array $balance
     * @return array
     */
    private function calcSheetBalance($balance)
    {
        $dataBase = new DataBase();
        $nivel1 = '';
        $nivel2 = '';
        $nivel3 = '';
        $nivel4 = '';
        $balanceCalculado = [];
        $saldoActivo = 0;
        $saldoPasivo = 0;
        $saldoBeneficio = 0;

        $lineaBalance = ['descripcion1' => 'ACTIVO', 'descripcion2' => '', 'descripcion3' => '', 'descripcion4' => '', 'saldo' => 0, 'saldo_1' => 0];
        $balanceCalculado['ACTIVO'] = $lineaBalance;

        if (!empty($balance)) {
            foreach ($balance as $lineaBalance) {

                if ($lineaBalance['naturaleza'] == 'P' && !array_key_exists('PASIVO', $balanceCalculado)) {
                    $lineaBalanceP = ['descripcion1' => 'PASIVO', 'descripcion2' => '', 'descripcion3' => '', 'descripcion4' => '', 'saldo' => 0, 'saldo_1' => 0];
                    $balanceCalculado['PASIVO'] = $lineaBalanceP;
                }
                if ($nivel1 !== $lineaBalance['nivel1']) {
                    $lineaAgregar = ['descripcion1' => '', 'descripcion2' => $lineaBalance['descripcion1'], 'descripcion3' => '', 'descripcion4' => '', 'saldo' => $lineaBalance['saldo'], 'saldo_1' => $lineaBalance['saldo_1']];
                    $balanceCalculado[$lineaBalance['descripcion1']] = $lineaAgregar;
                    $nivel1 = $lineaBalance['nivel1'];
                } else {
                    $balanceCalculado[$lineaBalance['descripcion1']]['saldo'] += $lineaBalance['saldo'];
                    $balanceCalculado[$lineaBalance['descripcion1']]['saldo_1'] += $lineaBalance['saldo_1'];
                }
                if ($lineaBalance['descripcion2'] !== '') {

                    if ($nivel2 !== $lineaBalance['nivel2']) {

                        $lineaAgregar = ['descripcion1' => '', 'descripcion2' => $lineaBalance['descripcion2'], 'descripcion3' => '', 'descripcion4' => '', 'saldo' => $lineaBalance['saldo'], 'saldo_1' => $lineaBalance['saldo_1']];
                        $balanceCalculado[$lineaBalance['descripcion2']] = $lineaAgregar;

                        $nivel2 = $lineaBalance['nivel2'];
                    } else {

                        $balanceCalculado[$lineaBalance['descripcion2']]['saldo'] += $lineaBalance['saldo'];
                        $balanceCalculado[$lineaBalance['descripcion2']]['saldo_1'] += $lineaBalance['saldo_1'];
                    }
                }
                if ($lineaBalance['descripcion3'] !== '') {
                    if ($nivel3 !== $lineaBalance['nivel3'] && $lineaBalance['descripcion3'] !== '') {
                        $lineaAgregar = ['descripcion1' => '', 'descripcion2' => '', 'descripcion3' => $lineaBalance['descripcion3'], 'descripcion4' => '', 'saldo' => $lineaBalance['saldo'], 'saldo_1' => $lineaBalance['saldo_1']];
                        $balanceCalculado[$lineaBalance['descripcion3']] = $lineaAgregar;
                        $nivel3 = $lineaBalance['nivel3'];
                    } else {
                        $balanceCalculado[$lineaBalance['descripcion3']['saldo']] += $lineaBalance['saldo'];
                        $balanceCalculado[$lineaBalance['descripcion3']['saldo_1']] += $lineaBalance['saldo_1'];
                    }
                }
                if ($lineaBalance['descripcion4'] !== '') {
                    if ($nivel4 !== $lineaBalance['nivel3'] && $lineaBalance['descripcion4'] !== '') {
                        $lineaAgregar = ['descripcion1' => '', 'descripcion2' => '', 'descripcion3' => '', 'descripcion4' => $lineaBalance['descripcion4'], 'saldo' => $lineaBalance['saldo'], 'saldo_1' => $lineaBalance['saldo_1']];
                        $balanceCalculado[$lineaBalance['descripcion4']] = $lineaAgregar;
                        $nivel4 = $lineaBalance['nivel4'];
                    } else {
                        $balanceCalculado[$lineaBalance['descripcion4']['saldo']] += $lineaBalance['saldo'];
                        $balanceCalculado[$lineaBalance['descripcion4']['saldo_1']] += $lineaBalance['saldo_1'];
                    }
                }

                if ($lineaBalance['naturaleza'] == 'A' && $lineaBalance['descripcion1'] !== '') {
                    $balanceCalculado['ACTIVO']['saldo'] += $lineaBalance['saldo'];
                    $balanceCalculado['ACTIVO']['saldo_1'] += $lineaBalance['saldo_1'];
                }
                if ($lineaBalance['naturaleza'] == 'P' && $lineaBalance['descripcion1'] !== '') {

                    $balanceCalculado['PASIVO']['saldo'] += $lineaBalance['saldo'];
                    $balanceCalculado['PASIVO']['saldo_1'] += $lineaBalance['saldo_1'];
                }
            }
        }

        foreach ($balanceCalculado as $linea) {
            $balanceFinal[] = $linea;
        }
        return($balanceFinal);
    }
}
