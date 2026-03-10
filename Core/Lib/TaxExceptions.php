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
 * @collaborator    Daniel Fernández Giménez    <hola@danielfg.es>
 */
class TaxExceptions
{
    const ES_TAX_EXCEPTION_E1 = 'ES_20'; // E1 Exenta art. 20 LIVA – Exenciones interiores (sanidad, enseñanza, seguros, financieros…)
    const ES_TAX_EXCEPTION_E2 = 'ES_21'; // E2 Exenta art. 21 LIVA – Exportaciones a países terceros
    const ES_TAX_EXCEPTION_E3 = 'ES_22'; // E3 Exenta art. 22 LIVA – Operaciones asimiladas a exportaciones
    const ES_TAX_EXCEPTION_E4 = 'ES_23_24'; // E4 Exenta arts. 23–24 LIVA – Zonas francas y depósitos aduaneros
    const ES_TAX_EXCEPTION_E5 = 'ES_25'; // E5 Exenta art. 25 LIVA – Entregas intracomunitarias de bienes
    const ES_TAX_EXCEPTION_E6 = 'ES_OTHER'; // E6 Otras exenciones (oro de inversión, regímenes especiales, etc.)
    const ES_TAX_EXCEPTION_N1 = 'ES_N1'; // N1 No sujeta – art. 7, 14 y otros (aportaciones, transmisión de UEA, muestras, operaciones vinculadas a exportaciones, OTAN, convenios…)
    const ES_TAX_EXCEPTION_N2 = 'ES_N2'; // N2 No sujeta – Reglas de localización (arts. 68–70 LIVA, entregas de bienes y servicios B2B UE o fuera UE)
    const ES_TAX_EXCEPTION_PASSIVE_SUBJECT = 'ES_PASSIVE_SUBJECT'; // Inversión del sujeto pasivo (art. 84 LIVA)

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
            self::ES_TAX_EXCEPTION_E1 => 'es-tax-exception-e1',
            self::ES_TAX_EXCEPTION_E2 => 'es-tax-exception-e2',
            self::ES_TAX_EXCEPTION_E3 => 'es-tax-exception-e3',
            self::ES_TAX_EXCEPTION_E4 => 'es-tax-exception-e4',
            self::ES_TAX_EXCEPTION_E5 => 'es-tax-exception-e5',
            self::ES_TAX_EXCEPTION_E6 => 'es-tax-exception-e6',
            self::ES_TAX_EXCEPTION_N1 => 'es-tax-exception-n1',
            self::ES_TAX_EXCEPTION_N2 => 'es-tax-exception-n2',
            self::ES_TAX_EXCEPTION_PASSIVE_SUBJECT => 'es-tax-exception-passive-subject',
        ];

        $all = array_merge($defaultValues, self::$values);
        foreach (array_keys(self::$removedValues) as $key) {
            unset($all[$key]);
        }

        return $all;
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
                'sales' => [self::ES_TAX_EXCEPTION_E3, self::ES_TAX_EXCEPTION_E4, self::ES_TAX_EXCEPTION_E5, self::ES_TAX_EXCEPTION_N2],
                'purchases' => [self::ES_TAX_EXCEPTION_PASSIVE_SUBJECT, self::ES_TAX_EXCEPTION_N1, self::ES_TAX_EXCEPTION_N2],
            ],
            InvoiceOperation::INTRA_COMMUNITY_SERVICES => [
                'sales' => [self::ES_TAX_EXCEPTION_N2],
                'purchases' => [self::ES_TAX_EXCEPTION_PASSIVE_SUBJECT],
            ],
            InvoiceOperation::REVERSE_CHARGE => [
                'sales' => [self::ES_TAX_EXCEPTION_PASSIVE_SUBJECT],
                'purchases' => [self::ES_TAX_EXCEPTION_PASSIVE_SUBJECT],
            ],
            InvoiceOperation::EXPORT => [
                'sales' => [self::ES_TAX_EXCEPTION_E2],
                'purchases' => [],
            ],
            InvoiceOperation::IMPORT => [
                'sales' => [],
                'purchases' => [null],
            ],
        ];

        // without operation: generic exceptions or null are allowed
        if (empty($operation)) {
            $allowed = [null, self::ES_TAX_EXCEPTION_E1, self::ES_TAX_EXCEPTION_E6, self::ES_TAX_EXCEPTION_N1, self::ES_TAX_EXCEPTION_N2, self::ES_TAX_EXCEPTION_PASSIVE_SUBJECT];
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
