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

use FacturaScripts\Dinamic\Model\Cuenta;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\Export\CSVExport;
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
     * Get cuentas quantity  from Acoounting Plan
     * 
     * @param string $code
     *
     * @return integer
     */
    public function countAccounts($code)
    {
        $cuentas = new Cuenta();
        $total = $cuentas->count([new DataBaseWhere('codejercicio', $code,)]);
        return $total;
    }

    /**
     * Get Acoounting Plan data to export
     * 
     * @param string $code
     *
     * @return array
     */
    private function getDataToExport($code)
    {
        $sql = 'SELECT codcuenta as cuenta, descripcion, codcuentaesp AS cuentaesp FROM cuentas'
            . ' WHERE codejercicio = ' . $code
            . ' UNION'
            . ' SELECT codsubcuenta AS cuenta, descripcion, codcuentaesp AS cuentaesp FROM subcuentas '
            . ' WHERE codejercicio = ' . $code
            . ' ORDER BY cuenta';
        $dataBase = new DataBase();
        $data = $dataBase->select($sql);

        return $data;
    }

    /**
     * Export accounting plan data to CSV file.
     * 
     * @param string $code
     *
     * @return string
     */
    public function exportCSV($code)
    {
        $columns = array('cuenta', 'descripcion', 'cuentaesp');
        $rows = $this->getDataToExport($code);
        $csvExport = new CSVExport();
        $csvExport->generateTablePage($columns, $rows);
        return $csvExport->getDoc();
    }

}
