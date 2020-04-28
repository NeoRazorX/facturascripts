<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\TotalModel;

/**
 * Set of tools for the management of customer payment risk
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class CustomerRiskTools
{
    // pedidos

    /**
     * Returns the sum of the customer's pending delivery notes.
     *
     * @return string
     */
    public function deliveryNotesPending(string $customer, int $company = 0)
    {
        $sql = "SELECT COALESCE(SUM((t2.pvptotal / t2.cantidad) * (t2.cantidad - t2.servido)), 0.00) AS total"
            . " FROM albaranescli t1 "
            . " LEFT JOIN lineasalbaranescli t2 ON t2.idalbaran = t1.idalbaran AND (t2.cantidad - t2.servido) <> 0"
            . " WHERE t1.codcliente = '" . $customer . "'"
            . " AND t1.editable = true";

        if ($company > 0) {
            $sql .= " AND t1.idempresa = " . $company;
        }
        return $this->dataBase()->select($sql)[0]['total'];
    }

    /**
     * It provides direct access to the database.
     *
     * @var DataBase
     */
    protected static $dataBase;

    /**
     * Returns the sum of the customer's total outstanding invoices.
     *
     * @return string
     */
    public function invoicesPending(string $customer, int $company = 0)
    {
        $where = [
            new DataBaseWhere('codcliente', $customer),
            new DataBaseWhere('pagado', false)
        ];

        if ($company > 0) {
            $where[] = new DataBaseWhere('idempresa', $company);
        }

        $totalModel = TotalModel::all('recibospagoscli', $where, ['total' => 'SUM(importe + gastos)'], '')[0];
        return $totalModel->totals['total'];
    }

    /**
     * Returns the sum of the customer's pending orders.
     *
     * @return string
     */
    public function ordersPending(string $customer, int $company = 0)
    {
        $sql = "SELECT COALESCE(SUM((t2.pvptotal / t2.cantidad) * (t2.cantidad - t2.servido)), 0.00) AS total"
            . " FROM pedidoscli t1 "
            . " LEFT JOIN lineaspedidoscli t2 ON t2.idpedido = t1.idpedido AND (t2.cantidad - t2.servido) <> 0"
            . " WHERE t1.codcliente = '" . $customer . "'"
            . " AND t1.editable = true";

        if ($company > 0) {
            $sql .= " AND t1.idempresa = " . $company;
        }
        return $this->dataBase()->select($sql)[0]['total'];
    }

    /**
     *
     * @return DataBase
     */
    protected function database()
    {
        if (self::$dataBase === null) {
            self::$dataBase = new DataBase();
        }

        return self::$dataBase;
    }
}
