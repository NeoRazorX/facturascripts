<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use DateTimeZone;
use FacturaScripts\Core\Base\PluginManager;
use FacturaScripts\Core\Base\ToolBox;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of AppInstaller
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
final class AppInstaller
{

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
        \define('FS_LANG', $this->request->get('fs_lang', $this->getUserLanguage()));

        $installed = false;
        if (false === $this->searchErrors() && $this->request->getMethod() === 'POST' &&
            $this->createDataBase() && $this->createFolders() && $this->saveHtaccess() && $this->saveInstall()) {
            $installed = true;
        }

        if ($installed && !empty($this->request->get('unattended', ''))) {
            echo 'OK';
        } elseif ($installed) {
            $this->render('Installer/Redir.html.twig');
        } elseif ('TRUE' === $this->request->get('phpinfo', '')) {
            /** @noinspection ForgottenDebugOutputInspection */
            \phpinfo();
        } else {
            $this->render();
        }
    }

    /**
     * Check database connection and creates the database if needed.
     *
     * @return bool
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

        $dbType = $this->request->request->get('fs_db_type');
        if ('postgresql' == $dbType && \strtolower($dbData['name']) != $dbData['name']) {
            ToolBox::i18nLog()->warning('database-name-must-be-lowercase');
            return false;
        }

        switch ($dbType) {
            case 'mysql':
                if (\class_exists('mysqli')) {
                    return $this->testMysql($dbData);
                }

                ToolBox::i18nLog()->critical('php-extension-not-found', ['%extension%' => 'mysqli']);
                return false;

            case 'postgresql':
                if (\function_exists('pg_connect')) {
                    return $this->testPostgreSql($dbData);
                }

                ToolBox::i18nLog()->critical('php-extension-not-found', ['%extension%' => 'postgresql']);
                return false;
        }

        ToolBox::i18nLog()->critical('cant-connect-database');
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
            if (false === ToolBox::files()->createFolder($folder)) {
                ToolBox::i18nLog()->critical('cant-create-folders', ['%folder%' => $folder]);
                return false;
            }
        }

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
        return ('/' === \substr($uri, -1)) ? \substr($uri, 0, -1) : $uri;
    }

    /**
     * Returns the user language to show the proper installation language in the selector.
     * When the JSON file doesn't exist, returns en_EN
     *
     * @return string
     */
    private function getUserLanguage()
    {
        $dataLanguage = \explode(';', \filter_input(INPUT_SERVER, 'HTTP_ACCEPT_LANGUAGE'));
        $userLanguage = \str_replace('-', '_', \explode(',', $dataLanguage[0])[0]);
        return \file_exists(\FS_FOLDER . '/Core/Translation/' . $userLanguage . '.json') ? $userLanguage : 'en_EN';
    }

    /**
     * Renders HTML.
     * 
     * @param string $template
     */
    private function render($template = 'Installer/Install.html.twig')
    {
        /// HTML template variables
        $templateVars = [
            'license' => \file_get_contents(\FS_FOLDER . DIRECTORY_SEPARATOR . 'COPYING'),
            'memcache_prefix' => ToolBox::utils()->randomString(8),
            'timezones' => DateTimeZone::listIdentifiers(),
            'version' => PluginManager::CORE_VERSION
        ];

        /// Load the template engine
        $webRender = new WebRender();

        /// Generate and return the HTML
        $response = new Response($webRender->render($template, $templateVars), Response::HTTP_OK);
        $response->send();
    }

    /**
     * Saves the htaccess file for the apache server.
     *
     * @return bool
     */
    private function saveHtaccess()
    {
        $contentFile = ToolBox::files()->extractFromMarkers(\FS_FOLDER . DIRECTORY_SEPARATOR . 'htaccess-sample', 'FacturaScripts code');
        return ToolBox::files()->insertWithMarkers($contentFile, \FS_FOLDER . DIRECTORY_SEPARATOR . '.htaccess', 'FacturaScripts code');
    }

    /**
     * Saves install parameters to config file.
     *
     * @return bool
     */
    private function saveInstall()
    {
        $file = \fopen(\FS_FOLDER . '/config.php', 'wb');
        if (\is_resource($file)) {
            \fwrite($file, "<?php\n");
            \fwrite($file, "define('FS_COOKIES_EXPIRE', " . $this->request->request->get('fs_cookie_expire', 604800) . ");\n");
            \fwrite($file, "define('FS_ROUTE', '" . $this->request->request->get('fs_route', $this->getUri()) . "');\n");
            \fwrite($file, "define('FS_DB_FOREIGN_KEYS', true);\n");
            \fwrite($file, "define('FS_DB_TYPE_CHECK', true);\n");
            \fwrite($file, "define('FS_MYSQL_CHARSET', 'utf8');\n");
            \fwrite($file, "define('FS_MYSQL_COLLATE', 'utf8_bin');\n");

            $fields = [
                'lang', 'timezone', 'db_type', 'db_host', 'db_port', 'db_name', 'db_user',
                'db_pass', 'cache_host', 'cache_port', 'cache_prefix', 'hidden_plugins'
            ];
            foreach ($fields as $field) {
                \fwrite($file, "define('FS_" . \strtoupper($field) . "', '" . $this->request->request->get('fs_' . $field, '') . "');\n");
            }

            $booleanFields = ['debug', 'disable_add_plugins', 'disable_rm_plugins'];
            foreach ($booleanFields as $field) {
                \fwrite($file, "define('FS_" . \strtoupper($field) . "', " . $this->request->request->get('fs_' . $field, 'false') . ");\n");
            }

            if ($this->request->request->get('db_type') === 'MYSQL' && $this->request->request->get('mysql_socket') !== '') {
                \fwrite($file, "\nini_set('mysqli.default_socket', '" . $this->request->request->get('mysql_socket') . "');\n");
            }

            \fwrite($file, "\n");
            \fclose($file);
            return true;
        }

        ToolBox::i18nLog()->critical('cant-save-install');
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
            ToolBox::i18nLog()->critical('wrong-decimal-separator');
            $errors = true;
        }

        foreach (['bcmath', 'curl', 'fileinfo', 'gd', 'mbstring', 'openssl', 'simplexml', 'zip'] as $extension) {
            if (false === \extension_loaded($extension)) {
                ToolBox::i18nLog()->critical('php-extension-not-found', ['%extension%' => $extension]);
                $errors = true;
            }
        }

        if (\function_exists('apache_get_modules') && false === \in_array('mod_rewrite', \apache_get_modules())) {
            ToolBox::i18nLog()->critical('apache-module-not-found', ['%module%' => 'mod_rewrite']);
            $errors = true;
        }

        if (false === \is_writable(\FS_FOLDER)) {
            ToolBox::i18nLog()->critical('folder-not-writable');
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
            \ini_set('mysqli.default_socket', $dbData['socket']);
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

        ToolBox::i18nLog()->critical('cant-connect-database');
        ToolBox::log()->critical((string) $connection->connect_errno . ': ' . $connection->connect_error);
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
        if (\is_resource($connection)) {
            // Check that the DB exists, if it doesn't, we try to create a new one
            $sqlExistsBD = "SELECT 1 AS result FROM pg_database WHERE datname = '" . $dbData['name'] . "';";
            $result = \pg_query($connection, $sqlExistsBD);
            if (\is_resource($result) && \pg_num_rows($result) > 0) {
                return true;
            }

            $sqlCreateBD = 'CREATE DATABASE "' . $dbData['name'] . '";';
            if (false !== \pg_query($connection, $sqlCreateBD)) {
                return true;
            }
        }

        ToolBox::i18nLog()->critical('cant-connect-database');
        if (\is_resource($connection) && \pg_last_error($connection) !== false) {
            ToolBox::log()->critical((string) \pg_last_error($connection));
        }

        return false;
    }
}
