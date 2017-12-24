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

use FacturaScripts\Core\Base\DataBase;

/**
 * Description of Ledger
 *
 * @author carlos
 * @author nazca <comercial@nazcanetworks.com>
 */
class Ledger
{

    /**
     * Generate the ledger between two dates
     * @param date $dateFrom
     * @param date $dateTo
     * @return array
     */
    public function generate($dateFrom, $dateTo)
    {

        $mayor = array();
        $sql = 'SELECT asto.numero, asto.fecha,part.codsubcuenta,part.concepto, part.debe,part.haber,0 saldo ' .
            'FROM `co_asientos` asto, `co_partidas` part where asto.idasiento = part.idasiento '
            . ' and fecha>="' . date('Y-m-d', strtotime($dateFrom)) . '" and fecha<="' . date('Y-m-d', strtotime($dateTo))
            . '" order by part.codsubcuenta,asto.fecha,part.idasiento ASC';

        $datb = new Database();
        $resultados = $datb->select($sql);
        // $resultados = self::$dataBase->select($sql);
        if (!empty($resultados)) {
            $tmpcuenta = '';
            $saldo = 0;
            foreach ($resultados as $linea) {
                if ($tmpcuenta != $linea['codsubcuenta']) {
                    $saldo = 0;
                }
                $saldo = $saldo + $linea['debe'] - $linea['haber'];
                $linea['saldo'] = $saldo;

                $mayor[] = $linea;
                $tmpcuenta = $linea['codsubcuenta'];
            }
        }
        return $mayor;
        //return $p->acountingLedger($dateFrom, $dateTo);
    }
}
