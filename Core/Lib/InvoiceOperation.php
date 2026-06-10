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
    const BENEFIT_THIRD_PARTIES = 'benefit-third-parties';

    const EXPORT = 'exportacion';

    const IMPORT = 'importacion';

    const INTRA_COMMUNITY = 'intracomunitaria';

    const INTRA_COMMUNITY_SERVICES = 'intracom-servicios';

    const REVERSE_CHARGE = 'inv-sujeto-pasivo';

    const SUCCESSIVE_TRACT = 'successive-tract';

    const TYPE_PURCHASE = 'purchase';

    const TYPE_SALE = 'sale';

    const WORK_CERTIFICATION = 'work-certification';

    /** @var array */
    private static $all = [];

    /** @var array */
    private static $removed = [];

    /** @var array */
    private static $types = [];

    public static function add(string $key, string $value, ?string $type = null): void
    {
        $fixedKey = substr($key, 0, 20);
        self::$all[$fixedKey] = $value;
        unset(self::$removed[$fixedKey]);

        if ($type) {
            self::$types[$fixedKey] = $type;
        }
    }

    public static function all(): array
    {
        return self::filter();
    }

    public static function allForPurchases(): array
    {
        return self::filter(self::TYPE_PURCHASE);
    }

    public static function allForSales(): array
    {
        return self::filter(self::TYPE_SALE);
    }

    public static function get(?string $key): ?string
    {
        $values = self::all();
        return $values[$key] ?? null;
    }

    public static function remove(string $key): void
    {
        $fixedKey = substr($key, 0, 20);
        unset(self::$all[$fixedKey], self::$types[$fixedKey]);
        self::$removed[$fixedKey] = true;
    }

    private static function defaultTypes(): array
    {
        return [
            self::BENEFIT_THIRD_PARTIES => self::TYPE_SALE,
            self::EXPORT => self::TYPE_SALE,
            self::IMPORT => self::TYPE_PURCHASE,
            self::SUCCESSIVE_TRACT => self::TYPE_SALE,
            self::WORK_CERTIFICATION => self::TYPE_SALE,
        ];
    }

    private static function filter(?string $type = null): array
    {
        $defaults = [
            self::BENEFIT_THIRD_PARTIES => 'benefit-third-parties',
            self::INTRA_COMMUNITY => 'intra-community',
            self::INTRA_COMMUNITY_SERVICES => 'intra-community-services',
            self::REVERSE_CHARGE => 'reverse-charge',
            self::EXPORT => 'operation-export',
            self::IMPORT => 'operation-import',
            self::WORK_CERTIFICATION => 'work-certification',
            self::SUCCESSIVE_TRACT => 'successive-tract',
        ];

        $all = array_merge($defaults, self::$all);
        foreach (array_keys(self::$removed) as $key) {
            unset($all[$key]);
        }

        if (null === $type) {
            return $all;
        }

        $types = array_merge(self::defaultTypes(), self::$types);
        foreach (array_keys($all) as $key) {
            // si tiene tipo asignado y no coincide, lo quitamos
            if (isset($types[$key]) && $types[$key] !== $type) {
                unset($all[$key]);
            }
        }

        return $all;
    }
}
