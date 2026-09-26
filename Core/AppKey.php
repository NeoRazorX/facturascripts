<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Clave secreta de la instalación (FS_APP_KEY) con la que se firman los tokens.
 * Se genera aleatoriamente al instalar y se guarda en el config.php.
 */
final class AppKey
{
    const KEY_BYTES = 32;

    /**
     * Genera una clave aleatoria nueva, en hexadecimal.
     */
    public static function generate(): string
    {
        return bin2hex(random_bytes(self::KEY_BYTES));
    }

    /**
     * Devuelve la clave de la instalación. Si el config.php no tiene FS_APP_KEY (instalaciones
     * anteriores), deriva una a partir de los datos de conexión a la base de datos.
     */
    public static function get(): string
    {
        if (false === self::isDerived()) {
            return Tools::config('app_key');
        }

        return hash('sha256', implode('|', [
            'facturascripts',
            FS_FOLDER,
            Tools::config('db_host', ''),
            Tools::config('db_name', ''),
            Tools::config('db_user', ''),
            Tools::config('db_pass', ''),
        ]));
    }

    /**
     * Indica si la clave es derivada porque falta FS_APP_KEY en el config.php.
     */
    public static function isDerived(): bool
    {
        $key = Tools::config('app_key');
        return false === is_string($key) || strlen($key) < self::KEY_BYTES;
    }

    /**
     * Firma los datos con HMAC-SHA256. El propósito separa las firmas de cada tipo de token,
     * para que un token de un tipo no sirva como token de otro.
     */
    public static function sign(string $purpose, string $data): string
    {
        return hash_hmac('sha256', $purpose . '|' . $data, self::get());
    }

    /**
     * Comprueba en tiempo constante que la firma corresponde a los datos.
     */
    public static function verify(string $purpose, string $data, string $signature): bool
    {
        return hash_equals(self::sign($purpose, $data), $signature);
    }
}
