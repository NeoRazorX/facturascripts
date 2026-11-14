<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Cuenta;
use FacturaScripts\Dinamic\Model\Partida;
use FacturaScripts\Dinamic\Model\Subcuenta;

/**
 * Recalcula de forma síncrona los saldos en caché de todas las subcuentas y
 * cuentas de un ejercicio a partir de las partidas.
 *
 * Complementa a los workers PartidaWorker y CuentaWorker (asíncronos, por
 * subcuenta): las importaciones masivas y los scripts CLI no procesan la
 * WorkQueue, por lo que los saldos en caché quedan desactualizados. Esta
 * clase permite reconstruirlos todos en una sola llamada.
 *
 * @author Santiago Lopez <santilh@gmail.com>
 */
class BalanceRecalculator
{
    /** Tolerancia para no escribir cambios irrelevantes, igual que en los workers */
    const TOLERANCE = 0.009;

    /**
     * Recalcula los saldos de las subcuentas del ejercicio a partir de sus
     * partidas, y después los de las cuentas a partir de sus subcuentas y
     * cuentas hijas.
     *
     * @param string $codejercicio
     *
     * @return bool
     */
    public static function run(string $codejercicio): bool
    {
        $db = new DataBase();
        if (false === $db->connected()) {
            $db->connect();
        }

        return self::recalculateSubaccounts($db, $codejercicio)
            && self::recalculateAccounts($db, $codejercicio);
    }

    protected static function recalculateAccounts(DataBase $db, string $codejercicio): bool
    {
        // sumas de las subcuentas agrupadas por cuenta
        $totals = [];
        $sql = 'SELECT idcuenta, COALESCE(SUM(debe), 0) AS debe, COALESCE(SUM(haber), 0) AS haber'
            . ' FROM ' . Subcuenta::tableName()
            . ' WHERE codejercicio = ' . $db->var2str($codejercicio)
            . ' GROUP BY idcuenta';
        foreach ($db->select($sql) as $row) {
            $totals[$row['idcuenta']] = [
                'debe' => (float)$row['debe'],
                'haber' => (float)$row['haber']
            ];
        }

        // leemos todas las cuentas del ejercicio para poder acumular las hijas en sus padres
        $accounts = [];
        $children = [];
        $sqlAccounts = 'SELECT idcuenta, parent_idcuenta, debe, haber, saldo FROM ' . Cuenta::tableName()
            . ' WHERE codejercicio = ' . $db->var2str($codejercicio);
        foreach ($db->select($sqlAccounts) as $row) {
            $accounts[$row['idcuenta']] = $row;
            if (!empty($row['parent_idcuenta']) && $row['parent_idcuenta'] != $row['idcuenta']) {
                $children[$row['parent_idcuenta']][] = $row['idcuenta'];
            }
        }

        // calculamos cada cuenta: sus subcuentas más sus cuentas hijas (recursivo con memoria)
        $calculated = [];
        foreach (array_keys($accounts) as $idcuenta) {
            self::accountTotal($idcuenta, $totals, $children, $calculated);
        }

        // actualizamos solo las cuentas con diferencias
        foreach ($accounts as $idcuenta => $row) {
            $debe = Tools::round($calculated[$idcuenta]['debe']);
            $haber = Tools::round($calculated[$idcuenta]['haber']);
            $diffDebe = abs((float)$row['debe'] - $debe);
            $diffHaber = abs((float)$row['haber'] - $haber);
            $diffSaldo = abs((float)$row['saldo'] - ($debe - $haber));
            if ($diffDebe < self::TOLERANCE && $diffHaber < self::TOLERANCE && $diffSaldo < self::TOLERANCE) {
                continue;
            }

            $sqlUpdate = 'UPDATE ' . Cuenta::tableName()
                . ' SET debe = ' . $db->var2str($debe)
                . ', haber = ' . $db->var2str($haber)
                . ', saldo = ' . $db->var2str(Tools::round($debe - $haber))
                . ' WHERE idcuenta = ' . $db->var2str($idcuenta);
            if (false === $db->exec($sqlUpdate)) {
                return false;
            }
        }

        return true;
    }

    protected static function recalculateSubaccounts(DataBase $db, string $codejercicio): bool
    {
        // sumas reales de las partidas de cada subcuenta del ejercicio
        $sql = 'SELECT s.idsubcuenta, s.debe, s.haber, s.saldo,'
            . ' COALESCE(SUM(p.debe), 0) AS suma_debe, COALESCE(SUM(p.haber), 0) AS suma_haber'
            . ' FROM ' . Subcuenta::tableName() . ' s'
            . ' LEFT JOIN ' . Partida::tableName() . ' p ON p.idsubcuenta = s.idsubcuenta'
            . ' WHERE s.codejercicio = ' . $db->var2str($codejercicio)
            . ' GROUP BY s.idsubcuenta, s.debe, s.haber, s.saldo';

        foreach ($db->select($sql) as $row) {
            $debe = Tools::round((float)$row['suma_debe']);
            $haber = Tools::round((float)$row['suma_haber']);

            // si no hay diferencias, no escribimos
            $diffDebe = abs((float)$row['debe'] - $debe);
            $diffHaber = abs((float)$row['haber'] - $haber);
            $diffSaldo = abs((float)$row['saldo'] - ($debe - $haber));
            if ($diffDebe < self::TOLERANCE && $diffHaber < self::TOLERANCE && $diffSaldo < self::TOLERANCE) {
                continue;
            }

            $sqlUpdate = 'UPDATE ' . Subcuenta::tableName()
                . ' SET debe = ' . $db->var2str($debe)
                . ', haber = ' . $db->var2str($haber)
                . ', saldo = ' . $db->var2str(Tools::round($debe - $haber))
                . ' WHERE idsubcuenta = ' . $db->var2str($row['idsubcuenta']);
            if (false === $db->exec($sqlUpdate)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Total de una cuenta: sus subcuentas más las cuentas hijas, con memoria
     * para no recalcular y protección frente a ciclos.
     */
    private static function accountTotal($idcuenta, array &$totals, array &$children, array &$calculated, array $visited = []): array
    {
        if (isset($calculated[$idcuenta])) {
            return $calculated[$idcuenta];
        }
        if (isset($visited[$idcuenta])) {
            return ['debe' => 0.0, 'haber' => 0.0];
        }
        $visited[$idcuenta] = true;

        $total = $totals[$idcuenta] ?? ['debe' => 0.0, 'haber' => 0.0];
        foreach ($children[$idcuenta] ?? [] as $childId) {
            $childTotal = self::accountTotal($childId, $totals, $children, $calculated, $visited);
            $total['debe'] += $childTotal['debe'];
            $total['haber'] += $childTotal['haber'];
        }

        $calculated[$idcuenta] = $total;
        return $total;
    }
}
