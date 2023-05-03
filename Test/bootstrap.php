<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Plugins;

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
echo "\n" . 'DB Name: ' . FS_DB_NAME . "\n\n";

// clean cache
Cache::clear();

// deploy
Plugins::deploy();
