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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Lib\Export\CSVExport;
use FacturaScripts\Dinamic\Model\Cuenta;
use FacturaScripts\Dinamic\Model\Subcuenta;

/**
 * Class to export accounting plans.
 *
 * @author Carlos García Gómez      <carlos@facturapascripts.com>
 * @author Oscar G. Villa González  <ogvilla@gmail.com>
 */
class AccountingPlanExport
{

    /**
     * Export accounting plan data to CSV file.
     * 
     * @param string $code
     *
     * @return string
     */
    public function exportCSV($code)
    {
        $columns = ['cuenta', 'descripcion', 'cuentaesp'];
        $rows = array_merge($this->getAccountsData($code), $this->getSubaccountsData($code));

        $csvExport = new CSVExport();
        $csvExport->addTablePage($columns, $rows);
        return $csvExport->getDoc();
    }

    /**
     * 
     * @param string $code
     *
     * @return array
     */
    protected function getAccountsData($code)
    {
        $data = [];

        $cuentaModel = new Cuenta();
        $where = [new DataBaseWhere('codejercicio', $code)];
        foreach ($cuentaModel->all($where, ['codcuenta' => 'ASC'], 0, 0) as $cuenta) {
            $data[] = [
                'cuenta' => $cuenta->codcuenta,
                'descripcion' => $cuenta->descripcion,
                'codcuentaesp' => $cuenta->codcuentaesp
            ];
        }

        return $data;
    }

    /**
     * 
     * @param string $code
     *
     * @return array
     */
    protected function getSubaccountsData($code)
    {
        $data = [];

        $subcuentaModel = new Subcuenta();
        $where = [new DataBaseWhere('codejercicio', $code)];
        foreach ($subcuentaModel->all($where, ['codsubcuenta' => 'ASC'], 0, 0) as $subcuenta) {
            $data[] = [
                'cuenta' => $subcuenta->codsubcuenta,
                'descripcion' => $subcuenta->descripcion,
                'codcuentaesp' => $subcuenta->codcuentaesp
            ];
        }

        return $data;
    }
}
