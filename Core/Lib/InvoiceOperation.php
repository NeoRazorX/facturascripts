<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * This class centralizes all available invoice operations.
 *
 * @author Carlos Garcia Gómez <carlos@facturascripts.com>
 */
class InvoiceOperation
{
    const EXPORT = 'exportacion';
    const IMPORT = 'importacion';
    const INTRA_COMMUNITY = 'intracomunitaria';
    const SUCCESSIVE_TRACT = 'successive-tract';
    const WORK_CERTIFICATION = 'work-certification';

    /** @var array */
    private static $all = [];
    /** @var array */
    private static $removed = [];

    public static function add(string $key, string $value): void
    {
        $fixedKey = substr($key, 0, 20);
        self::$all[$fixedKey] = $value;
        unset(self::$removed[$fixedKey]);
    }

    public static function remove(string $key): void
    {
        $fixedKey = substr($key, 0, 20);
        unset(self::$all[$fixedKey]);
        self::$removed[$fixedKey] = true;
    }

    public static function all(): array
    {
        $defaults = [
            self::INTRA_COMMUNITY => 'intra-community',
            self::EXPORT => 'operation-export',
            self::IMPORT => 'operation-import',
            self::WORK_CERTIFICATION => 'work-certification',
            self::SUCCESSIVE_TRACT => 'successive-tract',
        ];

        $all = array_merge($defaults, self::$all);
        foreach (array_keys(self::$removed) as $key) {
            unset($all[$key]);
        }

        return $all;
    }
}
