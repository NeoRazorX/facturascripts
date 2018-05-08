<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

/**
 * Class to manage the actions with folders and files
 * 
 * @package FacturaScripts\Core\Lib
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Cristo M. Estévez Hernández <cristom.estevez@gmail.com>
 */
class FileManager
{
    /**
     * Recursive delete directory.
     *
     * @param string $dir
     *
     * @return bool
     */
    public static function delTree(string $dir): bool
    {
        $files = is_dir($dir) ? $this->scanFolder($dir) : [];
        foreach ($files as $file) {
            is_dir($dir . '/' . $file) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        return is_dir($dir) ? rmdir($dir) : unlink($dir);
    }

    /**
     * Returns an array with all files and folders.
     *
     * @param string $folderPath
     *
     * @return array
     */
    public static function scanFolder(string $folderPath): array
    {
        $scan = scandir($folderPath, SCANDIR_SORT_ASCENDING);
        return is_array($scan) ? array_diff($scan, ['.', '..']) : [];
    }

    /**
     * Makes a recursive scan in folders inside a root folder and extracts the list of files
     * and pass its to an array as result.
     * 
     * @param string $folder
     * @param bool   $recursive
     *
     * @return array
     */
    public static function scanFolders(string $folder, bool $recursive = true): array
    {
        $scan = scandir($folder, SCANDIR_SORT_ASCENDING);
        if (!is_array($scan)) {
            return [];
        }
        $rootFolder = array_diff($scan, ['.', '..']);
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
            foreach ($this->scanFolders($newItem) as $item2) {
                $result[] = $item . DIRECTORY_SEPARATOR . $item2;
            }
        }
        return $result;
    }

    /**
     * Returns an array with all not writable folders.
     * 
     * @return array
     */
    public static function notWritablefolders(): array
    {
        $notwritable = [];
        foreach ($this->foldersFrom(FS_FOLDER) as $dir) {
            if (!is_writable($dir)) {
                $notwritable[] = $dir;
            }
        }
        return $notwritable;
    }

    /**
     * Returns an array with all subfolder of $baseDir folder.
     * 
     * @param string $baseDir
     * 
     * @return array
     */
    private static function foldersFrom(string $baseDir): array
    {
        $directories = [];
        foreach (scandir($baseDir) as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            $dir = $baseDir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($dir)) {
                $directories[] = $dir;
                $directories = array_merge($directories, $this->foldersFrom($dir));
            }
        }
        return $directories;
    }

    /**
     * Copy all files and folders from $src to $dst
     * 
     * @param string $src
     * @param string $dst
     */
    public static function recurseCopy(string $src, string $dst)
    {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ( $file = readdir($dir))) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            if (is_dir($src . '/' . $file)) {
                $this->recurseCopy($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
        closedir($dir);
    }
}
