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
use FacturaScripts\Dinamic\Model\Partida;

/**
 * Concilia las líneas de un extracto bancario (fecha + importe) con las
 * partidas del libro diario: cada línea se empareja con la partida más
 * cercana en fecha (dentro de una tolerancia de días) cuyo importe coincida.
 * Los importes positivos casan con el debe (entradas) y los negativos con
 * el haber (salidas). Cada partida solo se empareja una vez.
 *
 * @author Santiago Lopez <santilh@gmail.com>
 */
class BankStatementMatcher
{
    /** Tolerancia de importes al comparar con las partidas */
    const AMOUNT_TOLERANCE = 0.005;

    /**
     * Empareja las líneas del extracto con las partidas.
     *
     * Cada línea del extracto es un array con al menos:
     * - fecha: 'Y-m-d'
     * - importe: positivo = entrada (debe), negativo = salida (haber)
     * El resto de claves de la línea se conservan en el resultado.
     *
     * Parámetros opcionales:
     * - codsubcuenta: limitar a una subcuenta o prefijo (p. ej. '572')
     * - codejercicio: limitar a un ejercicio
     * - days: tolerancia de días entre extracto y asiento (3 por defecto)
     *
     * @param array $lines
     * @param array $params
     *
     * @return array ['matched' => array, 'unmatched' => array]
     */
    public static function match(array $lines, array $params = []): array
    {
        $days = (int)($params['days'] ?? 3);
        $db = new DataBase();

        $matched = [];
        $unmatched = [];
        $usedPartidas = [];
        foreach ($lines as $line) {
            $amount = (float)($line['importe'] ?? 0);
            $date = $line['fecha'] ?? '';
            if (empty($date) || abs($amount) < self::AMOUNT_TOLERANCE) {
                $unmatched[] = $line;
                continue;
            }

            $best = null;
            $bestDistance = null;
            foreach (self::candidates($db, $amount, $date, $days, $params) as $row) {
                if (isset($usedPartidas[$row['idpartida']])) {
                    continue;
                }

                $distance = abs((strtotime($row['fecha']) - strtotime($date)) / 86400);
                if ($bestDistance === null || $distance < $bestDistance) {
                    $best = $row;
                    $bestDistance = $distance;
                }
            }

            if ($best === null) {
                $unmatched[] = $line;
                continue;
            }

            $usedPartidas[$best['idpartida']] = true;
            $matched[] = [
                'line' => $line,
                'idpartida' => (int)$best['idpartida'],
                'idasiento' => (int)$best['idasiento'],
                'fecha' => $best['fecha'],
                'codsubcuenta' => $best['codsubcuenta'],
                'concepto' => $best['concepto'],
                'dias' => (int)round($bestDistance)
            ];
        }

        return ['matched' => $matched, 'unmatched' => $unmatched];
    }

    protected static function candidates(DataBase $db, float $amount, string $date, int $days, array $params): array
    {
        // los importes positivos casan con el debe, los negativos con el haber
        $column = $amount >= 0 ? 'p.debe' : 'p.haber';
        $fromDate = date('Y-m-d', strtotime($date . ' -' . $days . ' days'));
        $toDate = date('Y-m-d', strtotime($date . ' +' . $days . ' days'));

        $sql = 'SELECT p.idpartida, p.idasiento, p.codsubcuenta, p.concepto, a.fecha'
            . ' FROM ' . Partida::tableName() . ' p'
            . ' JOIN ' . Asiento::tableName() . ' a ON a.idasiento = p.idasiento'
            . ' WHERE ABS(' . $column . ' - ' . $db->var2str(abs($amount)) . ') <= ' . $db->var2str(self::AMOUNT_TOLERANCE)
            . ' AND a.fecha >= ' . $db->var2str($fromDate)
            . ' AND a.fecha <= ' . $db->var2str($toDate);

        if (!empty($params['codsubcuenta'])) {
            $code = $params['codsubcuenta'];
            $pattern = strpos($code, '%') === false && strlen($code) < 10 ? $code . '%' : $code;
            $sql .= ' AND p.codsubcuenta LIKE ' . $db->var2str($pattern);
        }
        if (!empty($params['codejercicio'])) {
            $sql .= ' AND a.codejercicio = ' . $db->var2str($params['codejercicio']);
        }

        return $db->select($sql . ' ORDER BY p.idpartida');
    }
}
