<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Base;

/**
 * Description of MyFilesToken
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 * @collaborator Daniel Fernández Giménez <hola@danielfg.es>
 */
class MyFilesToken
{

    const DATE_FORMAT = 'Y-m-d H:i:s';
    public static $date = null;
    public static $datetime = null;
    private static $method = 'aes-256-cbc';
    private static $clave = 'IeUZU0kRPvchirLTZlQAtfdgf4I8a934cx2LM6HpUuxsV1jd1cnrxOogeJkWS';
    private static $iv = '17Fa7tO0y4oFBA==';

    public static function get(string $path, bool $permanent, ?string $datetime = null): string
    {
        if (substr($path, 0, 1) == '/') {
            $path = substr($path, 1);
        }

        if ($permanent) {
            return self::encrypt($path);
        }

        $path .= is_null($datetime) ?
            '|' . date(self::DATE_FORMAT, strtotime("+ 1 days")) :
            '|' . date(self::DATE_FORMAT, strtotime($datetime));

        return self::encrypt($path);
    }

    public static function validate(string $path, string $token): bool
    {
        if (self::validateNew($path, $token)) {
            return true;
        }

        return self::validateOld($path, $token);
    }

    protected static function decrypt($valor)
    {
        return openssl_decrypt($valor, self::$method, self::$clave, false, self::$iv);
    }

    protected static function encrypt($valor)
    {
        return openssl_encrypt($valor, self::$method, self::$clave, false, self::$iv);
    }

    protected static function validateNew(string $path, string $token): bool
    {
        $decrypt = self::decrypt($token);
        $parts = explode('|', $decrypt);

        if (count($parts) > 1) {
            if (is_null(self::$datetime)) {
                self::$datetime = date(self::DATE_FORMAT);
            }
            return $parts[0] == $path && self::$datetime <= date(self::DATE_FORMAT, strtotime(end($parts)));
        }

        return $parts[0] == $path;
    }

    protected static function validateOld(string $path, string $token): bool
    {
        $init = FS_DB_NAME . \FS_DB_PASS;

        if (is_null(self::$date)) {
            self::$date = date('d-m-Y');
        }

        if (sha1($init . $path . self::$date) === $token || sha1($init . $path) === $token) {
            return true;
        }

        return false;
    }
}
