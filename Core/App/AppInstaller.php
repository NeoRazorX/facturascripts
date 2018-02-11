<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\App;

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Base\Translator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig_Environment;
use Twig_Loader_Filesystem;

/**
 * Description of AppInstaller
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AppInstaller
{

    private $i18n;
    private $miniLog;
    private $request;

    public function __construct()
    {
        $this->request = Request::createFromGlobals();

        define('FS_LANG', $this->request->get('fs_lang', $this->getUserLanguage()));
        $this->i18n = new Translator();
        $this->miniLog = new MiniLog();

        $installed = false;
        if (!$this->searchErrors() && $this->request->getMethod() === 'POST') {
            if ($this->createDataBase() && $this->createFolders() && $this->saveHtaccess() && $this->saveInstall()) {
                $installed = true;
            }
        }

        if ($installed) {
            header('Location: ' . $this->getUri());
        } else {
            $this->render();
        }
    }

    private function createDataBase()
    {
        $dbData = [
            'host' => $this->request->request->get('db_host'),
            'port' => $this->request->request->get('db_port'),
            'user' => $this->request->request->get('db_user'),
            'pass' => $this->request->request->get('db_pass'),
            'name' => $this->request->request->get('db_name'),
            'socket' => $this->request->request->get('mysql_socket', '')
        ];
        switch ($this->request->request->get('db_type')) {
            case 'mysql':
                if (class_exists('mysqli')) {
                    return $this->testMysql($dbData);
                }

                $this->miniLog->alert($this->i18n->trans('mysqli-not-found'));
                break;

            case 'postgresql':
                if (function_exists('pg_connect')) {
                    return $this->testPostgreSql($dbData);
                }

                $this->miniLog->alert($this->i18n->trans('postgresql-not-found'));
                break;

            default:
                $this->miniLog->alert($this->i18n->trans('cant-connect-db'));
        }

        return false;
    }

    /**
     * If the needed directories are created or already exist, returns true. False when not.
     *
     * @return bool
     */
    private function createFolders()
    {
        // If they already exist, we can return true
        if (is_dir('Plugins') && is_dir('Dinamic') && is_dir('MyFiles')) {
            return true;
        }

        if (mkdir('Plugins') && mkdir('Dinamic') && mkdir('MyFiles')) {
            chmod('Plugins', octdec(777));
            return true;
        }

        $this->miniLog->critical($this->i18n->trans('cant-create-folders'));
        return false;
    }

    private function getUri()
    {
        $uri = $this->request->server->get('REQUEST_URI');
        if ('/' === substr($uri, -1)) {
            return substr($uri, 0, -1);
        }

        return $uri;
    }

    /**
     * Returns the user language to show the proper installation language in the selector.
     * When the JSON file doesn't exist, returns en_EN
     *
     * @return string
     */
    private function getUserLanguage()
    {
        $dataLanguage = explode(';', filter_input(INPUT_SERVER, 'HTTP_ACCEPT_LANGUAGE'));
        $userLanguage = str_replace('-', '_', explode(',', $dataLanguage[0])[0]);
        $translationExists = file_exists(FS_FOLDER . '/Core/Translation/' . $userLanguage . '.json');

        return ($translationExists) ? $userLanguage : 'en_EN';
    }

    /**
     * Timezones list with GMT offset
     *
     * @return array
     *
     * @link http://stackoverflow.com/a/9328760
     */
    private function getTimezoneList()
    {
        $zonesArray = [];
        $timestamp = time();
        foreach (timezone_identifiers_list() as $key => $zone) {
            date_default_timezone_set($zone);
            $zonesArray[$key]['zone'] = $zone;
            $zonesArray[$key]['diff_from_GMT'] = 'UTC/GMT ' . date('P', $timestamp);
        }

        return $zonesArray;
    }

    /**
     * Return a random string
     *
     * @param int $length
     *
     * @return bool|string
     */
    private function randomString($length = 20)
    {
        return substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
    }

    private function render()
    {
        /// HTML template variables
        $templateVars = [
            'i18n' => $this->i18n,
            'license' => file_get_contents(FS_FOLDER . '/COPYING'),
            'log' => $this->miniLog,
            'memcache_prefix' => $this->randomString(8),
            'timezones' => $this->getTimezoneList()
        ];

        /// Load the template engine
        $twigLoader = new Twig_Loader_Filesystem(FS_FOLDER . '/Core/View');
        $twig = new Twig_Environment($twigLoader);

        /// Generate and return the HTML
        $response = new Response($twig->render('Installer/Install.html.twig', $templateVars), Response::HTTP_OK);
        $response->send();
    }

    private function saveHtaccess()
    {
        if (!file_exists(FS_FOLDER . '/.htaccess')) {
            $txt = file_get_contents(FS_FOLDER . '/htaccess-sample');
            file_put_contents(FS_FOLDER . '/.htaccess', \is_string($txt) ? $txt : '');
        }

        return true;
    }

    private function saveInstall()
    {
        $file = fopen(FS_FOLDER . '/config.php', 'wb');
        if ($file) {
            fwrite($file, "<?php\n");
            fwrite($file, "define('FS_COOKIES_EXPIRE', 604800);\n");
            fwrite($file, "define('FS_DEBUG', true);\n");
            fwrite($file, "define('FS_LANG', '" . $this->request->request->get('fs_lang') . "');\n");
            fwrite($file, "define('FS_ROUTE', '" . $this->getUri() . "');\n");
            fwrite($file, "define('FS_TIMEZONE', '" . $this->request->request->get('fs_timezone') . "');\n");
            fwrite($file, "define('FS_DB_TYPE', '" . $this->request->request->get('db_type') . "');\n");
            fwrite($file, "define('FS_DB_HOST', '" . $this->request->request->get('db_host') . "');\n");
            fwrite($file, "define('FS_DB_PORT', '" . $this->request->request->get('db_port') . "');\n");
            fwrite($file, "define('FS_DB_NAME', '" . $this->request->request->get('db_name') . "');\n");
            fwrite($file, "define('FS_DB_USER', '" . $this->request->request->get('db_user') . "');\n");
            fwrite($file, "define('FS_DB_PASS', '" . $this->request->request->get('db_pass') . "');\n");
            fwrite($file, "define('FS_DB_FOREIGN_KEYS', true);\n");
            fwrite($file, "define('FS_DB_INTEGER', 'INTEGER');\n");
            fwrite($file, "define('FS_DB_TYPE_CHECK', true);\n");
            fwrite($file, "define('FS_CACHE_HOST', '" . $this->request->request->get('memcache_host') . "');\n");
            fwrite($file, "define('FS_CACHE_PORT', '" . $this->request->request->get('memcache_port') . "');\n");
            fwrite($file, "define('FS_CACHE_PREFIX', '" . $this->request->request->get('memcache_prefix') . "');\n");
            if ($this->request->request->get('db_type') === 'MYSQL' && $this->request->request->get('mysql_socket') !== '') {
                fwrite($file, "\nini_set('mysqli.default_socket', '" . $this->request->request->get('mysql_socket') . "');\n");
            }
            fwrite($file, "\n");
            fclose($file);
            return true;
        }

        $this->miniLog->critical($this->i18n->trans('cant-save-install'));
        return false;
    }

    private function searchErrors()
    {
        $errors = false;

        if ((float) '3,1' >= (float) '3.1') {
            $this->miniLog->critical($this->i18n->trans('wrong-decimal-separator'));
            $errors = true;
        }

        if (!function_exists('mb_substr')) {
            $this->miniLog->critical($this->i18n->trans('mb-string-not-fount'));
            $errors = true;
        }

        if (!extension_loaded('simplexml')) {
            $this->miniLog->critical($this->i18n->trans('simplexml-not-found'));
            $errors = true;
        }

        if (!extension_loaded('openssl')) {
            $this->miniLog->critical($this->i18n->trans('openssl-not-found'));
            $errors = true;
        }

        if (!extension_loaded('zip')) {
            $this->miniLog->critical($this->i18n->trans('ziparchive-not-found'));
            $errors = true;
        }

        if (!is_writable(FS_FOLDER)) {
            $this->miniLog->critical($this->i18n->trans('folder-not-writable'));
            $errors = true;
        }

        return $errors;
    }

    private function testMysql($dbData)
    {
        if ($dbData['socket'] !== '') {
            ini_set('mysqli.default_socket', $dbData['socket']);
        }

        // Omit the DB name because it will be checked on a later stage
        $connection = new \mysqli($dbData['host'], $dbData['user'], $dbData['pass'], '', (int) $dbData['port']);
        if ($connection->connect_error) {
            $this->miniLog->critical((string) $connection->connect_error);
        } else {
            // Check that the DB exists, if it doesn't, we create a new one
            $dbSelected = \mysqli_select_db($connection, $dbData['name']);
            if ($dbSelected) {
                return true;
            }

            $sqlCrearBD = 'CREATE DATABASE `' . $dbData['name'] . '`;';
            if ($connection->query($sqlCrearBD)) {
                return true;
            }

            $this->miniLog->critical((string) $connection->connect_error);
        }

        return false;
    }

    private function testPostgreSql($dbData)
    {
        $connectionStr = 'host=' . $dbData['host'] . ' port=' . $dbData['port'];
        $connection = \pg_connect($connectionStr . ' user=' . $dbData['user'] . ' password=' . $dbData['pass']);
        if ($connection) {
            // Check that the DB exists, if it doesn't, we create a new one
            $connection2 = \pg_connect($connectionStr . ' dbname=' . $dbData['name'] . ' user=' . $dbData['user'] . ' password=' . $dbData['pass']);
            if ($connection2) {
                return true;
            }

            $sqlCrearBD = 'CREATE DATABASE "' . $dbData['name'] . '";';
            if (\pg_query($connection, $sqlCrearBD)) {
                return true;
            }

            $this->miniLog->critical((string) \pg_last_error($connection));
        }

        return false;
    }
}
