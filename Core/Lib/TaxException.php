<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * This class centralizes all common method for TAX Exceptions.
 *
 * @author Daniel Fernández Giménez    <hola@danielfg.es>
 */
class TaxException
{
    const ES_TAX_EXCEPTION_ART_7 = 'ES_ART_7'; // N3 No sujeta art. 7 LIVA – Operaciones no sujetas (aportaciones, transmisión de UEA, muestras…)
    const ES_TAX_EXCEPTION_ART_14 = 'ES_ART_14'; // N4 No sujeta art. 14 LIVA – Operaciones vinculadas a exportaciones
    const ES_TAX_EXCEPTION_E1 = 'ES_20'; // E1 Exenta art. 20 LIVA – Exenciones interiores (sanidad, enseñanza, seguros, financieros…)
    const ES_TAX_EXCEPTION_E2 = 'ES_21'; // E2 Exenta art. 21 LIVA – Exportaciones a países terceros
    const ES_TAX_EXCEPTION_E3 = 'ES_22'; // E3 Exenta art. 22 LIVA – Operaciones asimiladas a exportaciones
    const ES_TAX_EXCEPTION_E4 = 'ES_23_24'; // E4 Exenta arts. 23–24 LIVA – Zonas francas y depósitos aduaneros
    const ES_TAX_EXCEPTION_E5 = 'ES_25'; // E5 Exenta art. 25 LIVA – Entregas intracomunitarias de bienes
    const ES_TAX_EXCEPTION_E6 = 'ES_OTHER'; // E6 Otras exenciones (oro de inversión, regímenes especiales, etc.)
    const ES_TAX_EXCEPTION_LOCATION_RULES = 'ES_LOCATION_RULES'; // N2 No sujeta – Reglas de localización de servicios (arts. 69–70 LIVA, servicios B2B UE o fuera UE)
    const ES_TAX_EXCEPTION_N1 = 'ES_N1'; // No sujeta N1 – Reglas de localización entregas de bienes (art. 68 LIVA)
    const ES_TAX_EXCEPTION_N5 = 'ES_N5'; // No sujeta N5 – Otras disposiciones específicas (OTAN, convenios internacionales…)
    const ES_TAX_EXCEPTION_PASSIVE_SUBJECT = 'ES_PASSIVE_SUBJECT'; // S2 Inversión del sujeto pasivo (art. 84 LIVA)

    /** @var array */
    private static $values = [];

    public static function add(string $key, string $value): void
    {
        $fixedKey = substr($key, 0, 20);
        self::$values[$fixedKey] = $value;
    }

    public static function all(): array
    {
        return array_merge(self::defaults(), self::$values);
    }

    public static function get(string $key): ?string
    {
        $values = self::all();
        return $values[$key] ?? null;
    }

    private static function defaults(): array
    {
        return [
            self::ES_TAX_EXCEPTION_E1 => 'es-tax-exception-e1',
            self::ES_TAX_EXCEPTION_E2 => 'es-tax-exception-e2',
            self::ES_TAX_EXCEPTION_E3 => 'es-tax-exception-e3',
            self::ES_TAX_EXCEPTION_E4 => 'es-tax-exception-e4',
            self::ES_TAX_EXCEPTION_E5 => 'es-tax-exception-e5',
            self::ES_TAX_EXCEPTION_E6 => 'es-tax-exception-e6',
            self::ES_TAX_EXCEPTION_PASSIVE_SUBJECT => 'es-tax-exception-passive-subject',
            self::ES_TAX_EXCEPTION_ART_7 => 'es-tax-exception-art-7',
            self::ES_TAX_EXCEPTION_ART_14 => 'es-tax-exception-art-14',
            self::ES_TAX_EXCEPTION_LOCATION_RULES => 'es-tax-exception-location-rules',
            self::ES_TAX_EXCEPTION_N1 => 'es-tax-exception-n1',
            self::ES_TAX_EXCEPTION_N5 => 'es-tax-exception-n5',
        ];
    }
}
