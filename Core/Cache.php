<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Internal\CacheWithMemory;
use Throwable;

/**
 * Caché simple basada en ficheros, almacenada en `/MyFiles/Tmp/FileCache`.
 *
 * Cada clave se materializa como un fichero `.cache` cuyo contenido es el valor serializado con
 * `serialize()`. La expiración se calcula a partir de la fecha de modificación del fichero
 * (`filemtime`) frente a la constante `EXPIRATION`, por lo que no se almacena un TTL por entrada:
 * todas las entradas comparten la misma vida útil. Si se necesita un control de TTL más fino,
 * usar otro mecanismo de caché.
 *
 * Las claves se sanean (las barras `/` y `\` se sustituyen por `_`) para garantizar que el nombre
 * resultante sea seguro como nombre de fichero, pero no se hashean: claves muy largas pueden
 * desbordar el límite del sistema de ficheros.
 *
 * Para casos donde se quiera evitar leer del disco varias veces dentro de la misma petición,
 * `withMemory()` devuelve una variante que combina esta caché con un nivel adicional en memoria.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
final class Cache
{
    /** Tiempo de vida en segundos compartido por todas las entradas (1 hora). */
    const EXPIRATION = 3600;

    /** Ruta de la caché relativa a `FS_FOLDER`. */
    const FILE_PATH = '/MyFiles/Tmp/FileCache';

    /**
     * Borra todas las entradas de la caché de ficheros.
     *
     * Sólo se eliminan los ficheros con extensión `.cache`, dejando intacto cualquier otro
     * contenido que pudiera haber en la carpeta. Si la carpeta no existe, no hace nada.
     */
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

    /** Elimina la entrada asociada a `$key`. Si el fichero no existe, no hace nada. */
    public static function delete(string $key): void
    {
        // buscamos el archivo y lo borramos
        $fileName = self::filename($key);
        if (file_exists($fileName)) {
            unlink($fileName);
        }
    }

    /**
     * Elimina todas las entradas cuya clave (transformada en nombre de fichero) empiece por `$prefix`.
     *
     * El prefijo se compara contra el nombre del fichero ya saneado, así que las barras del
     * prefijo deberán reemplazarse por `_` por parte del llamador si se quiere usar tal cual.
     */
    public static function deleteMulti(string $prefix): void
    {
        // buscamos los archivos que contengan el prefijo y los borramos
        $folder = FS_FOLDER . self::FILE_PATH;
        if (false === file_exists($folder)) {
            return;
        }

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

    /**
     * Borra los ficheros cuyo `filemtime` indica que ya han expirado.
     *
     * Pensado para limpiezas periódicas (por cron o tareas internas). A diferencia de `clear()`,
     * aquí no se filtra por extensión: se elimina cualquier entrada del directorio con fecha de
     * modificación anterior a `now - EXPIRATION`, saltando únicamente `.` y `..`.
     */
    public static function expire(): void
    {
        // borramos todos los archivos que hayan expirado
        $folder = FS_FOLDER . self::FILE_PATH;
        if (false === file_exists($folder)) {
            return;
        }

        foreach (scandir($folder) as $fileName) {
            // saltamos los directorios . y ..
            if ($fileName === '.' || $fileName === '..') {
                continue;
            }

            if (filemtime($folder . '/' . $fileName) < time() - self::EXPIRATION) {
                unlink($folder . '/' . $fileName);
            }
        }
    }

    /**
     * Devuelve el valor cacheado bajo `$key`, o `$default` si no existe, ha expirado o no se puede deserializar.
     *
     * Si el contenido del fichero no se puede deserializar (por ejemplo porque la clase original
     * ha cambiado o el dato está corrupto), se devuelve el valor por defecto en lugar de propagar
     * la excepción, evitando romper al llamador por una caché inválida.
     *
     * @param string $key
     * @param mixed  $default valor a devolver cuando no hay entrada válida
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        // buscamos el archivo y comprobamos su fecha de modificación
        $fileName = self::filename($key);
        if (file_exists($fileName) && filemtime($fileName) >= time() - self::EXPIRATION) {
            // todavía no ha expirado, devolvemos el contenido
            $data = file_get_contents($fileName);
            try {
                return unserialize($data);
            } catch (Throwable $e) {
                return $default;
            }
        }

        return $default;
    }

    /**
     * Indica si existe una entrada vigente (no expirada) para `$key`.
     *
     * No deserializa el contenido, por lo que no detecta ficheros corruptos: una llamada posterior
     * a `get()` puede devolver el valor por defecto aunque `has()` haya devuelto true.
     */
    public static function has(string $key): bool
    {
        // buscamos el archivo y comprobamos su fecha de modificación
        $fileName = self::filename($key);
        return file_exists($fileName) && filemtime($fileName) >= time() - self::EXPIRATION;
    }

    /**
     * Devuelve el valor cacheado, o lo calcula con `$callback`, lo guarda y lo devuelve.
     *
     * Aviso: la comprobación se hace con `is_null($value)`, así que un valor null cacheado se
     * tratará como ausencia y forzará a recalcular. No usar esta función para cachear nulls
     * intencionados; en ese caso conviene devolver desde el callback un sentinela distinto.
     *
     * @param string  $key
     * @param Closure $callback función a invocar cuando no hay valor en caché
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

    /**
     * Guarda `$value` en la caché bajo `$key`.
     *
     * Si el directorio no existe se crea con permisos `0777` recursivamente, para que tanto el
     * proceso de PHP como otros usuarios del sistema (CLI, otra instancia, etc.) puedan operar
     * sobre la caché. La primera vez que se crea un fichero se le aplica `chmod 0666` para evitar
     * problemas en entornos donde la umask por defecto deja los ficheros sin permisos de escritura
     * para el grupo. El valor se serializa con `serialize()`, así que puede ser cualquier tipo
     * serializable de PHP.
     */
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

    /**
     * Devuelve un wrapper que añade un nivel de caché en memoria sobre la caché de ficheros.
     *
     * Útil cuando una misma clave se consulta muchas veces dentro de la misma petición y no se
     * quiere ir al disco cada vez. La instancia es nueva en cada llamada, así que el nivel en
     * memoria no se comparte entre llamadas distintas a `withMemory()`.
     */
    public static function withMemory(): CacheWithMemory
    {
        return new CacheWithMemory();
    }

    /**
     * Construye la ruta del fichero asociado a `$key`.
     *
     * Las barras `/` y `\` se sustituyen por `_` para garantizar un nombre válido en el sistema
     * de ficheros. No se aplica hashing, así que el llamador es responsable de evitar claves
     * extremadamente largas o con caracteres no admitidos por su sistema.
     */
    private static function filename(string $key): string
    {
        // reemplazamos / y \ por _
        $name = str_replace(['/', '\\'], '_', $key);
        return FS_FOLDER . self::FILE_PATH . '/' . $name . '.cache';
    }
}
