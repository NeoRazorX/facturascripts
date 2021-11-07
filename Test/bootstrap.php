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

define("FS_FOLDER", getcwd());

require_once __DIR__ . '/../vendor/autoload.php';

$config = FS_FOLDER . '/config.php';
if (__DIR__ === '/home/scrutinizer/build/Test') {
    echo 'Executing on scrutinizer ...' . "\n\n";
    $config = FS_FOLDER . '/Test/config-scrutinizer.php';
} elseif (!file_exists($config)) {
    die($config . " not found!\n");
}

echo 'Edit "Test/bootstrap.php" if you want to use another config.php file.';
echo "\n" . 'Using ' . $config . "\n";

require_once $config;

echo "\n" . 'Connection details:';
echo "\n" . 'PHP: ' . phpversion();
echo "\n" . 'DB Host: ' . FS_DB_HOST;
echo "\n" . 'DB User: ' . FS_DB_USER;
echo "\n" . 'DB Pass: ' . FS_DB_PASS;
echo "\n" . 'Database: ' . FS_DB_NAME . "\n\n";

// clean cache
$cache = new FacturaScripts\Core\Base\Cache();
$cache->clear();

// deploy
$pluginManager = new FacturaScripts\Core\Base\PluginManager();
$pluginManager->deploy();

// database connect
$db = new \FacturaScripts\Core\Base\DataBase();
$db->connect();

// settings
$appSettings = new \FacturaScripts\Core\App\AppSettings();
$fileContent = file_get_contents(FS_FOLDER . '/Core/Data/Codpais/ESP/default.json');
$defaultValues = json_decode($fileContent, true) ?? [];
foreach ($defaultValues as $group => $values) {
    foreach ($values as $key => $value) {
        $appSettings->set($group, $key, $value);
    }
}
$appSettings->save();
