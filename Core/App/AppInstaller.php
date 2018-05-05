<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018 Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\App;

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Base\PluginManager;
use FacturaScripts\Core\Base\Translator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of AppInstaller
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AppInstaller
{

    /**
     * Translation engine.
     *
     * @var Translator
     */
    private $i18n;

    /**
     * App log manager.
     *
     * @var MiniLog
     */
    private $miniLog;

    /**
     * Request on which we can get data.
     *
     * @var Request
     */
    private $request;

    /**
     * Starts installer and run it.
     */
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

        if ($installed && !empty($this->request->get('unattended', ''))) {
            echo 'OK';
        } elseif ($installed) {
            header('Location: ' . $this->getUri());
        } elseif ('TRUE' === $this->request->get('phpinfo', '')) {
            phpinfo();
        } else {
            $this->render();
        }
    }

    /**
     * Check database connection and creates the database if needed.
     *
     * @return boolean
     */
    private function createDataBase()
    {
        $dbData = [
            'host' => $this->request->request->get('fs_db_host'),
            'port' => $this->request->request->get('fs_db_port'),
            'user' => $this->request->request->get('fs_db_user'),
            'pass' => $this->request->request->get('fs_db_pass'),
            'name' => $this->request->request->get('fs_db_name'),
            'socket' => $this->request->request->get('mysql_socket', '')
        ];
        switch ($this->request->request->get('fs_db_type')) {
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
                $this->miniLog->critical($this->i18n->trans('cant-connect-database'));
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
        // Check each needed folder to deploy
        foreach (['Plugins', 'Dinamic', 'MyFiles'] as $folder) {
            if (!file_exists($folder) && !mkdir($folder) && !is_dir($folder)) {
                $this->miniLog->critical($this->i18n->trans('cant-create-folders', ['%folder%' => $folder]));
                return false;
            }
        }

        chmod('Plugins', octdec(777));
        $pluginManager = new PluginManager();
        $pluginManager->deploy();
        return true;
    }

    /**
     * Returns the request uri from server.
     *
     * @return string
     */
    private function getUri()
    {
        $uri = $this->request->getBasePath();
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

    /**
     * Renders HTML.
     */
    private function render()
    {
        /// HTML template variables
        $templateVars = [
            'license' => file_get_contents(FS_FOLDER . '/COPYING'),
            'memcache_prefix' => $this->randomString(8),
            'timezones' => $this->getTimezoneList()
        ];

        /// Load the template engine
        $webRender = new WebRender();

        /// Generate and return the HTML
        $response = new Response($webRender->render('Installer/Install.html.twig', $templateVars), Response::HTTP_OK);
        $response->send();
    }

    /**
     * Saves the htaccess file for the apache server.
     *
     * @return bool
     */
    private function saveHtaccess()
    {
        if (!file_exists(FS_FOLDER . '/.htaccess')) {
            $txt = file_get_contents(FS_FOLDER . '/htaccess-sample');
            file_put_contents(FS_FOLDER . '/.htaccess', \is_string($txt) ? $txt : '');
        }

        return true;
    }

    /**
     * Saves install parameters to config file.
     *
     * @return bool
     */
    private function saveInstall()
    {
        $file = fopen(FS_FOLDER . '/config.php', 'wb');
        if (\is_resource($file)) {
            fwrite($file, "<?php\n");
            fwrite($file, "define('FS_COOKIES_EXPIRE', 604800);\n");
            fwrite($file, "define('FS_DEBUG', true);\n");
            fwrite($file, "define('FS_ROUTE', '" . $this->getUri() . "');\n");
            fwrite($file, "define('FS_DB_FOREIGN_KEYS', true);\n");
            fwrite($file, "define('FS_DB_INTEGER', 'INTEGER');\n");
            fwrite($file, "define('FS_DB_TYPE_CHECK', true);\n");

            foreach (['lang', 'timezone', 'db_type', 'db_host', 'db_port', 'db_name', 'db_user', 'db_pass', 'cache_host', 'cache_port', 'cache_prefix'] as $field) {
                fwrite($file, "define('FS_" . strtoupper($field) . "', '" . $this->request->request->get('fs_' . $field) . "');\n");
            }

            fwrite($file, "define('FS_HIDDEN_PLUGINS', '" . \implode(',', $this->request->request->get('hidden_plugins', [])) . "');\n");
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

    /**
     * Check for common errors.
     *
     * @return bool
     */
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

        foreach (['bcmath', 'curl', 'simplexml', 'openssl', 'zip'] as $extension) {
            if (!extension_loaded($extension)) {
                $this->miniLog->critical($this->i18n->trans('php-extension-not-found', ['%extension%' => $extension]));
                $errors = true;
            }
        }

        if (!is_writable(FS_FOLDER)) {
            $this->miniLog->critical($this->i18n->trans('folder-not-writable'));
            $errors = true;
        }

        return $errors;
    }

    /**
     * Test the MySQL connection and creates the database if needed.
     *
     * @param array $dbData
     *
     * @return bool
     */
    private function testMysql($dbData)
    {
        if ($dbData['socket'] !== '') {
            ini_set('mysqli.default_socket', $dbData['socket']);
        }

        // Omit the DB name because it will be checked on a later stage
        $connection = @new \mysqli($dbData['host'], $dbData['user'], $dbData['pass'], '', (int) $dbData['port']);
        if (!$connection->connect_error) {
            // Check that the DB exists, if it doesn't, we create a new one
            $dbSelected = \mysqli_select_db($connection, $dbData['name']);
            if ($dbSelected) {
                return true;
            }

            $sqlCrearBD = 'CREATE DATABASE `' . $dbData['name'] . '`;';
            if ($connection->query($sqlCrearBD)) {
                return true;
            }
        }

        $this->miniLog->critical($this->i18n->trans('cant-connect-database'));
        $this->miniLog->critical((string) $connection->connect_errno . ': ' . $connection->connect_error);
        return false;
    }

    /**
     * Test the PostgreSQL connection and creates the database if needed.
     *
     * @param array $dbData
     *
     * @return bool
     */
    private function testPostgreSql($dbData)
    {
        $connectionStr = 'host=' . $dbData['host'] . ' port=' . $dbData['port'];
        $connection = @\pg_connect($connectionStr . ' dbname=postgres user=' . $dbData['user'] . ' password=' . $dbData['pass']);
        if (is_resource($connection)) {
            // Check that the DB exists, if it doesn't, we try to create a new one
            $sqlExistsBD = "SELECT 1 AS result FROM pg_database WHERE datname = '" . $dbData['name'] . "';";
            $result = \pg_query($connection, $sqlExistsBD);
            if (is_resource($result) && \pg_num_rows($result) > 0) {
                return true;
            }

            $sqlCreateBD = 'CREATE DATABASE "' . $dbData['name'] . '";';
            if (false !== \pg_query($connection, $sqlCreateBD)) {
                return true;
            }
        }

        $this->miniLog->critical($this->i18n->trans('cant-connect-database'));
        if (is_resource($connection) && \pg_last_error($connection) !== false) {
            $this->miniLog->critical((string) \pg_last_error($connection));
        }

        return false;
    }
}
