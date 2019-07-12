<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
define('FS_FOLDER', __DIR__);

/// This function shows useful error data
function fatal_handler()
{
    $error = error_get_last();
    if (isset($error) && in_array($error["type"], [1, 64])) {
        die("<h1>Fatal error</h1>"
            . "<ul>"
            . "<li><b>Type:</b> " . $error["type"] . "</li>"
            . "<li><b>File:</b> " . $error["file"] . "</li>"
            . "<li><b>Line:</b> " . $error["line"] . "</li>"
            . "<li><b>Message:</b> " . $error["message"] . "</li>"
            . "</ul>");
    }
}
register_shutdown_function("fatal_handler");

/// Preliminary checks
if (!file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'config.php')) {
    if ((int) substr(phpversion(), 0, 1) < 7) {
        die('You need PHP 7<br/>You have PHP ' . phpversion());
    } elseif (!file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'vendor')) {
        die('<h1>COMPOSER ERROR</h1><p>You need to run: composer install</p>');
    }

    /**
     * If there is no configuration file, it means the installation hasn't been done,
     * then we load the installer.
     */
    require_once __DIR__ . '/vendor/autoload.php';

    $router = new \FacturaScripts\Core\App\AppRouter();
    if (!$router->getFile()) {
        $app = new \FacturaScripts\Core\App\AppInstaller();
    }
    die('');
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

/// disable 30 seconds PHP limit
@set_time_limit(0);

/// Initialise the application
$router = new \FacturaScripts\Core\App\AppRouter();

if (isset($argv[1]) && $argv[1] === '-cron') {
    chdir(__DIR__);
    $app = new \FacturaScripts\Core\App\AppCron();
    $app->connect();
    $app->run();
    $app->render();
    $app->close();
} elseif (!$router->getFile()) {
    $app = $router->getApp();

    /// Connect to the database, cache, etc.
    $app->connect();

    /// Executes App logic
    $app->run();
    $app->render();

    /// Disconnect from everything
    $app->close();
}