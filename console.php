<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

if (\PHP_SAPI !== 'cli') {
    die('Access allowed only in command line.');
}

define('FS_FOLDER', __DIR__);

/** @noinspection PhpIncludeInspection */
require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'config.php')) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
} elseif (file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'Test' . DIRECTORY_SEPARATOR . 'config-scrutinizer.php')) {
    // Allows to use it if you are in development,
    // but need to use at least this config as development environment.
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'Test' . DIRECTORY_SEPARATOR . 'config-scrutinizer.php';
}

new FacturaScripts\Core\Console\ConsoleManager($_SERVER['argc'], $_SERVER['argv']);
