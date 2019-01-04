<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Base;

/**
 * Class to check integrity system
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class IntegrityCheck
{

    /**
     * Path to list of integrity file.
     */
    const BASE = FS_FOLDER . DIRECTORY_SEPARATOR . 'MyFiles' . DIRECTORY_SEPARATOR;
    const INTEGRITY_FILE = self::BASE . 'integrity.json';
    const INTEGRITY_USER_FILE = self::BASE . 'integrity-validation.json';

    /**
     * Return an array with integrity files hash.
     *
     * @return array
     */
    public static function getIntegrityFiles(): array
    {
        $resources = [];
        $fileManager = new FileManager();
        // Ignore the minimum files/folders possible.
        $exclude = ['.', '..', 'Dinamic', 'Documentation', 'MyFiles', 'node_modules', 'Plugins', 'Test', 'vendor'];
        $exclude[] = '.htaccess';
        $exclude[] = 'config.php';
        $files = $fileManager::scanFolder(FS_FOLDER, true, $exclude);
        foreach ($files as $fileName) {
            $resources[$fileName] = self::getFileHash($fileName);
        }
        return $resources;
    }

    /**
     * Save the integrity files to disk,
     *
     * @param string $file
     *
     * @return bool
     */
    public static function saveIntegrity($file = self::INTEGRITY_FILE): bool
    {
        $integrity = self::getIntegrityFiles();
        $content = json_encode($integrity, \JSON_PRETTY_PRINT);
        return file_put_contents($file, $content) !== false;
    }

    /**
     * Return the integrity files from disk,
     *
     * @param string $file
     *
     * @return array
     */
    public static function loadIntegrity($file = self::INTEGRITY_FILE): array
    {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            if ($content !== false) {
                return json_decode($content, true);
            }
        }

        if (self::saveIntegrity($file)) {
            $content = file_get_contents($file);
            if ($content !== false) {
                return json_decode($content, true);
            }
        }

        return [];
    }

    /**
     * Return the file hash.
     *
     * @param string $file
     * @param string $algo
     *
     * @return string
     */
    public static function getFileHash($file = self::INTEGRITY_FILE, $algo = 'sha512'): string
    {
        return \file_exists($file) ? hash_file($algo, $file) : '';
    }

    /**
     * Compare the array and return a list of differences.
     *
     * @return array
     */
    public static function compareIntegrity(): array
    {
        // Fast check, if hashes are equals, content is the same
        if (self::getFileHash() === self::getFileHash(self::INTEGRITY_USER_FILE)) {
            return [];
        }

        $origArray = self::loadIntegrity();
        $userArray = self::loadIntegrity(self::INTEGRITY_USER_FILE);
        $types = ['MISSING_FILE', 'INVALID_HASH', 'EXTRA_FILE'];
        $result = [];
        foreach ($types as $type) {
            $result[$type] = [];
        }

        self::getAlteredUserFiles($result, $origArray, $userArray);
        self::getExtraUserFiles($result, $origArray, $userArray);
        self::cleanResults($result, $types);

        return $result;
    }

    /**
     * Add items if user integrity files have missing or invalid hashes.
     *
     * @param array $result
     * @param array $origArray
     * @param array $userArray
     */
    private static function getAlteredUserFiles(array &$result, array $origArray = [], array $userArray = [])
    {
        foreach ($origArray as $item => $hash) {
            if (!isset($userArray[$item])) {
                $result['MISSING_FILE'][] = $item;
            } elseif ($userArray[$item] !== $hash) {
                $result['INVALID_HASH'][] = $item;
            }
        }
    }

    /**
     * Add items if user integrity files have no needed files.
     *
     * @param array $result
     * @param array $origArray
     * @param array $userArray
     */
    private static function getExtraUserFiles(array &$result, array $origArray = [], array $userArray = [])
    {
        foreach ($userArray as $item => $hash) {
            if (!isset($origArray[$item])) {
                $result['EXTRA_FILE'][] = $item;
            }
        }
    }

    /**
     * Unset empty validations that are unneeded.
     *
     * @param array $result
     * @param array $types
     */
    private static function cleanResults(array &$result, array $types)
    {
        foreach ($types as $type) {
            if (empty($result[$type])) {
                unset($result[$type]);
            }
        }
    }
}
