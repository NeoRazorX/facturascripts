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

/// comprobaciones previas
if (file_exists(__DIR__ . '/config.php')) {
    /**
     * Si hay fichero de configuración significa que ya se ha instalado,
     * así que redirigimos al index.
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

use FacturaScripts\Core\Base\Translator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Devuelve un array de errores con las situaciones conocidas
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
 * Devuelve un array de idiomas, donde la key es el nombre del archivo JSON y
 * el value es su correspondiente traducción.
 *
 * @param $i18n
 *
 * @return array
 */
function getLanguages(&$i18n)
{
    $languages = [];

    foreach (scandir(__DIR__ . '/Core/Translation', SCANDIR_SORT_ASCENDING) as $fileName) {
        if ($fileName !== '.' && $fileName !== '..' && !is_dir($fileName) && substr($fileName, -5) === '.json') {
            $key = substr($fileName, 0, -5);
            $languages[$key] = $i18n->trans('languages-' . substr($fileName, 0, -5));
        }
    }

    return $languages;
}

/**
 * Devuelve el lenguaje del usuario para mostrar en el selector el idioma correcto
 * para la instalación. En caso de que no exista el archivo json devuelve en_EN
 * @return string
 */
function getUserLanguage()
{
    $dataLanguage = explode(';', \filter_input(INPUT_SERVER, 'HTTP_ACCEPT_LANGUAGE'));
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
 * Se intenta realizar la conexión a la base de datos,
 * si se ha realizado se devuelve true, sino false.
 * En el caso que sea false, $errors contiene el error
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
 * Se intenta realizar la conexión a la base de datos MySQL,
 * si se ha realizado se devuelve true, sino false.
 * En el caso que sea false, $errors contiene el error
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

    // Omitimos el valor del nombre de la BD porque lo comprobaremos más tarde
    $connection = new mysqli($dbData['host'], $dbData['user'], $dbData['pass'], '', (int) $dbData['port']);
    if ($connection->connect_error) {
        $errors[] = (string) $connection->connect_error;
    } else {
        // Comprobamos que la BD exista, de lo contrario la creamos
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
 * Se intenta realizar la conexión a la base de datos PostgreSQL,
 * si se ha realizado se devuelve true, sino false.
 * En el caso que sea false, $errors contiene el error
 *
 * @param $errors
 * @param $dbData
 *
 * @return bool
 */
function testPostgreSql(&$errors, $dbData)
{
    $done = false;

    $connection = pg_connect('host=' . $dbData['host'] . ' port=' . $dbData['port'] . ' user=' . $dbData['user'] . ' password=' . $dbData['pass']);
    if ($connection) {
        // Comprobamos que la BD exista, de lo contrario la creamos
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
 * Si se han creado las carpetas necesarias, o ya existen
 * se devuelve true, sino false
 *
 * @return bool
 */
function createFolders()
{
    // En caso que ya existan previamente, podemos devolver true
    if (is_dir('Plugins') && is_dir('Dinamic') && is_dir('Cache')) {
        return true;
    }
    if (mkdir('Plugins') && mkdir('Dinamic') && mkdir('Cache')) {
        return true;
    }

    return false;
}

/**
 * Guarda la configuración en config.php,
 * devuelve true en caso afirmativo, y sino false.
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
        if (filter_input(INPUT_POST, 'db_type') === 'MYSQL' && filter_input(INPUT_POST, 'mysql_socket') !== '') {
            fwrite($file, "ini_set('mysqli.default_socket', '" . filter_input(INPUT_POST, 'mysql_socket') . "');\n");
        }
        fwrite($file, "\n");
        fclose($file);

        return true;
    }

    return false;
}

/**
 * Renderiza la vista y devuelve la respuesta
 *
 * @param $templateVars
 */
function renderHTML(&$templateVars)
{
    /// cargamos el motor de plantillas
    $twigLoader = new Twig_Loader_Filesystem(__DIR__ . '/Core/View');
    $twig = new Twig_Environment($twigLoader);

    /// generamos y volcamos el html
    $response = new Response($twig->render('Installer/Install.html', $templateVars), Response::HTTP_OK);
    $response->send();
}

/**
 * Función principal del instalador
 *
 * @return int
 */
function installerMain()
{
    $errors = [];

    if (filter_input(INPUT_POST, 'fs_lang')) {
        $i18n = new Translator(__DIR__, filter_input(INPUT_POST, 'fs_lang'));
    } elseif (filter_input(INPUT_GET, 'fs_lang')) {
        $i18n = new Translator(__DIR__, filter_input(INPUT_GET, 'fs_lang'));
    } else {
        $i18n = new Translator(__DIR__, getUserLanguage());
    }

    searchErrors($errors, $i18n);

    if (empty($errors) && filter_input(INPUT_POST, 'db_type')) {
        if (dbConnect($errors, $i18n) && createFolders() && saveInstall()) {
            header('Location: index.php');

            return 0;
        }
    }

    /// empaquetamos las variables a pasar el motor de plantillas
    $templateVars = [
        'errors' => $errors,
        'i18n' => $i18n,
        'languages' => getLanguages($i18n),
        'timezone' => get_timezone_list(),
        'license' => file_get_contents(__DIR__ . '/COPYING'),
    ];
    renderHTML($templateVars);
}
installerMain();
