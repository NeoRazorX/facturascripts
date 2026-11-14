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

namespace FacturaScripts\Core\Internal;

use FacturaScripts\Core\Tools;

/**
 * Limitador de peticiones con ventana deslizante, almacenado en `/MyFiles/Tmp/RateLimit`.
 *
 * Cada clave guarda en un fichero las marcas de tiempo de las peticiones aceptadas dentro de
 * la ventana. El fichero se bloquea con `flock()` mientras se lee y se actualiza, de modo que
 * varias peticiones simultáneas no puedan superar el límite.
 */
final class RateLimiter
{
    /** Ruta de los contadores relativa a `FS_FOLDER`. */
    const FILE_PATH = 'MyFiles/Tmp/RateLimit';

    /**
     * Registra una petición para `$key` y devuelve false si ya se han aceptado `$limit`
     * peticiones durante los últimos `$window` segundos. Las peticiones rechazadas no cuentan.
     *
     * Un límite menor que 1 desactiva la comprobación. Si no se puede escribir el contador,
     * la petición se acepta para no bloquear el servicio por un problema de permisos.
     */
    public static function attempt(string $key, int $limit, float $window = 1.0): bool
    {
        if ($limit < 1) {
            return true;
        }

        $folder = Tools::folder(self::FILE_PATH);
        if (false === Tools::folderCheckOrCreate($folder)) {
            return true;
        }

        $handle = @fopen(self::filename($key), 'c+');
        if (false === $handle) {
            return true;
        }

        if (false === flock($handle, LOCK_EX)) {
            fclose($handle);
            return true;
        }

        // descartamos las peticiones que ya han salido de la ventana
        $now = microtime(true);
        $hits = [];
        $saved = json_decode((string)stream_get_contents($handle), true);
        foreach (is_array($saved) ? $saved : [] as $time) {
            if (is_numeric($time) && $time <= $now && $now - $time < $window) {
                $hits[] = (float)$time;
            }
        }

        $allowed = count($hits) < $limit;
        if ($allowed) {
            $hits[] = $now;
        }

        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, json_encode($hits));
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        return $allowed;
    }

    /** Elimina el contador asociado a `$key`. */
    public static function clear(string $key): void
    {
        $fileName = self::filename($key);
        if (file_exists($fileName)) {
            unlink($fileName);
        }
    }

    /**
     * Devuelve los segundos que faltan para que `$key` vuelva a tener hueco tras alcanzar
     * el límite, es decir, hasta que la petición más antigua salga de la ventana.
     * Devuelve 0 si no hay peticiones registradas dentro de la ventana.
     */
    public static function retryAfter(string $key, float $window = 1.0): int
    {
        $fileName = self::filename($key);
        if (false === file_exists($fileName)) {
            return 0;
        }

        $now = microtime(true);
        $oldest = null;
        $saved = json_decode((string)file_get_contents($fileName), true);
        foreach (is_array($saved) ? $saved : [] as $time) {
            if (is_numeric($time) && $time <= $now && $now - $time < $window) {
                $oldest = null === $oldest ? (float)$time : min($oldest, (float)$time);
            }
        }

        return null === $oldest ? 0 : max(1, (int)ceil($oldest + $window - $now));
    }

    private static function filename(string $key): string
    {
        return Tools::folder(self::FILE_PATH, sha1($key) . '.json');
    }
}
