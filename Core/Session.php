<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core;

use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Dinamic\Model\User as DinUser;

/**
 * Permite gestionar la sesión del usuario.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
final class Session
{
    private static $data = [];

    public static function get(string $key)
    {
        return self::$data[$key] ?? null;
    }

    public static function getClientIp(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $field) {
            if (isset($_SERVER[$field])) {
                return (string)$_SERVER[$field];
            }
        }

        return '::1';
    }

    public static function permissions(): ControllerPermissions
    {
        return self::get('permissions') ?? new ControllerPermissions();
    }

    public static function set(string $key, $value): void
    {
        self::$data[$key] = $value;
    }

    public static function user(): User
    {
        if (isset(self::$data['user']) && self::$data['user'] instanceof User) {
            return self::$data['user'];
        }

        // si la clase existe en Dinamic, la usamos
        return class_exists('\\FacturaScripts\\Dinamic\\Model\\User') ?
            new DinUser() :
            new User();
    }
}
