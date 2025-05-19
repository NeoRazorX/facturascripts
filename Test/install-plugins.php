#!/usr/bin/env php
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

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Kernel;
use FacturaScripts\Core\Plugins;

define("FS_FOLDER", getcwd());

require_once __DIR__ . '/../vendor/autoload.php';

$config = FS_FOLDER . '/config.php';
if (!file_exists($config)) {
    die($config . " not found!\n");
}

require_once $config;

// connect to database
$db = new DataBase();
$db->connect();

// clean cache
Cache::clear();

// iniciamos el kernel
Kernel::init();

// deploy
Plugins::deploy();

// disable all plugins
foreach (Plugins::enabled() as $plugin) {
    Plugins::disable($plugin);
}

// get the list of plugins to install
$listPath = __DIR__ . '/Plugins/install-plugins.txt';
if (file_exists($listPath)) {
    $content = file_get_contents($listPath);
    $list = explode(',', $content);
    foreach ($list as $plugin) {
        if (Plugins::enable($plugin)) {
            echo 'Plugin ' . $plugin . ' enabled.' . PHP_EOL . PHP_EOL;
            continue;
        }

        echo 'Plugin ' . $plugin . ' not found.' . PHP_EOL . PHP_EOL;
        exit(2);
    }
    unlink($listPath);
}

// disconnect from database
$db->close();
