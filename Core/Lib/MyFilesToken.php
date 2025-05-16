<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Description of MyFilesToken
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class MyFilesToken
{
    /** @var string */
    private static $date;

    public static function get(string $path, bool $permanent, string $expiration = ''): string
    {
        self::checkPath($path);

        $init = FS_DB_NAME . FS_DB_PASS;
        if ($expiration && $permanent === false) {
            // si se especifica una fecha de expiración, la añadimos también al final para poder validarla
            return sha1($init . $path . $expiration) . '|' . $expiration;
        }

        $date = self::getCurrentDate();
        return $permanent ? sha1($init . $path) : sha1($init . $path . $date);
    }

    public static function getUrl(string $path, bool $permanent, string $expiration = ''): string
    {
        self::checkPath($path);

        return str_replace('\\', '/', $path) . '?myft=' . MyFilesToken::get($path, $permanent, $expiration);
    }

    public static function getCurrentDate(): string
    {
        if (self::$date === null) {
            self::$date = date('d-m-Y');
        }

        return self::$date;
    }

    public static function setCurrentDate(string $date): void
    {
        self::$date = $date;
    }

    public static function validate(string $path, string $token): bool
    {
        self::checkPath($path);

        // ¿El token contiene "|"?
        if (strpos($token, '|') !== false) {
            $expiration = explode('|', $token)[1];

            // ¿La fecha de expiración es válida?
            if (strtotime($expiration) < strtotime(self::getCurrentDate())) {
                return false;
            }

            // ¿El token es válido?
            if ($token === self::get($path, false, $expiration)) {
                return true;
            }
        }

        return $token === static::get($path, true) || $token === static::get($path, false);
    }

    private static function checkPath(string &$path): void
    {
        // comprobamos si el path empieza por / y lo eliminamos
        if (strpos($path, '/') === 0) {
            $path = substr($path, 1);
        }

        // comprobamos si el path empieza por \ y lo eliminamos
        if (strpos($path, '\\') === 0) {
            $path = substr($path, 1);
        }

        // si el path no empieza por MyFiles, lo añadimos
        if (strpos($path, 'MyFiles') !== 0) {
            $path = 'MyFiles' . DIRECTORY_SEPARATOR . $path;
        }
    }
}
