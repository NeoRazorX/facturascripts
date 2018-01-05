<?php
/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
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

/// preliminary checks
if (file_exists(__DIR__ . '/config.php')) {
    /**
     * If the configuration file exists it means that it is already installed,
     * redirects to the index.
     */
    header('Location: index.php');
    die('');
}

if (!file_exists(__DIR__ . '/vendor')) {
    die('<h1>COMPOSER ERROR</h1><p>You need to run: composer install<br/>npm install</p>'
        . '----------------------------------------'
        . '<p>Debes ejecutar: composer install<br/>npm install</p>');
}

require_once __DIR__ . '/vendor/autoload.php';
define('FS_FOLDER', __DIR__);

use FacturaScripts\Core\Base\Translator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns an error array with the known situations
 *
 * @param $errors
 * @param $i18n
 */
function searchErrors(&$errors, &$i18n)
{
    if ((float) '3,1' >= (float) '3.1') {
        $errors[] = $i18n->trans('wrong-decimal-separator');
    } elseif (!function_exists('mb_substr')) {
        $errors[] = $i18n->trans('mb-string-not-fount');
    } elseif (!extension_loaded('simplexml')) {
        $errors[] = $i18n->trans('simplexml-not-found');
    } elseif (!extension_loaded('openssl')) {
        $errors[] = $i18n->trans('openssl-not-found');
    } elseif (!extension_loaded('zip')) {
        $errors[] = $i18n->trans('ziparchive-not-found');
    } elseif (!is_writable(__DIR__)) {
        $errors[] = $i18n->trans('folder-not-writable');
    }
}

/**
 * Returns the corresponding font-awesome value to the @param parameter (true or false)
 *
 * @param boolean $isOk
 * @return string
 */
function checkRequirement($isOk)
{
    return $isOk ? 'fa-check text-success' : 'fa-ban text-danger';
}

/**
 * Returns the user language to show the proper installation language in the selector.
 * When the JSON file doesn't exist, returns en_EN
 *
 * @return string
 */
function getUserLanguage()
{
    $dataLanguage = explode(';', filter_input(INPUT_SERVER, 'HTTP_ACCEPT_LANGUAGE'));
    $userLanguage = str_replace('-', '_', explode(',', $dataLanguage[0])[0]);
    $translationExists = file_exists(__DIR__ . '/Core/Translation/' . $userLanguage . '.json');
    return ($translationExists) ? $userLanguage : 'en_EN';
}

/**
 * Timezones list with GMT offset
 *
 * @return array
 * @link http://stackoverflow.com/a/9328760
 */
function get_timezone_list()
{
    $zones_array = array();
    $timestamp = time();
    foreach (timezone_identifiers_list() as $key => $zone) {
        date_default_timezone_set($zone);
        $zones_array[$key]['zone'] = $zone;
        $zones_array[$key]['diff_from_GMT'] = 'UTC/GMT ' . date('P', $timestamp);
    }
    return $zones_array;
}

/**
 * Tries to perform the database connection,
 * if succeeded returns true, false if not.
 * When false, the error is stored in $errors
 *
 * @param $errors
 * @param $i18n
 *
 * @return bool
 */
function dbConnect(&$errors, &$i18n)
{
    $done = false;
    $dbData = [
        'host' => filter_input(INPUT_POST, 'db_host'),
        'port' => filter_input(INPUT_POST, 'db_port'),
        'user' => filter_input(INPUT_POST, 'db_user'),
        'pass' => filter_input(INPUT_POST, 'db_pass'),
        'name' => filter_input(INPUT_POST, 'db_name'),
    ];

    switch (filter_input(INPUT_POST, 'db_type')) {
        case 'mysql':
            if (class_exists('mysqli')) {
                $done = testMysql($errors, $dbData);
            } else {
                $errors[] = $i18n->trans('mysqli-not-found');
            }
            break;

        case 'postgresql':
            if (function_exists('pg_connect')) {
                $done = testPostgreSql($errors, $dbData);
            } else {
                $errors[] = $i18n->trans('postgresql-not-found');
            }
            break;
    }

    if (!$done) {
        $errors[] = $i18n->trans('cant-connect-db');
    }

    return $done;
}

/**
 * Tries to perform the MYSQL database connection,
 * if succeeded returns true, false if not.
 * When false, the error is stored in $errors
 *
 * @param $errors
 * @param $dbData
 *
 * @return bool
 */
function testMysql(&$errors, $dbData)
{
    $done = false;

    if (filter_input(INPUT_POST, 'mysql_socket') !== '') {
        ini_set('mysqli.default_socket', filter_input(INPUT_POST, 'mysql_socket'));
    }

    // Omit the DB name because it will be checked on a later stage
    $connection = new mysqli($dbData['host'], $dbData['user'], $dbData['pass'], '', (int) $dbData['port']);
    if ($connection->connect_error) {
        $errors[] = (string) $connection->connect_error;
    } else {
        // Check that the DB exists, if it doesn't, we create a new one
        $dbSelected = mysqli_select_db($connection, $dbData['name']);
        if ($dbSelected) {
            $done = true;
        } else {
            $sqlCrearBD = 'CREATE DATABASE `' . $dbData['name'] . '`;';
            if ($connection->query($sqlCrearBD)) {
                $done = true;
            } else {
                $errors[] = (string) $connection->connect_error;
            }
        }
    }

    return $done;
}

/**
 * Tries to perform the PostgreSQL database connection,
 * if succeeded returns true, false if not.
 * When false, the error is stored in $errors
 *
 * @param $errors
 * @param $dbData
 *
 * @return bool
 */
function testPostgreSql(&$errors, $dbData)
{
    $done = false;

    $connection = @pg_connect('host=' . $dbData['host'] . ' port=' . $dbData['port'] . ' user=' . $dbData['user'] . ' password=' . $dbData['pass']);
    if ($connection) {
        // Check that the DB exists, if it doesn't, we create a new one
        $connection2 = pg_connect('host=' . $dbData['host'] . ' port=' . $dbData['port'] . ' dbname=' . $dbData['name'] . ' user=' . $dbData['user'] . ' password=' . $dbData['pass']);
        if ($connection2) {
            $done = true;
        } else {
            $sqlCrearBD = 'CREATE DATABASE "' . $dbData['name'] . '";';
            if (pg_query($connection, $sqlCrearBD)) {
                $done = true;
            } else {
                $errors[] = (string) pg_last_error($connection);
            }
        }
    }

    return $done;
}

/**
 * If the needed directories are created or already exist, returns true. False when not.
 *
 * @return bool
 */
function createFolders()
{
    // If they already exist, we can return true
    if (is_dir('Plugins') && is_dir('Dinamic') && is_dir('Cache')) {
        return true;
    }
    if (mkdir('Plugins') && mkdir('Dinamic') && mkdir('Cache')) {
        chmod('Plugins', octdec(777));
        return true;
    }

    return false;
}

/**
 * Saves the configuration to config.php
 * returns true when succeeded, false when not.
 *
 * @return bool
 */
function saveInstall()
{
    $file = fopen(__DIR__ . '/config.php', 'wb');
    if ($file) {
        fwrite($file, "<?php\n");
        fwrite($file, "define('FS_COOKIES_EXPIRE', 604800);\n");
        fwrite($file, "define('FS_DEBUG', true);\n");
        fwrite($file, "define('FS_LANG', '" . filter_input(INPUT_POST, 'fs_lang') . "');\n");
        fwrite($file, "define('FS_TIMEZONE', '" . filter_input(INPUT_POST, 'fs_timezone') . "');\n");
        fwrite($file, "define('FS_DB_TYPE', '" . filter_input(INPUT_POST, 'db_type') . "');\n");
        fwrite($file, "define('FS_DB_HOST', '" . filter_input(INPUT_POST, 'db_host') . "');\n");
        fwrite($file, "define('FS_DB_PORT', '" . filter_input(INPUT_POST, 'db_port') . "');\n");
        fwrite($file, "define('FS_DB_NAME', '" . filter_input(INPUT_POST, 'db_name') . "');\n");
        fwrite($file, "define('FS_DB_USER', '" . filter_input(INPUT_POST, 'db_user') . "');\n");
        fwrite($file, "define('FS_DB_PASS', '" . filter_input(INPUT_POST, 'db_pass') . "');\n");
        fwrite($file, "define('FS_DB_FOREIGN_KEYS', true);\n");
        fwrite($file, "define('FS_DB_INTEGER', 'INTEGER');\n");
        fwrite($file, "define('FS_DB_TYPE_CHECK', true);\n");
        fwrite($file, "define('FS_CACHE_HOST', '" . filter_input(INPUT_POST, 'memcache_host') . "');\n");
        fwrite($file, "define('FS_CACHE_PORT', '" . filter_input(INPUT_POST, 'memcache_port') . "');\n");
        fwrite($file, "define('FS_CACHE_PREFIX', '" . filter_input(INPUT_POST, 'memcache_prefix') . "');\n");
        fwrite($file, "define('FS_MYDOCS', '');\n");
        if (filter_input(INPUT_POST, 'db_type') === 'MYSQL' && filter_input(INPUT_POST, 'mysql_socket') !== '') {
            fwrite($file, "\nini_set('mysqli.default_socket', '" . filter_input(INPUT_POST, 'mysql_socket') . "');\n");
        }
        fwrite($file, "\n");
        fclose($file);

        return true;
    }

    return false;
}

/**
 * Renders the views and returns the response
 *
 * @param $templateVars
 */
function renderHTML(&$templateVars)
{
    /// Load the template engine
    $twigLoader = new Twig_Loader_Filesystem(__DIR__ . '/Core/View');
    $twig = new Twig_Environment($twigLoader);

    /// Generate and return the HTML
    $response = new Response($twig->render('Installer/Install.html', $templateVars), Response::HTTP_OK);
    $response->send();
}

/**
 * Return a random string
 *
 * @param int $length
 *
 * @return bool|string
 */
function randomString($length = 20)
{
    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
}

/**
 * Main installer function
 *
 * @return int
 */
function installerMain()
{
    if (filter_input(INPUT_POST, 'fs_lang')) {
        define('FS_LANG', filter_input(INPUT_POST, 'fs_lang'));
    } elseif (filter_input(INPUT_GET, 'fs_lang')) {
        define('FS_LANG', filter_input(INPUT_GET, 'fs_lang'));
    } else {
        define('FS_LANG', getUserLanguage());
    }

    $i18n = new Translator();
    $errors = [];
    searchErrors($errors, $i18n);

    if (empty($errors) && filter_input(INPUT_POST, 'db_type')) {
        if (dbConnect($errors, $i18n) && createFolders() && saveInstall()) {
            header('Location: index.php');

            return 0;
        }
    }

    /// Pack the variables to handover to the template engine
    $templateVars = [
        'errors' => $errors,
        'requirements' => [
            'mb_substr' => checkRequirement(function_exists('mb_substr')),
            'SimpleXML' => checkRequirement(extension_loaded('simplexml')),
            'openSSL' => checkRequirement(extension_loaded('openssl')),
            'Zip' => checkRequirement(extension_loaded('zip'))
        ],
        'i18n' => $i18n,
        'languages' => $i18n->getAvailableLanguages(),
        'timezone' => get_timezone_list(),
        'license' => file_get_contents(__DIR__ . '/COPYING'),
        'memcache_prefix' => randomString(8),
    ];
    renderHTML($templateVars);
}
installerMain();
