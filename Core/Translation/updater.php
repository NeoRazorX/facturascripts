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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
if (php_sapi_name() !== "cli") {
    die("Please use command line: php updater.php");
}

chdir(__DIR__);

/**
 * Downloads translations file from facturascripts.com
 * 
 * @param string $filename
 */
function downloadJson(string $filename): string
{
    $url = "https://beta.facturascripts.com/EditLanguage?action=json&code=";
    return file_get_contents($url . substr($filename, 0, -5));
}

/**
 * Scans .json files in current folder.
 * 
 * @return array
 */
function scanFolder(): array
{
    $scan = scandir(__DIR__, SCANDIR_SORT_ASCENDING);
    if (!is_array($scan)) {
        return [];
    }

    $files = [];
    foreach ($scan as $filename) {
        if (is_file($filename) && substr($filename, -5) === '.json') {
            $files[] = $filename;
        }
    }

    return $files;
}

/// main process
foreach (scanFolder() as $filename) {
    $json = downloadJson($filename);
    if (empty($json) || strlen($json) < 10) {
        echo "Skip " . $filename . "\n";
        continue;
    }

    echo "Download " . $filename . "\n";
    file_put_contents($filename, $json);
}