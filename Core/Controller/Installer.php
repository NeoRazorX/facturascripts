<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Controller;

use DateTimeZone;
use Exception;
use FacturaScripts\Core\Contract\ControllerInterface;
use FacturaScripts\Core\Html;
use FacturaScripts\Core\Kernel;
use FacturaScripts\Core\KernelException;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Tools;
use mysqli;

class Installer implements ControllerInterface
{
    /** @var string */
    public $db_host;

    /** @var string */
    public $db_name;

    /** @var string */
    public $db_pass;

    /** @var int */
    public $db_port;

    /** @var string */
    public $db_type;

    /** @var string */
    public $db_user;

    /** @var Request */
    protected $request;

    /** @var bool */
    protected $use_new_mysql = false;

    public function __construct(string $className, string $url = '')
    {
        $this->request = Request::createFromGlobals();

        $lang = $this->request->get('fs_lang', $this->getUserLanguage());
        Tools::lang()->setDefaultLang($lang);

        // si ya hay configuración de base de datos, lanzamos error de que ya está instalado
        if (Tools::config('db_name')) {
            throw new KernelException('AlreadyInstalled', Tools::lang()->trans('already-installed'));
        }

        Html::disablePlugins();
    }

    public function getPageData(): array
    {
        return [];
    }

    public function run(): void
    {
        $this->db_host = strtolower(trim($this->request->get('fs_db_host', 'localhost')));
        $this->db_name = strtolower(trim($this->request->get('fs_db_name', 'facturascripts')));
        $this->db_pass = $this->request->get('fs_db_pass', '');
        $this->db_port = (int)$this->request->get('fs_db_port', 3306);
        $this->db_type = $this->request->get('fs_db_type', 'mysql');
        $this->db_user = strtolower(trim($this->request->get('fs_db_user', 'root')));

        $installed = $this->searchErrors() &&
            $this->request->method() === 'POST' &&
            $this->createDataBase() &&
            $this->createFolders() &&
            $this->saveHtaccess() &&
            $this->saveInstall();

        if ($installed) {
            if (!empty($this->request->get('unattended', ''))) {
                echo 'OK';
                return;
            }

            echo Html::render('Installer/Redir.html.twig');
            return;
        }

        if ('TRUE' === $this->request->get('phpinfo', '')) {
            /** @noinspection ForgottenDebugOutputInspection */
            phpinfo();
            return;
        }

        echo Html::render('Installer/Install.html.twig', [
            'fsc' => $this,
            'license' => file_get_contents(FS_FOLDER . DIRECTORY_SEPARATOR . 'COPYING'),
            'timezones' => DateTimeZone::listIdentifiers(),
            'version' => Kernel::version()
        ]);
    }

    private function createDataBase(): bool
    {
        $dbData = [
            'host' => $this->db_host,
            'port' => $this->db_port,
            'user' => $this->db_user,
            'pass' => $this->db_pass,
            'name' => $this->db_name,
            'socket' => $this->request->request->get('mysql_socket', ''),
            'pgsql-ssl' => $this->request->request->get('pgsql_ssl_mode', ''),
            'pgsql-endpoint' => $this->request->request->get('pgsql_endpoint', '')
        ];

        if ('postgresql' == $this->db_type && strtolower($dbData['name']) != $dbData['name']) {
            Tools::log()->warning('database-name-must-be-lowercase');
            return false;
        }

        switch ($this->db_type) {
            case 'mysql':
                return $this->testMysql($dbData);

            case 'postgresql':
                return $this->testPostgresql($dbData);
        }

        Tools::log()->critical('cant-connect-database');
        return false;
    }

    private function createFolders(): bool
    {
        // Check each needed folder to deploy
        foreach (['Plugins', 'Dinamic', 'MyFiles'] as $folder) {
            if (file_exists(FS_FOLDER . DIRECTORY_SEPARATOR . $folder)) {
                continue;
            }

            if (false === mkdir($folder, 0755, true)) {
                Tools::log()->critical('cant-create-folders', ['%folder%' => $folder]);
                return false;
            }
        }

        Plugins::deploy();
        return true;
    }

    private function getUri(): string
    {
        $uri = $this->request->getBasePath();
        return ('/' === substr($uri, -1)) ? substr($uri, 0, -1) : $uri;
    }

    private function getUserLanguage(): string
    {
        $dataLanguage = explode(';', filter_input(INPUT_SERVER, 'HTTP_ACCEPT_LANGUAGE'));
        $userLanguage = str_replace('-', '_', explode(',', $dataLanguage[0])[0]);
        return file_exists(FS_FOLDER . '/Core/Translation/' . $userLanguage . '.json') ? $userLanguage : 'en_EN';
    }

    private function saveHtaccess(): bool
    {
        // guardamos el archivo .htaccess
        $file = fopen(FS_FOLDER . '/.htaccess', 'wb');
        if (false === is_resource($file)) {
            Tools::log()->critical('cant-save-htaccess');
            return false;
        }

        $samplePath = Tools::folder('htaccess-sample');
        $contentFile = file_get_contents($samplePath);

        // reemplazamos la ruta de la instalación
        $route = $this->request->request->get('fs_route', $this->getUri());
        if (!empty($route)) {
            $contentFile = str_replace('RewriteBase /', 'RewriteBase ' . $route, $contentFile);
        }

        fwrite($file, $contentFile);
        fclose($file);
        return true;
    }

    private function saveInstall(): bool
    {
        $file = fopen(FS_FOLDER . '/config.php', 'wb');
        if (false === is_resource($file)) {
            Tools::log()->critical('cant-save-install');
            return false;
        }

        fwrite($file, "<?php\n");
        fwrite($file, "define('FS_COOKIES_EXPIRE', " . $this->request->request->get('fs_cookie_expire', 31536000) . ");\n");
        fwrite($file, "define('FS_ROUTE', '" . $this->request->request->get('fs_route', $this->getUri()) . "');\n");
        fwrite($file, "define('FS_DB_TYPE', '" . $this->db_type . "');\n");
        fwrite($file, "define('FS_DB_HOST', '" . $this->db_host . "');\n");
        fwrite($file, "define('FS_DB_PORT', " . $this->db_port . ");\n");
        fwrite($file, "define('FS_DB_NAME', '" . $this->db_name . "');\n");
        fwrite($file, "define('FS_DB_USER', '" . $this->db_user . "');\n");
        fwrite($file, "define('FS_DB_PASS', '" . $this->db_pass . "');\n");
        fwrite($file, "define('FS_DB_FOREIGN_KEYS', true);\n");
        fwrite($file, "define('FS_DB_TYPE_CHECK', true);\n");

        if ($this->use_new_mysql) {
            // for new databases, we use utf8mb4
            fwrite($file, "define('FS_MYSQL_CHARSET', 'utf8mb4');\n");
            fwrite($file, "define('FS_MYSQL_COLLATE', 'utf8mb4_unicode_520_ci');\n");
        } elseif ($this->db_type === 'mysql') {
            // for existing databases, we use utf8
            fwrite($file, "define('FS_MYSQL_CHARSET', 'utf8');\n");
            fwrite($file, "define('FS_MYSQL_COLLATE', 'utf8_bin');\n");
        }

        if ($this->db_type === 'mysql' && $this->request->request->get('mysql_socket') !== '') {
            fwrite($file, "\nini_set('mysqli.default_socket', '" . $this->request->request->get('mysql_socket') . "');\n");
        } elseif ($this->db_type === 'postgresql') {
            fwrite($file, "define('FS_PGSQL_SSL', '" . $this->request->request->get('pgsql_ssl_mode') . "');\n");
            fwrite($file, "define('FS_PGSQL_ENDPOINT', '" . $this->request->request->get('pgsql_endpoint') . "');\n");
        }

        $fields = [
            'lang' => 'es_ES',
            'timezone' => 'Europe/Madrid',
            'hidden_plugins' => ''
        ];
        foreach ($fields as $field => $default) {
            fwrite($file, "define('FS_" . strtoupper($field) . "', '" . $this->request->request->get('fs_' . $field, $default) . "');\n");
        }

        $booleanFields = ['debug', 'disable_add_plugins', 'disable_rm_plugins'];
        foreach ($booleanFields as $field) {
            fwrite($file, "define('FS_" . strtoupper($field) . "', " . $this->request->request->get('fs_' . $field, 'false') . ");\n");
        }

        if ($this->request->request->get('fs_gtm', false)) {
            fwrite($file, "define('GOOGLE_TAG_MANAGER', 'GTM-53H8T9BL');\n");
        }

        fclose($file);
        return true;
    }

    private function searchErrors(): bool
    {
        $errors = false;

        if ((float)'3,1' >= (float)'3.1') {
            Tools::log()->critical('wrong-decimal-separator');
            $errors = true;
        }

        foreach (['bcmath', 'curl', 'fileinfo', 'gd', 'mbstring', 'openssl', 'simplexml', 'zip'] as $extension) {
            if (false === extension_loaded($extension)) {
                Tools::log()->critical('php-extension-not-found', ['%extension%' => $extension]);
                $errors = true;
            }
        }

        if (function_exists('apache_get_modules') && false === in_array('mod_rewrite', apache_get_modules())) {
            Tools::log()->critical('apache-module-not-found', ['%module%' => 'mod_rewrite']);
            $errors = true;
        }

        if (false === is_writable(FS_FOLDER)) {
            Tools::log()->critical('folder-not-writable');
            $errors = true;
        }

        return $errors === false;
    }

    private function testMysql(array $dbData): bool
    {
        if (false === class_exists('mysqli')) {
            Tools::log()->critical('php-extension-not-found', ['%extension%' => 'mysqli']);
            return false;
        }

        if ($dbData['socket'] !== '') {
            ini_set('mysqli.default_socket', $dbData['socket']);
        }

        try {
            // Omit the DB name because it will be checked on a later stage
            $connection = @new mysqli($dbData['host'], $dbData['user'], $dbData['pass'], '', $dbData['port']);
            if ($connection->connect_error) {
                Tools::log()->critical('cant-connect-database');
                Tools::log()->critical($connection->connect_errno . ': ' . $connection->connect_error);
                return false;
            }
        } catch (Exception $e) {
            Tools::log()->critical('cant-connect-database');
            Tools::log()->critical($e->getMessage());
            return false;
        }

        // if mysql version is too old, we can't continue
        if ($connection->server_version < 50700) {
            Tools::log()->critical('mysql-version-too-old');
            return false;
        }

        // create the database if it doesn't exist
        $sqlCrearBD = 'CREATE DATABASE IF NOT EXISTS ' . $connection->escape_string($dbData['name']) . ';';
        if (!$connection->query($sqlCrearBD)) {
            return false;
        }

        // for mysql >= 8 or mariadb >= 10.2, we use utf8mb4
        $version = $connection->server_version;
        $this->use_new_mysql = $version >= 100200 || ($version >= 80000 && $version < 100000);

        // except if there is a previous installation, with te table co_cuentas
        $sql = 'SHOW TABLES FROM ' . $connection->escape_string($dbData['name']) . ';';
        $result = $connection->query($sql);
        if (false !== $result) {
            while ($row = $result->fetch_row()) {
                if ('co_cuentas' === $row[0]) {
                    $this->use_new_mysql = false;
                    break;
                }
            }
        }

        return true;
    }

    private function testPostgresql(array $dbData): bool
    {
        if (false === function_exists('pg_connect')) {
            Tools::log()->critical('php-extension-not-found', ['%extension%' => 'postgresql']);
            return false;
        }

        $connectionStr = 'host=' . $dbData['host'] . ' port=' . $dbData['port']
            . ' user=' . $dbData['user'] . ' password=' . $dbData['pass'];

        if ($dbData['pgsql-ssl'] !== '') {
            $connectionStr .= ' sslmode=' . $dbData['pgsql-ssl'];
        }

        if ($dbData['pgsql-endpoint'] !== '') {
            $connectionStr .= " options='endpoint=" . $dbData['pgsql-endpoint'] . "'";
        }

        // try to connect to the database
        $connection = pg_connect($connectionStr . ' dbname=' . $dbData['name']);
        if (is_resource($connection)) {
            // if postgresql version is too old, we can't continue
            if ($this->versionPostgres($connection) < 10) {
                Tools::log()->critical('postgresql-version-too-old');
                return false;
            }

            return true;
        }

        // can't connect to the database, try to connect to the default database
        $connection = pg_connect($connectionStr . ' dbname=postgres');
        if (is_resource($connection)) {
            // if postgresql version is too old, we can't continue
            if ($this->versionPostgres($connection) < 10) {
                Tools::log()->critical('postgresql-version-too-old');
                return false;
            }

            // create the database
            $sqlCreateBD = 'CREATE DATABASE ' . pg_escape_string($connection, $dbData['name']) . ';';
            if (false !== @pg_query($connection, $sqlCreateBD)) {
                return true;
            }

            if (pg_last_error($connection) != false) {
                Tools::log()->critical(pg_last_error($connection));
                return false;
            }

            Tools::log()->critical('cant-create-database');
            return false;
        }

        Tools::log()->critical('cant-connect-database');
        if (is_resource($connection) && pg_last_error($connection) != false) {
            Tools::log()->critical(pg_last_error($connection));
        }

        return false;
    }

    private function versionPostgres($connection): float
    {
        $version = pg_version($connection);
        $parts = explode(' ', $version['server']);
        return (float)$parts[0];
    }
}
