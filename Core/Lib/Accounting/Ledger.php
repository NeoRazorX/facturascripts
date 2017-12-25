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
     * Generate the ledger between two dates.
     * 
     * @param date $dateFrom
     * @param date $dateTo
     * 
     * @return array
     */
    public function generate($dateFrom, $dateTo)
    {
        $dataBase = new DataBase();
        $sql = 'SELECT asto.numero, asto.fecha, part.codsubcuenta, part.concepto, part.debe, part.haber ' .
            'FROM co_asientos as asto, co_partidas as part WHERE asto.idasiento = part.idasiento '
            . ' AND fecha >= ' . $dataBase->var2str($dateFrom)
            . ' AND fecha <= ' . $dataBase->var2str($dateTo)
            . ' ORDER BY part.codsubcuenta, asto.fecha, part.idasiento ASC';

        $results = $dataBase->select($sql);
        if (empty($results)) {
            return [];
        }

        $mayor = [];
        $tmpcuenta = '';
        $saldo = 0.0;
        foreach ($results as $linea) {
            if ($tmpcuenta != $linea['codsubcuenta']) {
                $saldo = 0.0;
            }
            $saldo += $linea['debe'] - $linea['haber'];
            $tmpcuenta = $linea['codsubcuenta'];

            $linea['saldo'] = $saldo;
            $mayor[] = $linea;
        }

        return $mayor;
    }
}
