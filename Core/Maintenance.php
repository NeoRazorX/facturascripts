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
 * Modo de mantenimiento: mientras está activo, index.php rechaza las peticiones nuevas con un 503,
 * y el cron y la cola de trabajos dejan de ejecutar tareas. Se usa al actualizar el núcleo o un plugin
 * y al reconstruir la carpeta Dinamic, para que nadie ejecute código a medio sustituir.
 *
 * El estado se guarda en el archivo MyFiles/maintenance, que index.php lee sin cargar ninguna clase.
 * Un archivo vacío (por ejemplo, creado con touch desde un script de despliegue) activa el modo
 * de mantenimiento sin caducidad, hasta que se elimine.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
final class Maintenance
{
    /** Segundos tras los que caduca por defecto, por si el proceso que lo activó muere sin desactivarlo. */
    const DEFAULT_TTL = 600;

    const FILE_NAME = 'maintenance';

    /** Parámetro de la url con el que la petición que continúa la actualización atraviesa el bloqueo. */
    const URL_PARAM = 'maintenance';

    /**
     * Desactiva el modo de mantenimiento.
     *
     * @return bool True si no queda activo.
     */
    public static function disable(): bool
    {
        $filePath = self::filePath();
        if (false === file_exists($filePath)) {
            return true;
        }

        return @unlink($filePath) || false === file_exists($filePath);
    }

    /**
     * Activa el modo de mantenimiento, si no lo estaba ya.
     *
     * @param int $ttl Segundos tras los que caduca. Con 0 no caduca.
     *
     * @return bool True si lo ha activado esta llamada: en ese caso, es quien debe desactivarlo.
     *              False si ya estaba activo o no se ha podido activar.
     */
    public static function enable(int $ttl = self::DEFAULT_TTL): bool
    {
        if (self::isActive()) {
            return false;
        }

        $data = [
            'expires' => $ttl > 0 ? time() + $ttl : 0,
            'token' => Tools::randomString(32),
        ];

        // escribimos en un archivo temporal y lo renombramos, para que index.php nunca lo lea a medias
        Tools::folderCheckOrCreate(Tools::folder('MyFiles'));
        $filePath = self::filePath();
        $tmpPath = $filePath . '.' . Tools::randomString(8) . '.tmp';
        if (false === @file_put_contents($tmpPath, json_encode($data))) {
            return false;
        }

        if (false === @rename($tmpPath, $filePath)) {
            @unlink($tmpPath);
            return false;
        }

        return true;
    }

    /**
     * Devuelve la ruta del archivo que indica que el modo de mantenimiento está activo.
     */
    public static function filePath(): string
    {
        return Tools::folder('MyFiles', self::FILE_NAME);
    }

    /**
     * Indica si el modo de mantenimiento está activo, es decir, si existe el archivo y no ha caducado.
     */
    public static function isActive(): bool
    {
        $data = self::read();
        if (null === $data) {
            return false;
        }

        return $data['expires'] === 0 || $data['expires'] > time();
    }

    /**
     * Devuelve el token con el que una petición puede atravesar el bloqueo, añadiéndolo a la url
     * en el parámetro URL_PARAM. Cadena vacía si no está activo o se activó a mano, sin token.
     */
    public static function token(): string
    {
        return self::isActive() ? self::read()['token'] : '';
    }

    /**
     * Lee el archivo de mantenimiento. Null si no existe.
     * Un archivo vacío o ilegible equivale a un mantenimiento sin caducidad ni token.
     */
    private static function read(): ?array
    {
        $filePath = self::filePath();

        // PHP recuerda que el archivo existe: en un proceso largo, como el cron, hay que consultarlo de nuevo
        clearstatcache(true, $filePath);
        if (false === file_exists($filePath)) {
            return null;
        }

        $data = json_decode((string)@file_get_contents($filePath), true);
        return [
            'expires' => is_array($data) ? (int)($data['expires'] ?? 0) : 0,
            'token' => is_array($data) ? (string)($data['token'] ?? '') : '',
        ];
    }
}
