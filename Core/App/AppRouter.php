<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\MyFilesToken;
use FacturaScripts\Core\Plugins;

/**
 * Description of AppRouter
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
final class AppRouter
{
    /**
     * Path to list of routes stored on file.
     */
    const ROUTE_LIST_FILE = FS_FOLDER . DIRECTORY_SEPARATOR . 'MyFiles' . DIRECTORY_SEPARATOR . 'routes.json';

    /**
     * List of routes.
     *
     * @var array
     */
    private $routes;

    /**
     * AppRouter constructor.
     */
    public function __construct()
    {
        if (false === defined('FS_ROUTE')) {
            define('FS_ROUTE', '');
        }

        $this->routes = $this->loadFromFile();
    }

    /**
     * Clear the App routes.
     */
    public function clear()
    {
        $this->routes = [];
        $this->save();
    }

    /**
     * Return the specific App controller for any kind of petition.
     *
     * @return App
     */
    public function getApp()
    {
        $uri = $this->getUri();
        if ('/api' === $uri || '/api/' === substr($uri, 0, 5)) {
            return new AppAPI($uri);
        }

        if ('/cron' === $uri) {
            return new AppCron($uri);
        }

        if ('/deploy' === $uri) {
            $this->deploy();
        }

        foreach ($this->routes as $key => $data) {
            if ($uri === $key) {
                return $this->newAppController($uri, $data['controller']);
            }

            if ('*' !== substr($key, -1)) {
                continue;
            }

            if (0 === strncmp($uri, $key, strlen($key) - 1)) {
                return $this->newAppController($uri, $data['controller']);
            }
        }

        return $this->newAppController($uri);
    }

    /**
     * Return true if can output a file, false otherwise.
     *
     * @return bool
     */
    public function getFile(): bool
    {
        $uri = $this->getUri();
        $filePath = FS_FOLDER . urldecode($uri);

        // favicon.ico
        if ('/favicon.ico' == $uri) {
            $filePath = FS_FOLDER . '/Dinamic/Assets/Images/favicon.ico';
            header('Content-Type: ' . $this->getMime($filePath));
            readfile($filePath);
            return true;
        }

        // Not a file? Not a safe file?
        if (false === is_file($filePath) || false === $this->isFileSafe($filePath)) {
            return false;
        }

        // Allowed folder?
        $allowedFolders = ['node_modules', 'vendor', 'Dinamic', 'Core', 'Plugins', 'MyFiles/Public'];
        foreach ($allowedFolders as $folder) {
            if ('/' . $folder === substr($uri, 0, 1 + strlen($folder))) {
                $this->download($filePath);
                return true;
            }
        }

        // MyFiles and token?
        $token = filter_input(INPUT_GET, 'myft');
        $fixedFilePath = substr(urldecode($uri), 1);
        if ('/MyFiles/' === substr($uri, 0, 9) && $token && MyFilesToken::validate($fixedFilePath, $token)) {
            $this->download($filePath);
            return true;
        }

        return false;
    }

    /**
     * @param string $filePath
     *
     * @return bool
     */
    public static function isFileSafe(string $filePath): bool
    {
        $parts = explode('.', $filePath);
        $safe = [
            'accdb', 'avi', 'cdr', 'css', 'csv', 'doc', 'docx', 'eot', 'gif', 'gz', 'html', 'ico', 'jpeg', 'jpg', 'js',
            'json', 'map', 'mdb', 'mkv', 'mp3', 'mp4', 'ndg', 'ods', 'odt', 'ogg', 'pdf', 'png', 'pptx', 'sql', 'svg',
            'ttf', 'txt', 'webm', 'woff', 'woff2', 'xls', 'xlsx', 'xml', 'xsig', 'zip'
        ];
        return empty($parts) || count($parts) === 1 || in_array(end($parts), $safe, true);
    }

    /**
     * Adds this route to the ap routes.
     *
     * @param string $newRoute
     * @param string $controllerName
     * @param string $optionalId
     * @param bool $checkOptionalId
     */
    public function setRoute(string $newRoute, string $controllerName, string $optionalId = '', bool $checkOptionalId = true)
    {
        if (!empty($optionalId) && $checkOptionalId) {
            // if optionalId, then remove previous items with that data
            foreach ($this->routes as $route => $routeItem) {
                if ($routeItem['controller'] === $controllerName && $routeItem['optionalId'] === $optionalId) {
                    unset($this->routes[$route]);
                }
            }
        }

        $this->routes[$newRoute] = [
            'controller' => $controllerName,
            'optionalId' => $optionalId
        ];

        $this->save();
    }

    /**
     * Deploy all dynamic files.
     */
    private function deploy()
    {
        if (false === file_exists(FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic')) {
            Plugins::deploy();
        }
    }

    private function download(string $filePath)
    {
        header('Content-Type: ' . $this->getMime($filePath));

        // disable the buffer if enabled
        if (ob_get_contents()) {
            ob_end_flush();
        }

        // force to download svg, xml and xsig files to prevent XSS attacks
        $info = pathinfo($filePath);
        $extension = strtolower($info['extension']);
        if (in_array($extension, ['svg', 'xml', 'xsig'])) {
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        }

        readfile($filePath);
    }

    /**
     * Return the mime type from given file.
     *
     * @param string $filePath
     *
     * @return string
     */
    private function getMime(string $filePath): string
    {
        $info = pathinfo($filePath);
        $extension = strtolower($info['extension']);
        switch ($extension) {
            case 'css':
                return 'text/css';

            case 'js':
                return 'application/javascript';

            case 'xml':
            case 'xsig':
                return 'text/xml';
        }

        return mime_content_type($filePath);
    }

    /**
     * Return the uri from the request.
     *
     * @return bool|string
     */
    private function getUri()
    {
        $uri = filter_input(INPUT_SERVER, 'REQUEST_URI');
        $uri2 = is_null($uri) ? filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL) : $uri;
        $uriArray = explode('?', $uri2);

        return substr($uriArray[0], strlen(FS_ROUTE));
    }

    /**
     * Returns an array with the list of plugins in the plugin.list file.
     *
     * @return array
     */
    private function loadFromFile(): array
    {
        if (file_exists(self::ROUTE_LIST_FILE)) {
            $content = file_get_contents(self::ROUTE_LIST_FILE);
            if ($content !== false) {
                return json_decode($content, true);
            }
        }

        return [];
    }

    /**
     * @param string $uri
     * @param string $pageName
     *
     * @return App
     */
    private function newAppController(string $uri, string $pageName = '')
    {
        return FS_DEBUG ? new AppDebugController($uri, $pageName) : new AppController($uri, $pageName);
    }

    /**
     * Save the routes in a file.
     *
     * @return void
     */
    private function save(): void
    {
        $content = json_encode($this->routes);
        file_put_contents(self::ROUTE_LIST_FILE, $content);
    }
}
