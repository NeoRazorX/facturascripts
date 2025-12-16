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
    const ES_TAX_EXCEPTION_7 = 'ES_7'; // No sujeta – Art. 7 LIVA (aportaciones, transmisión de UEA, muestras, autoconsumo exterior, etc.)
    const ES_TAX_EXCEPTION_14 = 'ES_14'; // No sujeta – Art. 14 LIVA (regímenes aduaneros, depósitos, zonas francas, operaciones en tránsito)
    const ES_TAX_EXCEPTION_20 = 'ES_20'; // Exenta Art. 20 LIVA – Exenciones interiores (sanidad, enseñanza, seguros, banca…)
    const ES_TAX_EXCEPTION_21 = 'ES_21'; // Exenta Art. 21 LIVA – Exportaciones a países terceros
    const ES_TAX_EXCEPTION_22 = 'ES_22'; // Exenta Art. 22 LIVA – Operaciones asimiladas a exportaciones
    const ES_TAX_EXCEPTION_23_24 = 'ES_23_24'; // Exenta Arts. 23–24 LIVA – Zonas francas y depósitos aduaneros
    const ES_TAX_EXCEPTION_25 = 'ES_25'; // Exenta - Art. 25 LIVA – Entregas intracomunitarias
    const ES_TAX_EXCEPTION_68_70 = 'ES_68_70'; // No sujeta – Arts. 68–70 LIVA (reglas de localización de bienes y servicios, B2B a UE/extranjero)
    const ES_TAX_EXCEPTION_OTHER = 'ES_OTHER'; // Exenta - Otras exenciones (oro de inversión, regímenes especiales, organismos internacionales, etc.)
    const ES_OTHER_NOT_SUBJECT = 'ES_OTHER_NOT_SUBJECT'; // No sujeta – Otros supuestos no sujetos (OTAN, convenios internacionales, fuerzas armadas UE…)
    const ES_TAX_EXCEPTION_84 = 'ES_84'; // Sujeta - Inversión del sujeto pasivo Art. 84 LIVA (obras, inmuebles, residuos, oro de inversión no exento…)

    /** @var array */
    private static $values = [];

    public static function add(string $key, string $value): void
    {
        $fixedKey = substr($key, 0, 50);
        self::$values[$fixedKey] = $value;
    }

    public static function all(): array
    {
        return array_merge(self::defaults(), self::$values);
    }

    public static function get(?string $key): ?string
    {
        $values = self::all();
        return $values[$key] ?? null;
    }

    private static function defaults(): array
    {
        return [
            self::ES_TAX_EXCEPTION_20 => 'es-tax-exception-e1',
            self::ES_TAX_EXCEPTION_21 => 'es-tax-exception-e2',
            self::ES_TAX_EXCEPTION_22 => 'es-tax-exception-e3',
            self::ES_TAX_EXCEPTION_23_24 => 'es-tax-exception-e4',
            self::ES_TAX_EXCEPTION_25 => 'es-tax-exception-e5',
            self::ES_TAX_EXCEPTION_OTHER => 'es-tax-exception-e6',
            self::ES_TAX_EXCEPTION_84 => 'es-tax-exception-passive-subject',
            self::ES_TAX_EXCEPTION_7 => 'es-tax-exception-art-7',
            self::ES_TAX_EXCEPTION_14 => 'es-tax-exception-art-14',
            self::ES_TAX_EXCEPTION_68_70 => 'es-tax-exception-location-rules',
            self::ES_OTHER_NOT_SUBJECT => 'es-tax-exception-other-not-subject',
        ];
    }
}
