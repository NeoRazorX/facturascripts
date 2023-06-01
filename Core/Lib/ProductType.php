<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * This class centralizes all types of products.
 *
 * @author    Daniel Fernández Giménez    <hola@danielfg.es>
 */
class ProductType
{
    const PRODUCT_TYPE_NORMAL = 'Normal';
    const PRODUCT_TYPE_SECOND_HAND = 'Segunda mano';
    const PRODUCT_TYPE_SERVICE = 'Servicio';
    const PRODUCT_TYPE_TRAVEL = 'Viaje';

    /** @var array */
    private static $types = [];

    public static function add(string $key, string $value): void
    {
        $fixedKey = substr($key, 0, 20);
        self::$types[$fixedKey] = $value;
    }

    public static function all(): array
    {
        $defaultTypes = [
            self::PRODUCT_TYPE_NORMAL => 'normal',
            self::PRODUCT_TYPE_SECOND_HAND => 'second-hand',
            self::PRODUCT_TYPE_SERVICE => 'service',
            self::PRODUCT_TYPE_TRAVEL => 'travel'
        ];

        return array_merge($defaultTypes, self::$types);
    }
}
