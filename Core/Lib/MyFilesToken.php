<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\AppKey;

/**
 * Description of MyFilesToken
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class MyFilesToken
{
    const PURPOSE = 'myfiles';

    /** @var string */
    private static $date;

    public static function get(string $path, bool $permanent, string $expiration = ''): string
    {
        self::checkPath($path);

        // sin FS_APP_KEY seguimos generando el token antiguo, para que siga siendo válido cuando se añada la clave
        if (AppKey::isDerived()) {
            return self::legacyToken($path, $permanent, $expiration);
        }

        if ($expiration && $permanent === false) {
            // si se especifica una fecha de expiración, la añadimos también al final para poder validarla
            return AppKey::sign(self::PURPOSE, 'until|' . $expiration . '|' . $path) . '|' . $expiration;
        }

        return $permanent ?
            AppKey::sign(self::PURPOSE, 'permanent|' . $path) :
            AppKey::sign(self::PURPOSE, 'daily|' . self::getCurrentDate() . '|' . $path);
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

        $valid = [
            static::get($path, true),
            static::get($path, false),
            // tokens generados antes de existir FS_APP_KEY, para no romper los enlaces ya compartidos
            self::legacyToken($path, true),
            self::legacyToken($path, false),
        ];

        // ¿El token contiene "|"?
        if (strpos($token, '|') !== false) {
            $expiration = explode('|', $token)[1];

            // ¿La fecha de expiración es válida?
            if (strtotime($expiration) < strtotime(self::getCurrentDate())) {
                return false;
            }

            $valid[] = self::get($path, false, $expiration);
            $valid[] = self::legacyToken($path, false, $expiration);
        }

        foreach ($valid as $validToken) {
            if (hash_equals($validToken, $token)) {
                return true;
            }
        }

        return false;
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

    private static function legacyToken(string $path, bool $permanent, string $expiration = ''): string
    {
        $init = FS_DB_NAME . FS_DB_PASS;
        if ($expiration && $permanent === false) {
            return sha1($init . $path . $expiration) . '|' . $expiration;
        }

        return $permanent ? sha1($init . $path) : sha1($init . $path . self::getCurrentDate());
    }
}
