<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * long with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Base;

/**
 * Manage some basic and common actions with files.
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class FileManager
{
    /**
     * To use it as sorten name.
     */
    const DS = DIRECTORY_SEPARATOR;
    /**
     * Common permissions.
     */
    const PERMS_FOLDER = 0755;
    const PERMS_FILE = 0644;
    /**
     * Common permissions for shared hostings.
     */
    const SHARED_PERMS_FOLDER = 0775;
    const SHARED_PERMS_FILE = 0664;

    /**
     * Return files and folders inside a path.
     * Note: The path are relative to FS_FOLDER.
     *
     * @param string $dir     Folder where looking for files and folders inside.
     * @param int    $order   Order to apply SCANDIR_SORT_ASCENDING/SCANDIR_SORT_DESCENDING,
     *                        by default SCANDIR_SORT_ASCENDING.
     * @param array  $exclude Array list of items to exclude, by default ['.', '..'].
     *
     * @return array
     */
    public function getFrom($dir, $order = SCANDIR_SORT_ASCENDING, array $exclude = ['.', '..']): array
    {
        $list = [];
        foreach (array_diff(scandir($dir, $order), $exclude) as $file) {
            $list[] = str_replace(\FS_FOLDER . '/', '', $dir . self::DS . $file);
        }
        return $list;
    }

    /**
     * Return a list of files (only files).
     * Note: The path are relative to FS_FOLDER.
     *
     * @param string $dir       Folder to start looking for files.
     * @param int    $order     Order to apply SCANDIR_SORT_ASCENDING/SCANDIR_SORT_DESCENDING,
     *                          by default SCANDIR_SORT_ASCENDING.
     * @param array  $exclude   Array list of items to exclude, by default ['.', '..'].
     * @param bool   $recursive Look for recursively or not, by default false.
     *
     * @return array
     */
    public function getFilesFrom($dir, $order = SCANDIR_SORT_ASCENDING, array $exclude = ['.', '..'], $recursive = false): array
    {
        $items = $this->getFrom($dir, $order, $exclude);

        $moreItems = [];
        foreach ($items as $pos => $item) {
            if (is_dir($dir . self::DS . $item)) {
                unset($items[$pos]);
                if ($recursive) {
                    foreach ($this->getFilesFrom($dir . self::DS . $item, $order, $exclude, $recursive) as $file) {
                        $moreItems[] = str_replace(\FS_FOLDER . '/', '', $file);
                    }
                }
            }
        }


        return array_merge($items, $moreItems);
    }

    /**
     * Return a list of folders and files.
     * Note: The path are relative to FS_FOLDER.
     *
     * @param array $directories Folder to start looking for files.
     * @param int   $order       Order to apply SCANDIR_SORT_ASCENDING/SCANDIR_SORT_DESCENDING,
     *                           by default SCANDIR_SORT_ASCENDING.
     * @param array $exclude     Array list of items to exclude, by default ['.', '..'].
     * @param bool  $recursive   Look for recursively or not, by default true.
     *
     * @return array
     */
    public function getAllFrom(array $directories, $order = SCANDIR_SORT_ASCENDING, array $exclude = ['.', '..'], $recursive = true): array
    {
        foreach ($directories as $directory) {
            $items = $this->getFrom($directory, $order, $exclude);

            $moreItems = [];
            foreach ($items as $item) {
                if ($recursive && is_dir($item)) {
                    $moreItems[] = $item;
                    foreach ($this->getAllFrom([$item], $order, $exclude, $recursive) as $file) {
                        $moreItems[] = str_replace(\FS_FOLDER . '/', '', $file);
                    }
                } else {
                    $moreItems[] = str_replace(\FS_FOLDER . '/', '', $item);
                }
            }
            $result = array_unique(\array_merge($items, $moreItems));
        }

        sort($result);
        return $result;
    }

    /**
     * Create a folder and return the result.
     * Returns True if success or False if fails.
     *
     * @param string $dir Folder to start looking for files.
     * @param int    $perms
     * @param bool   $recursive
     *
     * @return bool
     */
    public function createFolder($dir, $perms = self::PERMS_FOLDER, $recursive = true): bool
    {
        return !(!file_exists($dir) && !@mkdir($dir, $perms, $recursive) && !is_dir($dir));
    }

    /**
     * Delete a directory recursively.
     * Returns True if success or False if fails.
     *
     * @param string $dir Folder to remove.
     *
     * @return bool
     */
    public function deleteDirectory($dir): bool
    {
        if (is_dir($dir)) {
            $files = $this->getFrom($dir);

            foreach ($files as $file) {
                is_dir($file) ? $this->deleteDirectory($file) : unlink(\FS_FOLDER . self::DS . $file);
            }
            return rmdir($dir);
        }
        return false;
    }

    /**
     * Return a list of disabled php functions.
     *
     * @return array
     */
    private function getPhpDisabledFunctions(): array
    {
        return explode(',', ini_get('disable_functions'));
    }

    /**
     * Returns default permissions for file or folder.
     * If not correctOwner or realFileOwner received, readed from execution.
     *
     * @param bool   $isFile
     * @param string $correctOwner
     * @param string $realFileOwner
     *
     * @return string
     */
    private function getDefaultPerms($isFile, $correctOwner = '', $realFileOwner = ''): string
    {
        if ($correctOwner === '') {
            $correctOwner = \posix_getpwuid(\posix_geteuid())['name'];
        }
        if ($realFileOwner === '') {
            $realFileOwner = \posix_getpwuid(\fileowner(\FS_FOLDER))['name'];
        }

        /// Needed in common hostings accounts
        $string = $isFile ? self::PERMS_FILE : self::PERMS_FOLDER;
        if ($correctOwner !== $realFileOwner) {
            /// Needed with Apache userdir and some virtualhost configurations
            $string = $isFile ? self::SHARED_PERMS_FILE : self::SHARED_PERMS_FOLDER;
        }
        return $string;
    }

    /**
     * Calls to chgrp recursively.
     *
     * @param string $path
     * @param string $group
     *
     * @return bool
     */
    private function chGrpR(string $path, string $group): bool
    {
        if (\in_array('chgrp', $this->getPhpDisabledFunctions(), true)) {
            $miniLog = new MiniLog();
            $miniLog->critical(
                'chgrp is a disabled function.'
            );
            return false;
        }

        if (!is_dir($path)) {
            return @chgrp($path, $group);
        }

        foreach ($this->getAllFrom([$path]) as $file) {
            $fullPath = $path . self::DS . $file;
            if (is_link($fullPath)) {
                return false;
            }
            if (!is_dir($fullPath) && !@chgrp($fullPath, $group)) {
                return false;
            }
            if (!$this->chGrpR($fullPath, $group)) {
                return false;
            }
        }

        return @chgrp($path, $group);
    }

    /**
     * Calls to chmod recursively.
     *
     * @param string $path
     * @param string $fileMode
     *
     * @return bool
     */
    private function chModR(string $path, string $fileMode = ''): bool
    {
        $miniLog = new MiniLog();
        if (\in_array('chmod', $this->getPhpDisabledFunctions(), true)) {
            $miniLog->critical(
                'chmod is a disabled function.'
            );
            return false;
        }

        if ($fileMode === '') {
            $fileMode = $this->getDefaultPerms(is_file($path));
        }

        if ($this->isOctal($fileMode)) {
            if (!is_dir($path)) {
                return @chmod($path, (int) $fileMode);
            }

            foreach ($this->getAllFrom([$path]) as $file) {
                $fullPath = $path . self::DS . $file;
                if (is_link($fullPath)) {
                    return false;
                }
                if (!is_dir($fullPath) && !@chmod($fullPath, (int) $fileMode)) {
                    return false;
                }
                if (!$this->chModR($fullPath, $fileMode)) {
                    return false;
                }
            }

            return @chmod($path, (int) $fileMode);
        }

        $miniLog->critical(
            '"' . $fileMode . '" : Is not an octal file mode.'
        );
        return false;
    }

    /**
     * Returns if is octal file mode.
     *
     * @param string $fileMode
     *
     * @return bool
     */
    private function isOctal($fileMode): bool
    {
        $formatted = \str_pad(
            decoct((int) octdec($fileMode)),
            4,
            0,
            \STR_PAD_LEFT
        );
        return $formatted === $fileMode;
    }

    /**
     * Calls to chown recursively.
     *
     * @param string $path
     * @param string $owner
     *
     * @return bool
     */
    private function chOwnR(string $path, string $owner): bool
    {
        if (\in_array('chown', $this->getPhpDisabledFunctions(), true)) {
            $miniLog = new MiniLog();
            $miniLog->critical(
                'chmod is a disabled function.'
            );
            return false;
        }

        if (!is_dir($path)) {
            return @chown($path, $owner);
        }

        foreach ($this->getAllFrom([$path]) as $file) {
            $fullPath = $path . self::DS . $file;
            if (is_link($fullPath)) {
                return false;
            }
            if (!is_dir($fullPath) && !@chown($fullPath, $owner)) {
                return false;
            }
            if (!$this->chOwnR($fullPath, $owner)) {
                return false;
            }
        }

        return @chown($path, $owner);
    }
}
