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

/// scan json files
chdir(__DIR__);
$files = [];
foreach (scandir(__DIR__, SCANDIR_SORT_ASCENDING) as $filename) {
    if (is_file($filename) && substr($filename, -5) === '.json') {
        $files[] = $filename;
    }
}

/// download json from facturascripts.com
foreach ($files as $filename) {
    $url = "https://facturascripts.com/EditLanguage?action=json&code=";
    $json = file_get_contents($url . substr($filename, 0, -5));
    if (empty($json) || strlen($json) < 10) {
        unlink($filename);
        echo "Remove " . $filename . "\n";
        continue;
    }

    echo "Download " . $filename . "\n";
    file_put_contents($filename, $json);
}
