<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Class to manage the actions with folders and files.
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 * @author Francesc Pineda Segarra      <francesc.pineda@x-netdigital.com>
 * @author Raul Jimenez                 <raul.jimenez@nazcanetworks.com>
 */
class FileManager
{

    /**
     * Default permissions to create new folders
     */
    const DEFAULT_FOLDER_PERMS = 0755;

    /**
     * Folders to exclude in scanFolder.
     */
    const EXCLUDE_FOLDERS = ['.', '..', '.DS_Store', '.well-known'];

    /**
     * Create the folder.
     *
     * @param string $folder    Path to folder to create
     * @param bool   $recursive If needs to be created recursively
     * @param int    $mode      Perms mode in octal format
     *
     * @return bool
     */
    public static function createFolder(string $folder, $recursive = false, $mode = self::DEFAULT_FOLDER_PERMS): bool
    {
        if (!file_exists($folder) && !@mkdir($folder, $mode, $recursive) && !is_dir($folder)) {
            return false;
        }

        return true;
    }

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

        $files = is_dir($folder) ? static::scanFolder($folder, false, ['.', '..']) : [];
        foreach ($files as $file) {
            $path = $folder . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? static::delTree($path) : unlink($path);
        }

        return is_dir($folder) ? rmdir($folder) : unlink($folder);
    }

    /**
     * Extracts strings from between the BEGIN and END markers in the .htaccess file.
     *
     * @source https://github.com/WordPress/WordPress/blob/master/wp-admin/includes/misc.php#L67
     *
     * @param string $fileName
     * @param string $marker
     *
     * @return array An array of strings from a file (.htaccess ) from between BEGIN and END markers.
     */
    public static function extractFromMarkers(string $fileName, string $marker): array
    {
        $result = [];
        if (!file_exists($fileName)) {
            return $result;
        }

        $markerData = explode("\n", file_get_contents($fileName));
        $state = false;
        foreach ($markerData as $markerLine) {
            if (false !== strpos($markerLine, '# END ' . $marker)) {
                $state = false;
            }
            if ($state) {
                $result[] = $markerLine;
            }
            if (false !== strpos($markerLine, '# BEGIN ' . $marker)) {
                $state = true;
            }
        }

        return $result;
    }

    /**
     * Inserts an array of strings into a file (.htaccess ), placing it between
     * BEGIN and END markers.
     *
     * Replaces existing marked info. Retains surrounding
     * data. Creates file if none exists.
     *
     * @source https://github.com/WordPress/WordPress/blob/master/wp-admin/includes/misc.php#L106
     *
     * @param array  $insertion The new content to insert.
     * @param string $fileName  Filename to alter.
     * @param string $marker    The marker to alter.
     *
     * @return bool True on write success, false on failure.
     */
    public static function insertWithMarkers(array $insertion, string $fileName, string $marker): bool
    {
        if (!file_exists($fileName)) {
            if (!is_writable(\dirname($fileName))) {
                return false;
            }
            if (!touch($fileName)) {
                return false;
            }
        } elseif (!is_writable($fileName)) {
            return false;
        }

        $startMarker = '# BEGIN ' . $marker;
        $endMarker = '# END ' . $marker;
        $fp = fopen($fileName, 'rb+');
        if (!$fp) {
            return false;
        }

        // Attempt to get a lock. If the filesystem supports locking, this will block until the lock is acquired.
        flock($fp, LOCK_EX);
        $lines = [];
        while (!feof($fp)) {
            $lines[] = rtrim(fgets($fp), "\r\n");
        }

        // Split out the existing file into the preceding lines, and those that appear after the marker
        $preLines = $postLines = $existingLines = [];
        $foundMarker = $foundEndMarker = false;
        foreach ($lines as $line) {
            if (!$foundMarker && false !== strpos($line, $startMarker)) {
                $foundMarker = true;
                continue;
            }
            if (!$foundEndMarker && false !== strpos($line, $endMarker)) {
                $foundEndMarker = true;
                continue;
            }
            if (!$foundMarker) {
                $preLines[] = $line;
            } elseif ($foundMarker && $foundEndMarker) {
                $postLines[] = $line;
            } else {
                $existingLines[] = $line;
            }
        }

        // Check to see if there was a change
        if ($existingLines === $insertion) {
            flock($fp, LOCK_UN);
            fclose($fp);
            return true;
        }

        // If it's true, is the old content version without the tags marker, we can remove it
        if (empty(\array_diff($insertion, $preLines))) {
            $preLines = [];
        }

        // Generate the new file data
        $newFileData = implode(
            \PHP_EOL, array_merge(
                $preLines, [$startMarker], $insertion, [$endMarker], $postLines
            )
        );

        // Write to the start of the file, and truncate it to that length
        fseek($fp, 0);
        $bytes = fwrite($fp, $newFileData);
        if ($bytes) {
            ftruncate($fp, ftell($fp));
        }
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        return (bool) $bytes;
    }

    /**
     * Returns an array with all not writable folders.
     *
     * @return array
     */
    public static function notWritableFolders(): array
    {
        $notwritable = [];
        foreach (static::scanFolder(\FS_FOLDER, true) as $folder) {
            if (is_dir($folder) && !is_writable($folder)) {
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
     * 
     * @return bool
     */
    public static function recurseCopy(string $src, string $dst): bool
    {
        $folder = opendir($src);

        if (!static::createFolder($dst)) {
            return false;
        }

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

        return true;
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
    public static function scanFolder(string $folder, bool $recursive = false, array $exclude = self::EXCLUDE_FOLDERS): array
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
