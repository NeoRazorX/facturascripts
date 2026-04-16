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
 * This class centralizes all common method for VAT Regime.
 *
 * @author          Carlos García Gómez         <carlos@facturascripts.com>
 * @collaborator    Daniel Fernández Giménez    <contacto@danielfg.es>
 */
class RegimenIVA
{
    // Deprecated: use TaxExceptions constants.
    const ES_TAX_EXCEPTION_E1 = TaxExceptions::ES_TAX_EXCEPTION_20;
    const ES_TAX_EXCEPTION_E2 = TaxExceptions::ES_TAX_EXCEPTION_21;
    const ES_TAX_EXCEPTION_E3 = TaxExceptions::ES_TAX_EXCEPTION_22;
    const ES_TAX_EXCEPTION_E4 = TaxExceptions::ES_TAX_EXCEPTION_23_24;
    const ES_TAX_EXCEPTION_E5 = TaxExceptions::ES_TAX_EXCEPTION_25;
    const ES_TAX_EXCEPTION_E6 = TaxExceptions::ES_TAX_EXCEPTION_OTHER;
    const ES_TAX_EXCEPTION_N1 = TaxExceptions::ES_TAX_EXCEPTION_7;
    const ES_TAX_EXCEPTION_N2 = TaxExceptions::ES_TAX_EXCEPTION_68_70;
    const ES_TAX_EXCEPTION_PASSIVE_SUBJECT = TaxExceptions::ES_TAX_EXCEPTION_84;
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
    private static $values = [];
    /** @var array */
    private static $removedValues = [];

    public static function add(string $key, string $value): void
    {
        $fixedKey = substr($key, 0, 20);
        self::$values[$fixedKey] = $value;
        unset(self::$removedValues[$fixedKey]);
    }

    /**
     * @deprecated Use TaxExceptions::add() instead.
     */
    public static function addException(string $key, string $value): void
    {
        TaxExceptions::add($key, $value);
    }

    public static function remove(string $key): void
    {
        $fixedKey = substr($key, 0, 20);
        unset(self::$values[$fixedKey]);
        self::$removedValues[$fixedKey] = true;
    }

    /**
     * @deprecated Use TaxExceptions::remove() instead.
     */
    public static function removeException(string $key): void
    {
        TaxExceptions::remove($key);
    }

    public static function all(): array
    {
        $defaultValues = [
            self::TAX_SYSTEM_EXEMPT => 'es-tax-regime-exempt',
            self::TAX_SYSTEM_GENERAL => 'es-tax-regime-general',
            self::TAX_SYSTEM_SURCHARGE => 'es-tax-regime-surcharge',
            self::TAX_SYSTEM_SIMPLIFIED => 'es-tax-regime-simplified',
            self::TAX_SYSTEM_AGRARIAN => 'es-tax-regime-agrarian',
            self::TAX_SYSTEM_CASH_CRITERIA => 'es-tax-regime-cash-criteria',
            self::TAX_SYSTEM_GROUP_ENTITIES => 'es-tax-regime-group-entities',
            self::TAX_SYSTEM_TRAVEL => 'es-tax-regime-travel',
            self::TAX_SYSTEM_USED_GOODS => 'es-tax-regime-used-goods',
            self::TAX_SYSTEM_GOLD => 'es-tax-regime-gold',
            self::TAX_SYSTEM_SPECIAL_SMALL_BUSINESS => 'es-tax-regime-special-small-business',
            self::TAX_SYSTEM_ONE_STOP_SHOP_OSS => 'es-tax-regime-one-stop-shop-oss',
            self::TAX_SYSTEM_ONE_STOP_SHOP_IOSS => 'es-tax-regime-one-stop-shop-ioss',
        ];

        $all = array_merge($defaultValues, self::$values);
        foreach (array_keys(self::$removedValues) as $key) {
            unset($all[$key]);
        }

        return $all;
    }

    /**
     * @deprecated Use TaxExceptions::all() instead.
     */
    public static function allExceptions(): array
    {
        return TaxExceptions::all();
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
     *
     * @deprecated Use TaxExceptions::isValidCombination() instead.
     */
    public static function isValidCombination(?string $operation, ?string $exception, string $context): bool
    {
        return TaxExceptions::isValidCombination($operation, $exception, $context);
    }
}
