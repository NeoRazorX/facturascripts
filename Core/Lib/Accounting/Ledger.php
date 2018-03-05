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
 * Description of Ledger
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author nazca <comercial@nazcanetworks.com>
 */
class Ledger extends AccountingBase
{

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
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;

        $results = $this->getData();
        if (empty($results)) {
            return [];
        }

        $ledger = [];
        foreach ($results as $line) {
            $ledger[] = $this->processLine($line);
        }

        /// every page is a table
        $pages = [$ledger];
        return $pages;
    }

    /**
     * Return the appropiate data from database.
     *
     * @return array
     */
    protected function getData()
    {
        $sql = 'SELECT asto.numero, asto.fecha, part.codsubcuenta, part.concepto, part.debe, part.haber'
            . ' FROM asientos as asto, partidas AS part WHERE asto.idasiento = part.idasiento '
            . ' AND fecha >= ' . $this->dataBase->var2str($this->dateFrom)
            . ' AND fecha <= ' . $this->dataBase->var2str($this->dateTo)
            . ' ORDER BY asto.numero, part.codsubcuenta ASC';

        return $this->dataBase->select($sql);
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
        $line['fecha'] = date('d-m-Y', strtotime($line['fecha']));
        $line['concepto'] = $this->fixHtml($line['concepto']);
        $line['debe'] = $this->divisaTools->format($line['debe'], FS_NF0, '');
        $line['haber'] = $this->divisaTools->format($line['haber'], FS_NF0, '');

        return $line;
    }
}
