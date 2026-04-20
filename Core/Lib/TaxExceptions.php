<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * This class centralizes all VAT tax exceptions and related validation rules.
 *
 * @author          Carlos García Gómez         <carlos@facturascripts.com>
 * @collaborator    Daniel Fernández Giménez    <contacto@danielfg.es>
 */
class TaxExceptions
{
    const ES_TAX_EXCEPTION_7 = 'ES_7'; // No sujeta – Art. 7 LIVA (aportaciones, transmisión de UEA, muestras, autoconsumo exterior, etc.)
    const ES_TAX_EXCEPTION_14 = 'ES_14'; // No sujeta – Art. 14 LIVA (regímenes aduaneros, depósitos, zonas francas, operaciones en tránsito)
    const ES_TAX_EXCEPTION_20 = 'ES_20'; // Exenta Art. 20 LIVA – Exenciones interiores (sanidad, enseñanza, seguros, banca…)
    const ES_TAX_EXCEPTION_21 = 'ES_21'; // Exenta Art. 21 LIVA – Exportaciones a países terceros
    const ES_TAX_EXCEPTION_22 = 'ES_22'; // Exenta Art. 22 LIVA – Operaciones asimiladas a exportaciones
    const ES_TAX_EXCEPTION_23_24 = 'ES_23_24'; // Exenta Arts. 23–24 LIVA – Zonas francas y depósitos aduaneros
    const ES_TAX_EXCEPTION_25 = 'ES_25'; // Exenta - Art. 25 LIVA – Entregas intracomunitarias
    const ES_TAX_EXCEPTION_68_70 = 'ES_68_70'; // No sujeta – Arts. 68–70 LIVA (reglas de localización de bienes y servicios, B2B a UE/extranjero)
    const ES_TAX_EXCEPTION_84 = 'ES_84'; // Sujeta - Inversión del sujeto pasivo Art. 84 LIVA (obras, inmuebles, residuos, oro de inversión no exento…)
    const ES_TAX_EXCEPTION_OTHER = 'ES_OTHER'; // Exenta - Otras exenciones (oro de inversión, regímenes especiales, organismos internacionales, etc.)
    const ES_OTHER_NOT_SUBJECT = 'ES_OTHER_NOT_SUBJECT'; // No sujeta – Otros supuestos no sujetos (OTAN, convenios internacionales, fuerzas armadas UE…)

    /** @var array */
    private static $values = [];
    /** @var array */
    private static $removedValues = [];

    public static function add(string $key, string $value): void
    {
        $fixedKey = substr($key, 0, 20);
        self::$values[$fixedKey] = $value;
        unset(self::$removedValues[$fixedKey]);
    }

    public static function all(): array
    {
        $defaultValues = [
            self::ES_TAX_EXCEPTION_7 => 'es-tax-exception-7',
            self::ES_TAX_EXCEPTION_14 => 'es-tax-exception-14',
            self::ES_TAX_EXCEPTION_20 => 'es-tax-exception-20',
            self::ES_TAX_EXCEPTION_21 => 'es-tax-exception-21',
            self::ES_TAX_EXCEPTION_22 => 'es-tax-exception-22',
            self::ES_TAX_EXCEPTION_23_24 => 'es-tax-exception-23-24',
            self::ES_TAX_EXCEPTION_25 => 'es-tax-exception-25',
            self::ES_TAX_EXCEPTION_68_70 => 'es-tax-exception-68-70',
            self::ES_TAX_EXCEPTION_84 => 'es-tax-exception-84',
            self::ES_TAX_EXCEPTION_OTHER => 'es-tax-exception-other',
            self::ES_OTHER_NOT_SUBJECT => 'es-tax-exception-other-not-subject',
        ];

        $all = array_merge($defaultValues, self::$values);
        foreach (array_keys(self::$removedValues) as $key) {
            unset($all[$key]);
        }

        return $all;
    }

    public static function get(?string $key): ?string
    {
        $values = self::all();
        return $values[$key] ?? null;
    }

    /**
     * Checks if the operation and VAT exception combination is valid.
     *
     * @param string|null $operation InvoiceOperation value (intracomunitaria, exportacion, importacion, null...)
     * @param string|null $exception VAT exception value (ES_20, ES_25, ES_PASSIVE_SUBJECT, null...)
     * @param string $context 'sales' or 'purchases'
     */
    public static function isValidCombination(?string $operation, ?string $exception, string $context): bool
    {
        $validMap = [
            InvoiceOperation::INTRA_COMMUNITY => [
                'sales' => [self::ES_TAX_EXCEPTION_22, self::ES_TAX_EXCEPTION_23_24, self::ES_TAX_EXCEPTION_25, self::ES_TAX_EXCEPTION_68_70],
                'purchases' => [self::ES_TAX_EXCEPTION_84, self::ES_TAX_EXCEPTION_7, self::ES_TAX_EXCEPTION_68_70],
            ],
            InvoiceOperation::INTRA_COMMUNITY_SERVICES => [
                'sales' => [self::ES_TAX_EXCEPTION_68_70],
                'purchases' => [self::ES_TAX_EXCEPTION_84],
            ],
            InvoiceOperation::REVERSE_CHARGE => [
                'sales' => [self::ES_TAX_EXCEPTION_84],
                'purchases' => [self::ES_TAX_EXCEPTION_84],
            ],
            InvoiceOperation::EXPORT => [
                'sales' => [self::ES_TAX_EXCEPTION_21],
                'purchases' => [],
            ],
            InvoiceOperation::IMPORT => [
                'sales' => [],
                'purchases' => [null],
            ],
        ];

        // without operation: generic exceptions or null are allowed
        if (empty($operation)) {
            $allowed = [null, self::ES_TAX_EXCEPTION_20, self::ES_TAX_EXCEPTION_OTHER, self::ES_TAX_EXCEPTION_7, self::ES_TAX_EXCEPTION_68_70, self::ES_TAX_EXCEPTION_84];
            return in_array($exception, $allowed);
        }

        // unrecognized operation: allow any combination
        if (!isset($validMap[$operation])) {
            return true;
        }

        $allowed = $validMap[$operation][$context] ?? [];
        return in_array($exception, $allowed);
    }

    public static function remove(string $key): void
    {
        $fixedKey = substr($key, 0, 20);
        unset(self::$values[$fixedKey]);
        self::$removedValues[$fixedKey] = true;
    }
}
