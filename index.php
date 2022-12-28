<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\App\AppCron;
use FacturaScripts\Core\App\AppInstaller;
use FacturaScripts\Core\App\AppRouter;
use FacturaScripts\Core\Base\Debug\ProductionErrorHandler;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

const FS_FOLDER = __DIR__;

/**
 * Preliminary checks
 */
if (false === file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'config.php')) {
    if (version_compare(PHP_VERSION, '7.2') < 0) {
        die('You need PHP 7.2 or later<br/>You have PHP ' . phpversion());
    } elseif (false === file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'vendor')) {
        die('<h1>COMPOSER ERROR</h1><p>You need to run: composer install</p><p>You should also run: npm install</p>');
    }

    /**
     * If there is no configuration file, it means the installation hasn't been done,
     * then we load the installer.
     */
    require_once __DIR__ . '/vendor/autoload.php';

    $router = new AppRouter();
    if (false === $router->getFile()) {
        $app = new AppInstaller();
    }
    exit();
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

// Disable 30 seconds PHP limit
@set_time_limit(0);
ignore_user_abort(true);

// timezone
date_default_timezone_set(FS_TIMEZONE);

// Register error handler
if (FS_DEBUG) {
    $whoops = new Run;
    $whoops->prependHandler(new PlainTextHandler());
    $whoops->prependHandler(new JsonResponseHandler());
    $whoops->prependHandler(new PrettyPageHandler());
    $whoops->register();
} else {
    $errorHandler = new ProductionErrorHandler();
}

/**
 * Initialise the application
 */
$router = new AppRouter();
if (isset($argv[1]) && $argv[1] === '-cron') {
    chdir(__DIR__);
    $app = new AppCron();
    $app->connect();
    $app->run();
    $app->render();
    $app->close();
} elseif (false === $router->getFile()) {
    $app = $router->getApp();

    // Connect to the database, cache, etc.
    $app->connect();

    // Executes App logic
    $app->run();
    $app->render();

    // Disconnect from everything
    $app->close();
}
