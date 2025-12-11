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
 * This class centralizes all common method for TAX Regimes.
 *
 * @author Daniel Fernández Giménez    <hola@danielfg.es>
 */
class TaxRegime
{
    const ES_TAX_REGIME_AGRARIAN = 'Agrario';
    const ES_TAX_REGIME_CASH_CRITERIA = 'Caja';
    const ES_TAX_REGIME_GENERAL = 'General';
    const ES_TAX_REGIME_GOLD = 'Oro';
    const ES_TAX_REGIME_GROUP_ENTITIES = 'Grupo entidades';
    const ES_TAX_REGIME_DISTANCE_SALES = 'Ventas a distancia';
    const ES_TAX_REGIME_SIMPLIFIED = 'Simplificado';
    const ES_TAX_REGIME_SURCHARGE = 'Recargo';
    const ES_TAX_REGIME_TRAVEL = 'Agencias de viaje';
    const ES_TAX_REGIME_USED_GOODS = 'Bienes usados';
    
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

    public static function get(string $key): ?string
    {
        $values = self::all();
        return $values[$key] ?? null;
    }

    private static function defaults(): array
    {
        return [
            self::ES_TAX_REGIME_AGRARIAN => 'es-tax-regime-agrarian',
            self::ES_TAX_REGIME_CASH_CRITERIA => 'es-tax-regime-cash-criteria',
            self::ES_TAX_REGIME_GENERAL => 'es-tax-regime-general',
            self::ES_TAX_REGIME_GOLD => 'es-tax-regime-gold',
            self::ES_TAX_REGIME_GROUP_ENTITIES => 'es-tax-regime-group-entities',
            self::ES_TAX_REGIME_DISTANCE_SALES => 'es-tax-regime-distance-sales',
            self::ES_TAX_REGIME_SIMPLIFIED => 'es-tax-regime-simplified',
            self::ES_TAX_REGIME_SURCHARGE => 'es-tax-regime-surcharge',
            self::ES_TAX_REGIME_TRAVEL => 'es-tax-regime-travel',
            self::ES_TAX_REGIME_USED_GOODS => 'es-tax-regime-used-goods',
        ];
    }
}