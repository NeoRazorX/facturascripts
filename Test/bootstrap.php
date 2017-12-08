<?php
/*
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Carlos Garcia Gomez  carlos@facturascripts.com
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

/**
 * @author Carlos García Gómez
 */
require_once __DIR__ . '/../vendor/autoload.php';
if (__DIR__ === '/home/scrutinizer/build/Test') {
    echo 'Executing on scrutinizer ...' . "\n\n";
    require_once __DIR__ . '/config-scrutinizer.php';
} else {
    // Local config, to use the same database connection
    $config = __DIR__ . '/../config.php';
    // To use another config, to use the same database connection that scrutinizer
    //$config = __DIR__ . '/config-scrutinizer.php';

    echo 'Executing on development ...' . "\n";
    echo 'Edit "Test/bootstrap.php" if you want to use another config.php file.' . "\n";
    echo 'Using ' . $config . "\n\n";

    require_once $config;
}

define('FS_FOLDER', dirname(__DIR__ . '/../..'));

echo "\n" . 'Database connection details:' . "\n";
echo "\n" . 'Host: ' . FS_DB_HOST;
echo "\n" . 'User: ' . FS_DB_USER;
echo "\n" . 'Pass: ' . FS_DB_PASS . "\n\n";
