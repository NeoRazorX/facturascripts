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
     * Añade FS_APP_KEY al config.php de una instalación que no la tiene y devuelve la clave añadida,
     * o null si no se ha podido: no existe el archivo, no se puede escribir o ya define la constante.
     */
    public static function saveToConfig(string $configFile = ''): ?string
    {
        $configFile = empty($configFile) ? FS_FOLDER . DIRECTORY_SEPARATOR . 'config.php' : $configFile;
        if (false === is_writable($configFile) || false === is_writable(dirname($configFile))) {
            return null;
        } elseif (is_link($configFile)) {
            // al renombrar el temporal sustituiríamos el enlace por un archivo normal
            return null;
        }

        // solo modificamos un config.php normal que no mencione ya la clave
        $content = file_get_contents($configFile);
        if (false === is_string($content) || strpos($content, '<?php') !== 0) {
            return null;
        } elseif (strpos($content, 'FS_APP_KEY') !== false) {
            return null;
        }

        // usamos la clave de la variable de entorno, como el instalador, si es válida
        $key = (string)Tools::env('FS_APP_KEY', '');
        if (strlen($key) < self::KEY_BYTES) {
            $key = self::generate();
        }
        $line = "define('FS_APP_KEY', '" . str_replace(['\\', "'"], ['\\\\', "\\'"], $key) . "');";

        // la añadimos justo tras la etiqueta de apertura, por si el archivo tiene etiqueta de cierre
        $newContent = '<?php' . "\n" . $line . substr($content, strlen('<?php'));

        // escribimos en un temporal y lo renombramos, para no dejar nunca un config.php a medias
        $tmpFile = $configFile . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (false === file_put_contents($tmpFile, $newContent)) {
            @unlink($tmpFile);
            return null;
        }

        @chmod($tmpFile, fileperms($configFile) & 0777);
        // falla, por ejemplo, si el config.php es un archivo montado en un contenedor
        if (false === @rename($tmpFile, $configFile)) {
            @unlink($tmpFile);
            return null;
        }

        // sin esto, opcache podría seguir sirviendo el config.php anterior
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($configFile, true);
        }

        return $key;
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
