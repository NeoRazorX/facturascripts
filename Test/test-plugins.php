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
if (!file_exists($config)) {
    die($config . " not found!\n");
}

require_once $config;

// connect to database
$db = new FacturaScripts\Core\Base\DataBase();
$db->connect();

// clean cache
$cache = new FacturaScripts\Core\Base\Cache();
$cache->clear();

// load Init file for every plugin
$pluginManager = new FacturaScripts\Core\Base\PluginManager();
foreach ($pluginManager->enabledPlugins() as $plugin) {
    $initClass = '\\FacturaScripts\\Plugins\\' . $plugin . '\\Init';
    if (class_exists($initClass)) {
        $initObject = new $initClass();
        $initObject->init();
    }
}
