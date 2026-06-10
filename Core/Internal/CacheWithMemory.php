<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use Closure;
use FacturaScripts\Core\Cache;

/**
 * Clase auxiliar para el patrón fluent con memoria
 */
class CacheWithMemory
{
    private static array $memoryStorage = [];

    public static function clear(): void
    {
        self::$memoryStorage = [];

        Cache::clear();
    }

    public function delete(string $key): void
    {
        // Eliminamos de memoria
        unset(self::$memoryStorage[$key]);

        // También eliminamos del caché de archivos
        Cache::delete($key);
    }

    public static function deleteMulti(string $prefix): void
    {
        // Eliminamos de memoria todos los que empiecen con el prefijo
        foreach (self::$memoryStorage as $key => $item) {
            $len = strlen($prefix);
            if (substr($key, 0, $len) === $prefix) {
                unset(self::$memoryStorage[$key]);
            }
        }

        // También eliminamos del caché de archivos
        Cache::deleteMulti($prefix);
    }

    public static function expire(): void
    {
        $now = time();
        foreach (self::$memoryStorage as $key => $item) {
            if ($item['expires'] < $now) {
                unset(self::$memoryStorage[$key]);
            }
        }

        Cache::expire();
    }

    public function get(string $key)
    {
        // Primero intentamos obtener de memoria
        if (isset(self::$memoryStorage[$key])) {
            $item = self::$memoryStorage[$key];
            // Verificamos si no ha expirado
            if ($item['expires'] >= time()) {
                return $item['value'];
            }
            // Si ha expirado, lo eliminamos de memoria
            unset(self::$memoryStorage[$key]);
        }

        // Si no está en memoria o ha expirado, intentamos del caché de archivos
        return Cache::get($key);
    }

    public function remember(string $key, Closure $callback)
    {
        if (!is_null($value = $this->get($key))) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value);
        return $value;
    }

    public function set(string $key, $value): void
    {
        // Guardamos en memoria
        self::$memoryStorage[$key] = [
            'value' => $value,
            'expires' => time() + Cache::EXPIRATION
        ];

        // También guardamos en caché de archivos
        Cache::set($key, $value);
    }
}
