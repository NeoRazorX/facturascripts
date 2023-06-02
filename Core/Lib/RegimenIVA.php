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
    const ES_TAX_EXCEPTION_E1 = 'ES_20';
    const ES_TAX_EXCEPTION_E2 = 'ES_21';
    const ES_TAX_EXCEPTION_E3 = 'ES_22';
    const ES_TAX_EXCEPTION_E4 = 'ES_23_24';
    const ES_TAX_EXCEPTION_E5 = 'ES_25';
    const ES_TAX_EXCEPTION_E6 = 'ES_OTHER';

    const TAX_SYSTEM_AGRARIAN = 'Agrario';
    const TAX_SYSTEM_CASH_CRITERIA = 'Caja';
    const TAX_SYSTEM_EXEMPT = 'Exento';
    const TAX_SYSTEM_GENERAL = 'General';
    const TAX_SYSTEM_GOLD = 'Oro';
    const TAX_SYSTEM_GROUP_ENTITIES = 'Grupo entidades';
    const TAX_SYSTEM_SIMPLIFIED = 'Simplificado';
    const TAX_SYSTEM_SURCHARGE = 'Recargo';
    const TAX_SYSTEM_TELECOM = 'Telecom';
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
            self::TAX_SYSTEM_TELECOM => 'es-tax-regime-telecom',
            self::TAX_SYSTEM_USED_GOODS => 'es-tax-regime-used-goods',
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
        ];

        return array_merge($defaultExceptions, self::$exceptions);
    }

    public static function defaultValue(): string
    {
        return self::TAX_SYSTEM_GENERAL;
    }
}
