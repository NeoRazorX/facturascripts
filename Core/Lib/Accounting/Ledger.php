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
 * Description of Ledger
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author nazca <comercial@nazcanetworks.com>
 */
class Ledger
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
     * Generate the ledger between two dates.
     * 
     * @param string $dateFrom
     * @param string $dateTo
     * 
     * @return array
     */
    public function generate($dateFrom, $dateTo)
    {
        $results = $this->getData($dateFrom, $dateTo);
        if (empty($results)) {
            return [];
        }

        $ledger = [];
        $tmpcuenta = '';
        $balance = 0.0;
        foreach ($results as $line) {
            if ($tmpcuenta != $line['codsubcuenta']) {
                $balance = 0.0;
            }
            $balance += $line['debe'] - $line['haber'];
            $tmpcuenta = $line['codsubcuenta'];

            $ledger[] = $this->processLine($line, $balance);
        }

        return $ledger;
    }

    /**
     * Return the appropiate data from database.
     * 
     * @param string $dateFrom
     * @param string $dateTo
     * 
     * @return array
     */
    private function getData($dateFrom, $dateTo)
    {
        $dataBase = new DataBase();
        $sql = 'SELECT asto.numero, asto.fecha, part.codsubcuenta, part.concepto, part.debe, part.haber ' .
            'FROM co_asientos as asto, co_partidas as part WHERE asto.idasiento = part.idasiento '
            . ' AND fecha >= ' . $dataBase->var2str($dateFrom)
            . ' AND fecha <= ' . $dataBase->var2str($dateTo)
            . ' ORDER BY part.codsubcuenta, asto.fecha, part.idasiento ASC';

        return $dataBase->select($sql);
    }
    
    /**
     * Process the line data to use the appropiate formats.
     * 
     * @param array $line
     * @param float $balance
     * 
     * @return array
     */
    private function processLine($line, $balance)
    {
        $line['saldo'] = $this->divisaTools->format($balance, FS_NF0, false);
        $line['haber'] = $this->divisaTools->format($line['haber'], FS_NF0, false);
        $line['debe'] = $this->divisaTools->format($line['debe'], FS_NF0, false);
        $line['concepto'] = $this->fixHtml($line['concepto']);
        $line['fecha'] = date('d-m-Y', strtotime($line['fecha']));
        
        return $line;
    }
}
