<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2025-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Esta clase centraliza todas las operaciones relacionadas con los impuestos.
 *
 * @author Daniel Fernández Giménez <contacto@danielfg.es>
 */
class OperacionIVA
{
    const ES_OPERATION_01 = 'ES_01'; // valor añadido
    const ES_OPERATION_02 = 'ES_02'; // Ceuta y Melilla
    const ES_OPERATION_03 = 'ES_03'; // IGIC
    const ES_OPERATION_04 = 'ES_04'; // IPSI
    const ES_OPERATION_99 = 'ES_99'; // otro

    /** @var array */
    private static $values = [];

    /**
     * Añade una operación de IVA personalizada al listado.
     *
     * @param string $key Código identificador de la operación.
     * @param string $value Clave de traducción de la operación.
     */
    public static function add(string $key, string $value): void
    {
        $fixedKey = substr($key, 0, 20);
        self::$values[$fixedKey] = $value;
    }

    /**
     * Devuelve todas las operaciones de IVA disponibles.
     */
    public static function all(): array
    {
        $defaultValues = [
            self::ES_OPERATION_01 => 'es-operation-tax-added-value',
            self::ES_OPERATION_02 => 'es-operation-tax-ceuta-melilla',
            self::ES_OPERATION_03 => 'es-operation-tax-igic',
            self::ES_OPERATION_04 => 'es-operation-tax-ipsi',
            self::ES_OPERATION_99 => 'es-operation-tax-other',
        ];

        return array_merge($defaultValues, self::$values);
    }

    /**
     * Devuelve el código de la operación de IVA predeterminada.
     */
    public static function default(): string
    {
        return self::ES_OPERATION_01;
    }
}
