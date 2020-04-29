<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Set of tools for the management of customer payment risk
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class CustomerRiskTools
{

    /**
     * Provides direct access to the database.
     *
     * @var DataBase
     */
    private static $dataBase;

    /**
     * Returns the current customer's risk.
     * 
     * @param string $codcliente
     * @param int    $idempresa
     *
     * @return float
     */
    public static function getCurrent($codcliente, $idempresa = null): float
    {
        return static::getInvoicesRisk($codcliente, $idempresa) +
            static::getDeliveryNotesRisk($codcliente, $idempresa) +
            static::getOrdersRisk($codcliente, $idempresa);
    }

    /**
     * Returns the sum of the customer's pending delivery notes.
     * 
     * @param string $codcliente
     * @param int    $idempresa
     *
     * @return float
     */
    public static function getDeliveryNotesRisk($codcliente, $idempresa = null): float
    {
        $sql = "SELECT SUM(total) AS total FROM albaranescli"
            . " WHERE codcliente = " . static::database()->var2str($codcliente)
            . " AND editable = true";
        if (null !== $idempresa) {
            $sql .= " AND idempresa = " . static::database()->var2str($idempresa);
        }

        foreach (static::dataBase()->select($sql) as $item) {
            return (float) $item['total'];
        }

        return 0.0;
    }

    /**
     * Returns the sum of the customer's unpaid invoices receipts.
     * 
     * @param string $codcliente
     * @param int    $idempresa
     *
     * @return float
     */
    public static function getInvoicesRisk($codcliente, $idempresa = null): float
    {
        $sql = "SELECT SUM(importe) AS total FROM recibospagoscli"
            . " WHERE codcliente = " . static::database()->var2str($codcliente)
            . " AND pagado = false";
        if (null !== $idempresa) {
            $sql .= " AND idempresa = " . static::database()->var2str($idempresa);
        }

        foreach (static::dataBase()->select($sql) as $item) {
            return (float) $item['total'];
        }

        return 0.0;
    }

    /**
     * Returns the sum of the customer's pending orders.
     * 
     * @param string $codcliente
     * @param int    $idempresa
     *
     * @return float
     */
    public static function getOrdersRisk($codcliente, $idempresa = null): float
    {
        $sql = "SELECT SUM(total) AS total FROM pedidoscli"
            . " WHERE codcliente = " . static::database()->var2str($codcliente)
            . " AND editable = true";
        if (null !== $idempresa) {
            $sql .= " AND idempresa = " . static::database()->var2str($idempresa);
        }

        foreach (static::dataBase()->select($sql) as $item) {
            return (float) $item['total'];
        }

        return 0.0;
    }

    /**
     *
     * @return DataBase
     */
    protected static function database()
    {
        if (null === self::$dataBase) {
            self::$dataBase = new DataBase();
        }

        return self::$dataBase;
    }
}
