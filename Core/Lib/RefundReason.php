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

namespace FacturaScripts\Core\Lib;

/**
 * Motivos de rectificación de una factura (campo motivorect de las facturas de cliente y de proveedor).
 *
 * Es un dato declarativo: no cambia los importes de la rectificativa, que siempre recoge la diferencia. Los
 * valores por defecto son las causas legales de España (art. 80 de la Ley 37/1992 del IVA), que piden VERI*FACTU,
 * el SII y TicketBAI; los plugins pueden añadir o quitar motivos con add() y remove(). Vacío = sin indicar.
 *
 * @author Daniel Fernández Giménez <contacto@danielfg.es>
 */
class RefundReason
{
    /** Art. 80 Uno, Dos y Seis LIVA o error fundado en derecho: devoluciones, descuentos, cambios de precio... */
    const ES_REFUND_REASON_80_1_2_6 = 'ES_80_1_2_6';

    /** Art. 80 Tres LIVA: concurso de acreedores. */
    const ES_REFUND_REASON_80_3 = 'ES_80_3';

    /** Art. 80 Cuatro LIVA: créditos total o parcialmente incobrables. */
    const ES_REFUND_REASON_80_4 = 'ES_80_4';

    /** Resto de causas. */
    const ES_REFUND_REASON_OTHER = 'ES_OTHER';

    /** Longitud máxima de la clave, igual que la de la columna motivorect. */
    const MAX_KEY_LENGTH = 20;

    /** @var array Motivos quitados por plugins. */
    private static $removedValues = [];

    /** @var array Motivos añadidos por plugins: clave => clave de traducción. */
    private static $values = [];

    /**
     * Añade un motivo o cambia la traducción de uno existente.
     */
    public static function add(string $key, string $value): void
    {
        $fixedKey = substr($key, 0, self::MAX_KEY_LENGTH);
        self::$values[$fixedKey] = $value;
        unset(self::$removedValues[$fixedKey]);
    }

    /**
     * Devuelve todos los motivos: clave => clave de traducción.
     */
    public static function all(): array
    {
        $defaultValues = [
            self::ES_REFUND_REASON_80_1_2_6 => 'es-refund-reason-80-1-2-6',
            self::ES_REFUND_REASON_80_3 => 'es-refund-reason-80-3',
            self::ES_REFUND_REASON_80_4 => 'es-refund-reason-80-4',
            self::ES_REFUND_REASON_OTHER => 'es-refund-reason-other',
        ];

        $all = array_merge($defaultValues, self::$values);
        foreach (array_keys(self::$removedValues) as $key) {
            unset($all[$key]);
        }

        return $all;
    }

    /**
     * Devuelve la clave de traducción de un motivo, o null si no existe.
     */
    public static function get(?string $key): ?string
    {
        return self::all()[$key] ?? null;
    }

    /**
     * Quita un motivo.
     */
    public static function remove(string $key): void
    {
        $fixedKey = substr($key, 0, self::MAX_KEY_LENGTH);
        unset(self::$values[$fixedKey]);
        self::$removedValues[$fixedKey] = true;
    }
}
