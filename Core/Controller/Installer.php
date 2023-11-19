<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Contract\ControllerInterface;
use FacturaScripts\Core\Html;
use FacturaScripts\Core\Kernel;
use FacturaScripts\Core\KernelException;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Tools;
use mysqli;
use Symfony\Component\HttpFoundation\Request;

class Installer implements ControllerInterface
{
    /** @var bool */
    protected $created_mysql_db = false;

    /** @var Request */
    protected $request;

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
        $installed = $this->searchErrors() &&
            $this->request->getMethod() === 'POST' &&
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
            'license' => file_get_contents(FS_FOLDER . DIRECTORY_SEPARATOR . 'COPYING'),
            'timezones' => DateTimeZone::listIdentifiers(),
            'version' => Kernel::version()
        ]);
    }

    private function createDataBase(): bool
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
        if ('postgresql' == $dbType && strtolower($dbData['name']) != $dbData['name']) {
            Tools::log()->warning('database-name-must-be-lowercase');
            return false;
        }

        switch ($dbType) {
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

    private function extractFromMarkers(string $fileName, string $marker): array
    {
        $result = [];
        if (!file_exists($fileName)) {
            return $result;
        }

        $markerData = explode("\n", file_get_contents($fileName));
        $state = false;
        foreach ($markerData as $markerLine) {
            if (false !== strpos($markerLine, '# END ' . $marker)) {
                $state = false;
            }
            if ($state) {
                $result[] = $markerLine;
            }
            if (false !== strpos($markerLine, '# BEGIN ' . $marker)) {
                $state = true;
            }
        }

        return $result;
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

    private function insertWithMarkers(array $insertion, string $fileName, string $marker): bool
    {
        if (!file_exists($fileName)) {
            if (!is_writable(dirname($fileName))) {
                return false;
            }
            if (!touch($fileName)) {
                return false;
            }
        } elseif (!is_writable($fileName)) {
            return false;
        }

        $startMarker = '# BEGIN ' . $marker;
        $endMarker = '# END ' . $marker;
        $fp = fopen($fileName, 'rb+');
        if (!$fp) {
            return false;
        }

        // Attempt to get a lock. If the filesystem supports locking, this will block until the lock is acquired.
        flock($fp, LOCK_EX);
        $lines = [];
        while (!feof($fp)) {
            $lines[] = rtrim(fgets($fp), "\r\n");
        }

        // Split out the existing file into the preceding lines, and those that appear after the marker
        $preLines = $postLines = $existingLines = [];
        $foundMarker = $foundEndMarker = false;
        foreach ($lines as $line) {
            if (!$foundMarker && false !== strpos($line, $startMarker)) {
                $foundMarker = true;
                continue;
            }
            if (!$foundEndMarker && false !== strpos($line, $endMarker)) {
                $foundEndMarker = true;
                continue;
            }
            if (!$foundMarker) {
                $preLines[] = $line;
            } elseif ($foundEndMarker) {
                $postLines[] = $line;
            } else {
                $existingLines[] = $line;
            }
        }

        // Check to see if there was a change
        if ($existingLines === $insertion) {
            flock($fp, LOCK_UN);
            fclose($fp);
            return true;
        }

        // If it's true, is the old content version without the tags marker, we can remove it
        if (empty(array_diff($insertion, $preLines))) {
            $preLines = [];
        }

        // Generate the new file data
        $newFileData = implode(
            PHP_EOL, array_merge(
                $preLines, [$startMarker], $insertion, [$endMarker], $postLines
            )
        );

        // Write to the start of the file, and truncate it to that length
        fseek($fp, 0);
        $bytes = fwrite($fp, $newFileData);
        if ($bytes) {
            ftruncate($fp, ftell($fp));
        }
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        return (bool)$bytes;
    }

    private function saveHtaccess(): bool
    {
        $contentFile = $this->extractFromMarkers(FS_FOLDER . DIRECTORY_SEPARATOR . 'htaccess-sample', 'FacturaScripts code');
        return $this->insertWithMarkers($contentFile, FS_FOLDER . DIRECTORY_SEPARATOR . '.htaccess', 'FacturaScripts code');
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
        fwrite($file, "define('FS_DB_FOREIGN_KEYS', true);\n");
        fwrite($file, "define('FS_DB_TYPE_CHECK', true);\n");

        if ($this->created_mysql_db) {
            // for new databases, we use utf8mb4
            fwrite($file, "define('FS_MYSQL_CHARSET', 'utf8mb4');\n");
            fwrite($file, "define('FS_MYSQL_COLLATE', 'utf8mb4_unicode_520_ci');\n");
        } elseif ($this->request->request->get('db_type') === 'MYSQL') {
            // for existing databases, we use utf8
            fwrite($file, "define('FS_MYSQL_CHARSET', 'utf8');\n");
            fwrite($file, "define('FS_MYSQL_COLLATE', 'utf8_bin');\n");
        }

        $fields = ['lang', 'timezone', 'db_type', 'db_host', 'db_port', 'db_name', 'db_user', 'db_pass', 'hidden_plugins'];
        foreach ($fields as $field) {
            fwrite($file, "define('FS_" . strtoupper($field) . "', '" . $this->request->request->get('fs_' . $field, '') . "');\n");
        }

        $booleanFields = ['debug', 'disable_add_plugins', 'disable_rm_plugins'];
        foreach ($booleanFields as $field) {
            fwrite($file, "define('FS_" . strtoupper($field) . "', " . $this->request->request->get('fs_' . $field, 'false') . ");\n");
        }

        if ($this->request->request->get('db_type') === 'MYSQL' && $this->request->request->get('mysql_socket') !== '') {
            fwrite($file, "\nini_set('mysqli.default_socket', '" . $this->request->request->get('mysql_socket') . "');\n");
        }

        fwrite($file, "\n");
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

        // Omit the DB name because it will be checked on a later stage
        $connection = @new mysqli($dbData['host'], $dbData['user'], $dbData['pass'], '', (int)$dbData['port']);
        if ($connection->connect_error) {
            Tools::log()->critical('cant-connect-database');
            Tools::log()->critical($connection->connect_errno . ': ' . $connection->connect_error);
            return false;
        }

        $sqlCrearBD = 'CREATE DATABASE IF NOT EXISTS `' . $dbData['name'] . '`;';
        if ($connection->query($sqlCrearBD)) {
            $this->created_mysql_db = true;
            return true;
        }

        return false;
    }

    private function testPostgresql(array $dbData): bool
    {
        if (false === function_exists('pg_connect')) {
            Tools::log()->critical('php-extension-not-found', ['%extension%' => 'postgresql']);
            return false;
        }

        $connectionStr = 'host=' . $dbData['host'] . ' port=' . $dbData['port'];
        $connection = @pg_connect($connectionStr . ' dbname=postgres user=' . $dbData['user'] . ' password=' . $dbData['pass']);
        if (is_resource($connection)) {
            // Check that the DB exists, if it doesn't, we try to create a new one
            $sqlExistsBD = "SELECT 1 AS result FROM pg_database WHERE datname = '" . $dbData['name'] . "';";
            $result = pg_query($connection, $sqlExistsBD);
            if (is_resource($result) && pg_num_rows($result) > 0) {
                return true;
            }

            $sqlCreateBD = 'CREATE DATABASE "' . $dbData['name'] . '";';
            if (false !== pg_query($connection, $sqlCreateBD)) {
                return true;
            }
        }

        Tools::log()->critical('cant-connect-database');
        if (is_resource($connection) && pg_last_error($connection) != false) {
            Tools::log()->critical(pg_last_error($connection));
        }

        return false;
    }
}
