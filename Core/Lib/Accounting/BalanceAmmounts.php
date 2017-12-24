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
 * Description of BalanceAmmounts
 *
 * @author carlos
 * @author nazca <comercial@nazcanetworks.com>
 */
class BalanceAmmounts
{

    /**
     * Generate the balance ammounts between two dates.
     * 
     * @param date $dateFrom
     * @param date $dateTo
     * @return array
     */
    public function generate($dateFrom, $dateTo)
    {
        //SELECT subcta.codsubcuenta,subcta.descripcion,sum(partida.debe) SDebe,sum(partida.haber) SHaber,sum(partida.debe)-sum(partida.haber) saldo FROM `co_subcuentas` subcta,co_partidas partida,co_asientos asiento where subcta.codsubcuenta = partida.codsubcuenta and asiento.idasiento=partida.idasiento group by subcta.codsubcuenta,subcta.descripcion and asiento.fecha>='2017-01-01' and asiento.fecha<='2017-12-31'
        /// TODO
        $sql = 'SELECT subcta.codsubcuenta,subcta.descripcion,sum(partida.debe) SDebe,sum(partida.haber) SHaber,sum(partida.debe)-sum(partida.haber) saldo ' .
            ' FROM `co_subcuentas` subcta,co_partidas partida,co_asientos asiento ' .
            'where subcta.codsubcuenta = partida.codsubcuenta and asiento.idasiento=partida.idasiento ' .
            ' and asiento.fecha>="' . date('Y-m-d', strtotime($dateFrom)) . '" and asiento.fecha<="' . date('Y-m-d', strtotime($dateTo)) . '"' .
            'group by subcta.codsubcuenta,subcta.descripcion ';

        $datb = new Database();
        $resultados = $datb->select($sql);

        return $resultados;
    }
}
