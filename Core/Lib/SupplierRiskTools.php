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
 * Description of SupplierRiskTools
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class SupplierRiskTools
{

    /**
     * Provides direct access to the database.
     *
     * @var DataBase
     */
    private static $dataBase;

    /**
     * Returns the current supplier's risk.
     * 
     * @param string $codproveedor
     * @param int    $idempresa
     *
     * @return float
     */
    public static function getCurrent($codproveedor, $idempresa = null): float
    {
        return static::getInvoicesRisk($codproveedor, $idempresa) +
            static::getDeliveryNotesRisk($codproveedor, $idempresa) +
            static::getOrdersRisk($codproveedor, $idempresa);
    }

    /**
     * Returns the sum of the supplier's pending delivery notes.
     * 
     * @param string $codproveedor
     * @param int    $idempresa
     *
     * @return float
     */
    public static function getDeliveryNotesRisk($codproveedor, $idempresa = null): float
    {
        $sql = "SELECT SUM(total) AS total FROM albaranesprov"
            . " WHERE codproveedor = " . static::database()->var2str($codproveedor)
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
     * Returns the sum of the supplier's unpaid invoices receipts.
     * 
     * @param string $codproveedor
     * @param int    $idempresa
     *
     * @return float
     */
    public static function getInvoicesRisk($codproveedor, $idempresa = null): float
    {
        $sql = "SELECT SUM(importe) AS total FROM recibospagosprov"
            . " WHERE codproveedor = " . static::database()->var2str($codproveedor)
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
     * Returns the sum of the supplier's pending orders.
     * 
     * @param string $codproveedor
     * @param int    $idempresa
     *
     * @return float
     */
    public static function getOrdersRisk($codproveedor, $idempresa = null): float
    {
        $sql = "SELECT SUM(total) AS total FROM pedidosprov"
            . " WHERE codproveedor = " . static::database()->var2str($codproveedor)
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
