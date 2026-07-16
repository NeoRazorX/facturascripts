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
use FacturaScripts\Core\Model\Asiento;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Partida;

/**
 * Balance de sumas y saldos calculado directamente desde las partidas (nunca
 * desde los saldos en caché de subcuentas/cuentas, que pueden estar
 * desactualizados tras importaciones masivas). Agrupa por grupo (1 dígito),
 * cuenta (3 dígitos) o subcuenta, con filtro opcional de fechas, y excluye
 * por defecto los asientos de regularización y cierre.
 *
 * @author Santiago Lopez <santilh@gmail.com>
 */
class TrialBalance
{
    const LEVEL_GROUP = 'grupo';
    const LEVEL_ACCOUNT = 'cuenta';
    const LEVEL_SUBACCOUNT = 'subcuenta';

    /**
     * Devuelve el balance de sumas y saldos del ejercicio como lista de filas
     * ['codigo' => string, 'debe' => float, 'haber' => float, 'saldo' => float],
     * ordenadas por código.
     *
     * Parámetros opcionales:
     * - fecha_desde / fecha_hasta: acotar por fecha de asiento
     * - include_closing: incluir los asientos de regularización (R) y cierre (C),
     *   excluidos por defecto para que el balance no salga a cero en ejercicios cerrados
     *
     * @param string $codejercicio
     * @param string $level una de las constantes LEVEL_*
     * @param array $params
     *
     * @return array
     */
    public static function generate(string $codejercicio, string $level = self::LEVEL_GROUP, array $params = []): array
    {
        $lengths = [
            self::LEVEL_GROUP => 1,
            self::LEVEL_ACCOUNT => 3,
            self::LEVEL_SUBACCOUNT => 10
        ];
        $length = $lengths[$level] ?? 1;

        $db = new DataBase();
        $sql = 'SELECT SUBSTR(p.codsubcuenta, 1, ' . $length . ') AS codigo,'
            . ' COALESCE(SUM(p.debe), 0) AS debe, COALESCE(SUM(p.haber), 0) AS haber'
            . ' FROM ' . Partida::tableName() . ' p'
            . ' JOIN ' . Asiento::tableName() . ' a ON a.idasiento = p.idasiento'
            . ' WHERE a.codejercicio = ' . $db->var2str($codejercicio);

        if (empty($params['include_closing'])) {
            $sql .= " AND (a.operacion IS NULL OR a.operacion NOT IN ("
                . $db->var2str(Asiento::OPERATION_REGULARIZATION) . ', '
                . $db->var2str(Asiento::OPERATION_CLOSING) . '))';
        }
        if (!empty($params['fecha_desde'])) {
            $sql .= ' AND a.fecha >= ' . $db->var2str($params['fecha_desde']);
        }
        if (!empty($params['fecha_hasta'])) {
            $sql .= ' AND a.fecha <= ' . $db->var2str($params['fecha_hasta']);
        }

        $sql .= ' GROUP BY SUBSTR(p.codsubcuenta, 1, ' . $length . ') ORDER BY codigo';

        $rows = [];
        foreach ($db->select($sql) as $row) {
            $debe = Tools::round((float)$row['debe']);
            $haber = Tools::round((float)$row['haber']);
            $rows[] = [
                'codigo' => $row['codigo'],
                'debe' => $debe,
                'haber' => $haber,
                'saldo' => Tools::round($debe - $haber)
            ];
        }

        return $rows;
    }

    /**
     * Resultado del ejercicio calculado desde las partidas: la suma de
     * (haber - debe) de los grupos 6 y 7. Positivo = beneficio.
     *
     * @param string $codejercicio
     * @param array $params los mismos que generate()
     *
     * @return float
     */
    public static function result(string $codejercicio, array $params = []): float
    {
        $result = 0.0;
        foreach (self::generate($codejercicio, self::LEVEL_GROUP, $params) as $row) {
            if (in_array($row['codigo'], ['6', '7'])) {
                $result += $row['haber'] - $row['debe'];
            }
        }

        return Tools::round($result);
    }
}
