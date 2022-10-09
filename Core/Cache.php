<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
final class Cache
{
    const EXPIRATION = 3600;
    const FILE_PATH = '/MyFiles/Tmp/FileCache';

    /**
     * Removes all data
     */
    public static function clear(): void
    {
        $folder = FS_FOLDER . self::FILE_PATH;
        if (false === file_exists($folder)) {
            return;
        }

        foreach (scandir($folder) as $fileName) {
            if (str_ends_with($fileName, '.cache')) {
                unlink($folder . '/' . $fileName);
            }
        }
    }

    /**
     * Removes this key and value.
     *
     * @param string $key
     */
    public static function delete(string $key): void
    {
        $fileName = self::filename($key);
        if (file_exists($fileName)) {
            unlink($fileName);
        }
    }

    /**
     * Removes all keys starting with $prefix
     *
     * @param string $prefix
     */
    public static function deleteMulti(string $prefix): void
    {
        $folder = FS_FOLDER . self::FILE_PATH;
        foreach (scandir($folder) as $fileName) {
            $len = strlen($prefix);
            if (substr($fileName, 0, $len) === $prefix) {
                unlink($folder . '/' . $fileName);
            }
        }
    }

    /**
     * Removes all expired data.
     */
    public static function expire(): void
    {
        $folder = FS_FOLDER . self::FILE_PATH;
        foreach (scandir($folder) as $fileName) {
            if (filemtime($folder . '/' . $fileName) < time() - self::EXPIRATION) {
                unlink($folder . '/' . $fileName);
            }
        }
    }

    /**
     * Returns value stored for the $key
     *
     * @param string $key
     *
     * @return mixed
     */
    public static function get(string $key)
    {
        $fileName = self::filename($key);
        if (file_exists($fileName) && filemtime($fileName) >= time() - self::EXPIRATION) {
            $data = file_get_contents($fileName);
            return unserialize($data);
        }

        return null;
    }

    /**
     * Stores the $key and $value with the default expiration time.
     *
     * @param string $key
     * @param mixed $value
     */
    public static function set(string $key, $value): void
    {
        $folder = FS_FOLDER . self::FILE_PATH;
        if (false === file_exists($folder)) {
            mkdir($folder, 0777, true);
        }

        $data = serialize($value);
        $fileName = self::filename($key);
        file_put_contents($fileName, $data);
    }

    /**
     * Returns the filename for this key.
     *
     * @param string $key
     *
     * @return string
     */
    private static function filename(string $key): string
    {
        return FS_FOLDER . self::FILE_PATH . '/' . $key . '.cache';
    }
}
