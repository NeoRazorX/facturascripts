<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Base;

/**
 * Class to manage the actions with folders and files
 *
 * @package FacturaScripts\Core\Base
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Cristo M. Estévez Hernández <cristom.estevez@gmail.com>
 */
class FileManager
{

    /**
     * Recursive delete directory.
     *
     * @param string $folder
     *
     * @return bool
     */
    public static function delTree(string $folder): bool
    {
        if (!file_exists($folder)) {
            return true;
        }

        $files = is_dir($folder) ? static::scanFolder($folder) : [];
        foreach ($files as $file) {
            $path = $folder . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? static::delTree($path) : unlink($path);
        }

        return is_dir($folder) ? rmdir($folder) : unlink($folder);
    }

    /**
     * Returns an array with all not writable folders.
     *
     * @return array
     */
    public static function notWritableFolders(): array
    {
        $notwritable = [];
        foreach (static::scanFolder(FS_FOLDER) as $folder) {
            if (!is_writable($folder)) {
                $notwritable[] = $folder;
            }
        }

        return $notwritable;
    }

    /**
     * Copy all files and folders from $src to $dst
     *
     * @param string $src
     * @param string $dst
     */
    public static function recurseCopy(string $src, string $dst)
    {
        $folder = opendir($src);
        @mkdir($dst);
        while (false !== ($file = readdir($folder))) {
            if ($file === '.' || $file === '..') {
                continue;
            } elseif (is_dir($src . DIRECTORY_SEPARATOR . $file)) {
                static::recurseCopy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
            } else {
                copy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
            }
        }
        closedir($folder);
    }

    /**
     * Returns an array with files and folders inside given $folder
     *
     * @param string $folder
     * @param bool   $recursive
     * @param array  $exclude
     *
     * @return array
     */
    public static function scanFolder(string $folder, bool $recursive = false, array $exclude = ['.', '..']): array
    {
        $scan = scandir($folder, SCANDIR_SORT_ASCENDING);
        if (!is_array($scan)) {
            return [];
        }

        $rootFolder = array_diff($scan, $exclude);
        if (!$recursive) {
            return $rootFolder;
        }

        $result = [];
        foreach ($rootFolder as $item) {
            $newItem = $folder . DIRECTORY_SEPARATOR . $item;
            if (is_file($newItem)) {
                $result[] = $item;
                continue;
            }
            $result[] = $item;
            foreach (static::scanFolder($newItem, true) as $item2) {
                $result[] = $item . DIRECTORY_SEPARATOR . $item2;
            }
        }

        return $result;
    }
}
