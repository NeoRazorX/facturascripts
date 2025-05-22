<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use Closure;
use Throwable;

/**
 * Permite leer y escribir de forma sencilla información que se almacena en la carpeta /MyFiles/Tmp/FileCache.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
final class Cache
{
    const EXPIRATION = 3600;
    const FILE_PATH = '/MyFiles/Tmp/FileCache';

    public static function clear(): void
    {
        // si no existe la carpeta, no hace nada
        $folder = FS_FOLDER . self::FILE_PATH;
        if (false === file_exists($folder)) {
            return;
        }

        // recorremos la carpeta y borramos los archivos
        foreach (scandir($folder) as $fileName) {
            if (str_ends_with($fileName, '.cache')) {
                unlink($folder . '/' . $fileName);
            }
        }
    }

    public static function delete(string $key): void
    {
        // buscamos el archivo y lo borramos
        $fileName = self::filename($key);
        if (file_exists($fileName)) {
            unlink($fileName);
        }
    }

    public static function deleteMulti(string $prefix): void
    {
        // buscamos los archivos que contengan el prefijo y los borramos
        $folder = FS_FOLDER . self::FILE_PATH;
        foreach (scandir($folder) as $fileName) {
            // si no es un archivo, continuamos
            if (!str_ends_with($fileName, '.cache')) {
                continue;
            }

            // si el archivo empieza por el prefijo, lo borramos
            $len = strlen($prefix);
            if (substr($fileName, 0, $len) === $prefix) {
                unlink($folder . '/' . $fileName);
            }
        }
    }

    public static function expire(): void
    {
        // borramos todos los archivos que hayan expirado
        $folder = FS_FOLDER . self::FILE_PATH;
        foreach (scandir($folder) as $fileName) {
            if (filemtime($folder . '/' . $fileName) < time() - self::EXPIRATION) {
                unlink($folder . '/' . $fileName);
            }
        }
    }

    public static function get(string $key)
    {
        // buscamos el archivo y comprobamos su fecha de modificación
        $fileName = self::filename($key);
        if (file_exists($fileName) && filemtime($fileName) >= time() - self::EXPIRATION) {
            // todavía no ha expirado, devolvemos el contenido
            $data = file_get_contents($fileName);
            try {
                return unserialize($data);
            } catch (Throwable $e) {
                return null;
            }
        }

        return null;
    }

    public static function set(string $key, $value): void
    {
        // si no existe la carpeta, la creamos
        $folder = FS_FOLDER . self::FILE_PATH;
        if (false === file_exists($folder)) {
            mkdir($folder, 0777, true);
        }

        // guardamos el contenido
        $data = serialize($value);
        $fileName = self::filename($key);
        $exists = file_exists($fileName);

        file_put_contents($fileName, $data);

        // si no existía el archivo, le damos permisos de escritura
        if (!$exists) {
            chmod($fileName, 0666);
        }
    }

    private static function filename(string $key): string
    {
        // reemplazamos / y \ por _
        $name = str_replace(['/', '\\'], '_', $key);
        return FS_FOLDER . self::FILE_PATH . '/' . $name . '.cache';
    }

    /**
     * Obtenemos el valor almacenado si existe o, por el contrario, almacenamos lo que devuelva la función callback.
     *
     * @param string $key
     * @param Closure $callback
     * @return mixed
     */
    public static function remember(string $key, Closure $callback)
    {
        if (!is_null($value = self::get($key))) {
            return $value;
        }

        $value = $callback();
        self::set($key, $value);
        return $value;
    }
}
