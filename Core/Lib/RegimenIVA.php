<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * This class centralizes all common method for VAT Regime.
 *
 * @author          Carlos García Gómez         <carlos@facturascripts.com>
 * @collaborator    Daniel Fernández Giménez    <hola@danielfg.es>
 */
class RegimenIVA
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
    const TAX_SYSTEM_AGRARIAN = 'Agrario';
    const TAX_SYSTEM_CASH_CRITERIA = 'Caja';
    const TAX_SYSTEM_EXEMPT = 'Exento';
    const TAX_SYSTEM_GENERAL = 'General';
    const TAX_SYSTEM_GOLD = 'Oro';
    const TAX_SYSTEM_GROUP_ENTITIES = 'Grupo entidades';
    const TAX_SYSTEM_ONE_STOP_SHOP_OSS = 'One Stop Shop (OSS)';
    const TAX_SYSTEM_ONE_STOP_SHOP_IOSS = 'One Stop Shop (IOSS)';
    const TAX_SYSTEM_SIMPLIFIED = 'Simplificado';
    const TAX_SYSTEM_SPECIAL_SMALL_BUSINESS = 'Pequeño empresario';
    const TAX_SYSTEM_SURCHARGE = 'Recargo';
    const TAX_SYSTEM_TRAVEL = 'Agencias de viaje';
    const TAX_SYSTEM_USED_GOODS = 'Bienes usados';

    /** @var array */
    private static $exceptions = [];

    /** @var array */
    private static $values = [];

    public static function add(string $key, string $value): void
    {
        $fixedKey = substr($key, 0, 20);
        self::$exceptions[$fixedKey] = $value;
    }

    public static function addException(string $key, string $value): void
    {
        $fixedKey = substr($key, 0, 20);
        self::$exceptions[$fixedKey] = $value;
    }

    public static function all(): array
    {
        $defaultValues = [
            self::TAX_SYSTEM_AGRARIAN => 'es-tax-regime-agrarian',
            self::TAX_SYSTEM_CASH_CRITERIA => 'es-tax-regime-cash-criteria',
            self::TAX_SYSTEM_EXEMPT => 'es-tax-regime-exempt',
            self::TAX_SYSTEM_GENERAL => 'es-tax-regime-general',
            self::TAX_SYSTEM_GOLD => 'es-tax-regime-gold',
            self::TAX_SYSTEM_GROUP_ENTITIES => 'es-tax-regime-group-entities',
            self::TAX_SYSTEM_SIMPLIFIED => 'es-tax-regime-simplified',
            self::TAX_SYSTEM_SURCHARGE => 'es-tax-regime-surcharge',
            self::TAX_SYSTEM_TRAVEL => 'es-tax-regime-travel',
            self::TAX_SYSTEM_USED_GOODS => 'es-tax-regime-used-goods',
            self::TAX_SYSTEM_SPECIAL_SMALL_BUSINESS => 'es-tax-regime-special-small-business',
            self::TAX_SYSTEM_ONE_STOP_SHOP_OSS => 'es-tax-regime-one-stop-shop-oss',
            self::TAX_SYSTEM_ONE_STOP_SHOP_IOSS => 'es-tax-regime-one-stop-shop-ioss',
        ];

        return array_merge($defaultValues, self::$values);
    }

    public static function allExceptions(): array
    {
        $defaultExceptions = [
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

        return array_merge($defaultExceptions, self::$exceptions);
    }

    public static function defaultValue(): string
    {
        return self::TAX_SYSTEM_GENERAL;
    }

    /**
     * Comprueba si la combinación de operación y excepción de IVA es válida.
     *
     * @param string|null $operation Valor de InvoiceOperation (intracomunitaria, exportacion, importacion, null…)
     * @param string|null $exception Valor de excepción de IVA (ES_20, ES_25, ES_PASSIVE_SUBJECT, null…)
     * @param string $context 'sales' o 'purchases'
     */
    public static function isValidCombination(?string $operation, ?string $exception, string $context): bool
    {
        $validMap = [
            'intracomunitaria' => [
                'sales' => [self::ES_TAX_EXCEPTION_E3, self::ES_TAX_EXCEPTION_E4, self::ES_TAX_EXCEPTION_E5, self::ES_TAX_EXCEPTION_N2],
                'purchases' => [self::ES_TAX_EXCEPTION_PASSIVE_SUBJECT, self::ES_TAX_EXCEPTION_N1, self::ES_TAX_EXCEPTION_N2],
            ],
            'exportacion' => [
                'sales' => [self::ES_TAX_EXCEPTION_E2],
                'purchases' => [],
            ],
            'importacion' => [
                'sales' => [],
                'purchases' => [null],
            ],
        ];

        // sin operación: se permiten excepciones genéricas o null
        if (empty($operation)) {
            $allowed = [null, self::ES_TAX_EXCEPTION_E1, self::ES_TAX_EXCEPTION_E6, self::ES_TAX_EXCEPTION_N1, self::ES_TAX_EXCEPTION_N2, self::ES_TAX_EXCEPTION_PASSIVE_SUBJECT];
            return in_array($exception, $allowed);
        }

        // operación no reconocida: permitimos cualquier combinación
        if (!isset($validMap[$operation])) {
            return true;
        }

        $allowed = $validMap[$operation][$context] ?? [];
        return in_array($exception, $allowed);
    }
}
