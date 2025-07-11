<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Devuelve el riesgo actual de un cliente.
 *
 * @author Jose Antonio Cuello Principal    <yopli2000@gmail.com>
 * @author Carlos García Gómez              <carlos@facturascripts.com>
 */
class CustomerRiskTools
{
    /** @var DataBase */
    private static $dataBase;

    /**
     * Devuelve el riesgo actual de un cliente.
     *
     * @param string $codcliente
     * @param ?int $idempresa
     *
     * @return float
     */
    public static function getCurrent(string $codcliente, ?int $idempresa = null): float
    {
        return static::getInvoicesRisk($codcliente, $idempresa) +
            static::getDeliveryNotesRisk($codcliente, $idempresa) +
            static::getOrdersRisk($codcliente, $idempresa);
    }

    /**
     * Devuelve el riesgo actual en albaranes del cliente.
     *
     * @param string $codcliente
     * @param ?int $idempresa
     *
     * @return float
     */
    public static function getDeliveryNotesRisk(string $codcliente, ?int $idempresa = null): float
    {
        // comprobamos que la tabla albaranescli existe
        if (!static::dataBase()->tableExists('albaranescli')) {
            return 0.0;
        }

        $sql = "SELECT SUM(total) AS total FROM albaranescli"
            . " WHERE codcliente = " . static::database()->var2str($codcliente)
            . " AND editable = true";
        if (null !== $idempresa) {
            $sql .= " AND idempresa = " . static::database()->var2str($idempresa);
        }

        foreach (static::dataBase()->select($sql) as $item) {
            return (float)$item['total'];
        }

        return 0.0;
    }

    /**
     * Devuelve el riesgo actual de facturas del cliente.
     *
     * @param string $codcliente
     * @param ?int $idempresa
     *
     * @return float
     */
    public static function getInvoicesRisk(string $codcliente, ?int $idempresa = null): float
    {
        // comprobamos que las tablas facturascli y recibospagoscli existen
        if (!static::dataBase()->tableExists('facturascli') ||
            !static::dataBase()->tableExists('recibospagoscli')) {
            return 0.0;
        }

        $unpaidInvoicesAmount = static::getUnpaidInvoices($codcliente, $idempresa);
        if ($unpaidInvoicesAmount == 0.0) {
            // si no hay facturas pendientes de cobro, no hay que calcular el riesgo.
            return 0.0;
        }

        $sqlInvoices = "SELECT idfactura FROM facturascli"
            . " WHERE codcliente = " . static::database()->var2str($codcliente)
            . " AND pagada = false";
        if (null !== $idempresa) {
            $sqlInvoices .= " AND idempresa = " . static::database()->var2str($idempresa);
        }

        $sqlReceipt = "SELECT SUM(importe) AS total FROM recibospagoscli"
            . " WHERE codcliente = " . static::database()->var2str($codcliente)
            . " AND idfactura in (" . $sqlInvoices . ")"
            . " AND pagado = true";
        if (null !== $idempresa) {
            $sqlReceipt .= " AND idempresa = " . static::database()->var2str($idempresa);
        }

        foreach (static::dataBase()->select($sqlReceipt) as $item) {
            return (float)($unpaidInvoicesAmount - $item['total']);
        }

        return 0.0;
    }

    /**
     * Devuelve el importe total de las facturas pendientes de cobro de un cliente.
     *
     * @param string $codcliente
     * @param ?int $idempresa
     *
     * @return float
     */
    protected static function getUnpaidInvoices(string $codcliente, ?int $idempresa = null): float
    {
        $sql = "SELECT SUM(total) AS total FROM facturascli"
            . " WHERE codcliente = " . static::database()->var2str($codcliente)
            . " AND pagada = false";
        if (null !== $idempresa) {
            $sql .= " AND idempresa = " . static::database()->var2str($idempresa);
        }

        foreach (static::dataBase()->select($sql) as $item) {
            return (float)$item['total'];
        }

        return 0.0;
    }

    /**
     * Devuelve el riesgo actual de pedidos del cliente.
     *
     * @param string $codcliente
     * @param ?int $idempresa
     *
     * @return float
     */
    public static function getOrdersRisk(string $codcliente, ?int $idempresa = null): float
    {
        // comprobamos que la tabla pedidoscli existe
        if (!static::dataBase()->tableExists('pedidoscli')) {
            return 0.0;
        }

        $sql = "SELECT SUM(total) AS total FROM pedidoscli"
            . " WHERE codcliente = " . static::database()->var2str($codcliente)
            . " AND editable = true";
        if (null !== $idempresa) {
            $sql .= " AND idempresa = " . static::database()->var2str($idempresa);
        }

        foreach (static::dataBase()->select($sql) as $item) {
            return (float)$item['total'];
        }

        return 0.0;
    }

    protected static function database(): DataBase
    {
        if (null === self::$dataBase) {
            self::$dataBase = new DataBase();
        }

        return self::$dataBase;
    }
}
