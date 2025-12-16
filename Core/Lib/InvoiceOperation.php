<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class InvoiceOperation
{
    const EXEMPT = 'exenta';
    const ES_BENEFIT_THIRD_PARTIES = 'benefit-3-parties';
    const ES_EXPORT = 'exportacion';
    const ES_IMPORT = 'importacion';
    const ES_INTRA_COMMUNITY = 'intracomunitaria';
    const ES_SUCCESSIVE_TRACT = 'successive-tract';
    const ES_WORK_CERTIFICATION = 'work-certification';

    /** @var array */
    private static $values = [];

    public static function add(string $key, string $value): void
    {
        $fixedKey = substr($key, 0, 20);
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
            self::EXEMPT => 'exempt',
            self::ES_INTRA_COMMUNITY => 'es-intra-community',
            self::ES_EXPORT => 'es-operation-export',
            self::ES_IMPORT => 'es-operation-import',
            self::ES_WORK_CERTIFICATION => 'es-work-certification',
            self::ES_BENEFIT_THIRD_PARTIES => 'es-benefit-3-parties',
            self::ES_SUCCESSIVE_TRACT => 'es-successive-tract',
        ];
    }
}
