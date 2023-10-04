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
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class ProductType
{
    const NORMAL = 'Normal';
    const SECOND_HAND = 'Segunda mano';
    const SERVICE = 'Servicio';
    const TRAVEL = 'Viaje';

    /** @var array */
    private static $all = [];

    public static function add(string $key, string $value): void
    {
        $fixedKey = substr($key, 0, 20);
        self::$all[$fixedKey] = $value;
    }

    public static function all(): array
    {
        $defaultTypes = [
            self::NORMAL => 'normal',
            self::SECOND_HAND => 'second-hand',
            self::SERVICE => 'service',
            self::TRAVEL => 'travel'
        ];

        return array_merge($defaultTypes, self::$all);
    }
}
