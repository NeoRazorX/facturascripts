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
use FacturaScripts\Core\Base\DivisaTools;
use FacturaScripts\Core\Base\Utils;

/**
 * Description of BalanceAmmounts
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author nazca <comercial@nazcanetworks.com>
 */
class BalanceAmmounts
{

    use Utils;

    /**
     * Tools to format money.
     * 
     * @var DivisaTools 
     */
    private $divisaTools;

    public function __construct()
    {
        $this->divisaTools = new DivisaTools();
    }

    /**
     * Generate the balance ammounts between two dates.
     *
     * @param date $dateFrom
     * @param date $dateTo
     * 
     * @return array
     */
    public function generate($dateFrom, $dateTo)
    {
        $results = $this->getData($dateFrom, $dateTo);
        if (empty($results)) {
            return [];
        }

        $balance = [];
        foreach ($results as $line) {
            $balance[] = $this->proccessLine($line);
        }

        return $balance;
    }

    /**
     * Return the balance data from database.
     *
     * @param string $dateFrom
     * @param string $dateTo
     *
     * return array;
     */
    private function getData($dateFrom, $dateTo)
    {
        $dataBase = new DataBase();
        $sql = 'SELECT subcta.codsubcuenta, subcta.descripcion, sum(partida.debe) SDebe,' .
            ' sum(partida.haber) SHaber, sum(partida.debe) - sum(partida.haber) saldo' .
            ' FROM `co_subcuentas` subcta,co_partidas partida,co_asientos asiento' .
            ' WHERE subcta.codsubcuenta = partida.codsubcuenta' .
            ' AND asiento.idasiento = partida.idasiento ' .
            ' AND asiento.fecha >= ' . $dataBase->var2str($dateFrom) .
            ' AND asiento.fecha <= ' . $dataBase->var2str($dateTo) .
            ' GROUP BY subcta.codsubcuenta, subcta.descripcion';

        return $dataBase->select($sql);
    }

    /**
     *
     * @param array $line
     *
     * @return array
     */
    private function proccessLine($line)
    {
        $line['SDebe'] = $this->divisaTools->format($line['SDebe'], FS_NF0, false);
        $line['SHaber'] = $this->divisaTools->format($line['SHaber'], FS_NF0, false);
        $line['saldo'] = $this->divisaTools->format($line['saldo'], FS_NF0, false);
        $line['descripcion'] = $this->fixHtml($line['descripcion']);

        return $line;
    }
}
