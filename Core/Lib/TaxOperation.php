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
 * This class centralizes all common method for TAX Operations.
 *
 * @author Daniel Fernández Giménez    <hola@danielfg.es>
 */
class TaxOperation
{
    const ES_TAX_OPERATION_01 = 'ES_01'; // valor añadido
    const ES_TAX_OPERATION_02 = 'ES_02'; // Ceuta y Melilla
    const ES_TAX_OPERATION_03 = 'ES_03'; // IGIC
    const ES_TAX_OPERATION_99 = 'ES_99'; // otro

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
            self::ES_TAX_OPERATION_01 => 'es-tax-operation-added-value',
            self::ES_TAX_OPERATION_02 => 'es-tax-operation-ceuta-melilla',
            self::ES_TAX_OPERATION_03 => 'es-tax-operation-igic',
            self::ES_TAX_OPERATION_99 => 'es-tax-operation-other',
        ];
    }
}