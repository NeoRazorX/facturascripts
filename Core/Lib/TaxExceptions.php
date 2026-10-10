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
    /** No sujeta – Art. 7 LIVA (transmisión de un negocio, muestras, servicios en relación laboral...). Verifactu N1. */
    const ES_TAX_EXCEPTION_7 = 'ES_7';

    /** No sujeta – Art. 14 LIVA: adquisiciones intracomunitarias no sujetas (solo compras). Verifactu N1. */
    const ES_TAX_EXCEPTION_14 = 'ES_14';

    /** Exenta – Art. 20 LIVA: exenciones interiores (sanidad, enseñanza, seguros, financieras...). Verifactu E1. */
    const ES_TAX_EXCEPTION_20 = 'ES_20';

    /** Exenta – Art. 21 LIVA: exportaciones a terceros países. Verifactu E2. */
    const ES_TAX_EXCEPTION_21 = 'ES_21';

    /** Exenta – Art. 22 LIVA: operaciones asimiladas a exportaciones (buques, aeronaves, OTAN...). Verifactu E3. */
    const ES_TAX_EXCEPTION_22 = 'ES_22';

    /** Exenta – Arts. 23–24 LIVA: zonas francas, depósitos y regímenes aduaneros y fiscales. Verifactu E4. */
    const ES_TAX_EXCEPTION_23_24 = 'ES_23_24';

    /** Exenta – Art. 25 LIVA: entregas intracomunitarias de bienes. Verifactu E5. */
    const ES_TAX_EXCEPTION_25 = 'ES_25';

    /** No sujeta – Arts. 68–70 LIVA: reglas de localización, la operación tributa fuera de España. Verifactu N2. */
    const ES_TAX_EXCEPTION_68_70 = 'ES_68_70';

    /** Sujeta – Inversión del sujeto pasivo, arts. 84 y 85 LIVA (AIB, servicios de no establecidos, ISP doméstico). Verifactu S2. */
    const ES_TAX_EXCEPTION_84 = 'ES_84';

    /** Exenta – Otras exenciones (oro de inversión art. 140 bis, art. 26, régimen agrario...). Verifactu E6. */
    const ES_TAX_EXCEPTION_OTHER = 'ES_OTHER';

    /** No sujeta – Otros supuestos de no sujeción (cobros por cuenta de terceros, indemnizaciones...). Verifactu N1. */
    const ES_OTHER_NOT_SUBJECT = 'ES_OTHER_NOT_SUBJECT';

    /** @var array Excepciones añadidas o sobrescritas por plugins mediante add(). */
    private static $values = [];
    /** @var array Excepciones eliminadas por plugins mediante remove(). */
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
     * Comprueba si la combinación de operación y excepción de IVA es válida.
     *
     * Para las operaciones especiales solo se admiten las excepciones que CalculatorModSpain pone en
     * las líneas, para que lo que se guarda en el cliente o proveedor sea lo que sale en los documentos:
     * - Ventas intracomunitarias: ES_25. Compras intracomunitarias: ES_84.
     * - Servicios intracomunitarios: ES_68_70 en ventas y ES_84 en compras.
     *
     * Sin operación se admiten las excepciones genéricas o ninguna. ES_14 solo en compras, porque
     * se refiere a adquisiciones intracomunitarias no sujetas. Una operación no reconocida (añadida
     * por plugins) admite cualquier combinación.
     *
     * @param string|null $operation valor de InvoiceOperation (intracomunitaria, exportacion, importacion, null...)
     * @param string|null $exception código de excepción de IVA (ES_20, ES_25, ES_84, null...)
     * @param string $context 'sales' o 'purchases'
     *
     * @return bool
     */
    public static function isValidCombination(?string $operation, ?string $exception, string $context): bool
    {
        $validMap = [
            InvoiceOperation::INTRA_COMMUNITY => [
                'sales' => [self::ES_TAX_EXCEPTION_25],
                'purchases' => [self::ES_TAX_EXCEPTION_84],
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

        // sin operación: se admiten las excepciones genéricas o ninguna
        if (empty($operation)) {
            $allowed = [
                null,
                self::ES_TAX_EXCEPTION_7,
                self::ES_TAX_EXCEPTION_20,
                self::ES_TAX_EXCEPTION_22,
                self::ES_TAX_EXCEPTION_23_24,
                self::ES_TAX_EXCEPTION_68_70,
                self::ES_TAX_EXCEPTION_84,
                self::ES_TAX_EXCEPTION_OTHER,
                self::ES_OTHER_NOT_SUBJECT,
            ];

            // las adquisiciones intracomunitarias no sujetas (art. 14) solo existen en compras
            if ($context === 'purchases') {
                $allowed[] = self::ES_TAX_EXCEPTION_14;
            }

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
