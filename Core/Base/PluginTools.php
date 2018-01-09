<?php

/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Base;

/**
 * PluginTools give us some basic and common methods for manage the Plugins.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class PluginTools {

    /**
     * Check the zip file integrity
     *
     * @param string $filePath
     *
     * @return int|true
     */
    public function checkZipfile($filePath) {
        $zipFile = new \ZipArchive();
        $zip_status = $zipFile->open($filePath, ZipArchive::CHECKCONS);

        if ($zip_status !== TRUE) {
            return $zip_status;
        }

        $zipFile->close();
        return true;
    }

    /**
     * Unzip the file path to destiny folder.
     *
     * @param string $filePath
     *
     * @return bool|int|string
     */
    public function unzipFile($filePath) {
        $destinyFolder = FS_FOLDER . DIRECTORY_SEPARATOR . 'Plugins' . DIRECTORY_SEPARATOR;
        $zipFile = new \ZipArchive();
        $result = $zipFile->open($filePath, ZipArchive::CHECKCONS);

        if ($result === TRUE) {
            $folderPlugin = str_replace('/', '', $zipFile->getNameIndex(0));
            $pluginName = $this->getVerifiedPluginName($filePath);
            if ($pluginName) {
                // Removing previous version
                if (is_dir($destinyFolder . $pluginName)) {
                    $this->delTree($destinyFolder . $pluginName);
                }
                // Extract new version
                $zipFile->extractTo($destinyFolder);
                $zipFile->close();
                // Rename folder Plugin
                if ($folderPlugin !== $pluginName) {
                    rename($destinyFolder . $folderPlugin, $destinyFolder . $pluginName);
                }
                return $result;
            }
            return false;
        }

        return $result;
    }

    /**
     * Return the FacturaScripts´s version minimum requirement for the Plugin
     *
     * @param string $pluginUnzipped
     *
     * @return string|false
     */
    public function getRequiredPluginVersion($pluginUnzipped) {
        $zipFile = new \ZipArchive();
        $result = $zipFile->open($pluginUnzipped);
        if ($result) {
            $fsIni = $zipFile->getFromName($zipFile->getNameIndex(0) . 'facturascripts.ini');
            $zipFile->close();
            if (!$fsIni) {
                return -1;
            }
            $fsIniContent = parse_ini_string($fsIni);
            if (!array_key_exists('min_version', $fsIniContent)) {
                return -2;
            }
            return $fsIniContent['min_version'];
        }
        return false;
    }

    /**
     * Return the verified name, if its different than extracted folder, also rename it.
     *
     * @param string $pluginUnzipped
     *
     * @return string|false
     */
    public function getVerifiedPluginName($pluginUnzipped) {
        $zipFile = new \ZipArchive();
        $result = $zipFile->open($pluginUnzipped);
        if ($result) {
            $fsIni = $zipFile->getFromName($zipFile->getNameIndex(0) . 'facturascripts.ini');
            $zipFile->close();
            if (!$fsIni) {
                return -1;
            }
            $fsIniContent = parse_ini_string($fsIni);
            if (!array_key_exists('name', $fsIniContent)) {
                return -2;
            }
            return $fsIniContent['name'];
        }
        return false;
    }

    /**
     * Recursive delete directory.
     *
     * @param string $dir
     *
     * @return bool
     */
    private function delTree($dir) {
        $files = [];
        if (is_dir($dir)) {
            $files = array_diff(scandir($dir, SCANDIR_SORT_ASCENDING), ['.', '..']);
        }
        foreach ($files as $file) {
            is_dir($dir . '/' . $file) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        return is_dir($dir) ? rmdir($dir) : unlink($dir);
    }

}
