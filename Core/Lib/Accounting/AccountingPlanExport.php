<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase;

/**
 * Class to export accounting plans.
 *
 * @author Carlos García Gómez      <carlos@facturapascripts.com>
 * @author Oscar G. Villa González  <ogvilla@gmail.com>
 */
class AccountingPlanExport
{

    /**
     *
     * @var DataBase
     */
    private $dataBase;

    public function __construct()
    {
        $this->dataBase = new DataBase();
    }

    /**
     * Get Acoounting Plan data to export
     * 
     * @param string $code
     *
     * @return array
     */
    public function getDataToExport($code)
    {
        $sql = 'SELECT codcuenta as cuenta, descripcion, codcuentaesp AS cuentaesp FROM cuentas'
            . ' WHERE codejercicio = ' . $code
            . ' UNION'
            . ' SELECT codsubcuenta AS cuenta, descripcion, codcuentaesp AS cuentaesp FROM subcuentas '
            . ' WHERE codejercicio = ' . $code
            . ' ORDER BY cuenta';
        $data = $this->dataBase->select($sql);

        return $data;
    }

    /**
     * Export accounting plan data to CSV file.
     * 
     * @param string $code
     *
     * @return bool
     */
    public function exportCSV($data, $code)
    {
        if (!empty($data)) {
            $fields = array('cuenta', 'descripcion', 'cuentaesp');
            $csvOutput = fopen('php://memory', "w");
            fputcsv($csvOutput, $fields, ";");
            foreach ($data as $line) {
                fputcsv($csvOutput, $line, ";");
            }
            fseek($csvOutput, 0); //move back to beginning of file
            header('Content-Encoding: UTF-8');
            header('Content-type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $code . '.csv');
            fpassthru($csvOutput);
            fclose($csvOutput);
        }
        return true;
    }

}
