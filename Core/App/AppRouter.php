<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
    const ROUTE_LIST_FILE = \FS_FOLDER . DIRECTORY_SEPARATOR . 'MyFiles' . DIRECTORY_SEPARATOR . 'routes.json';

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
        if (false === \defined('FS_ROUTE')) {
            \define('FS_ROUTE', '');
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
     * Return the especific App controller for any kind of petition.
     *
     * @return App
     */
    public function getApp()
    {
        $uri = $this->getUri();
        if ('/api' === $uri || '/api/' === \substr($uri, 0, 5)) {
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

            if ('*' !== \substr($key, -1)) {
                continue;
            }

            if (0 === \strncmp($uri, $key, \strlen($key) - 1)) {
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
        $filePath = \FS_FOLDER . $uri;

        /// favicon.ico
        if ('/favicon.ico' == $uri) {
            $filePath = \FS_FOLDER . '/Dinamic/Assets/Images/favicon.ico';
            \header('Content-Type: ' . $this->getMime($filePath));
            \readfile($filePath);
            return true;
        }

        /// Not a file? Not a safe file?
        if (false === \is_file($filePath) || false === $this->isFileSafe($filePath)) {
            return false;
        }

        /// Allowed folder?
        $allowedFolders = ['node_modules', 'vendor', 'Dinamic', 'Core', 'Plugins', 'MyFiles/Public'];
        foreach ($allowedFolders as $folder) {
            if ('/' . $folder === \substr($uri, 0, 1 + \strlen($folder))) {
                \header('Content-Type: ' . $this->getMime($filePath));
                \readfile($filePath);
                return true;
            }
        }

        /// MyFiles and token?
        $token = \filter_input(INPUT_GET, 'myft');
        if ('/MyFiles/' === \substr($uri, 0, 9) && $token && MyFilesToken::validate(\substr($uri, 1), $token)) {
            \header('Content-Type: ' . $this->getMime($filePath));
            \readfile($filePath);
            return true;
        }

        return false;
    }

    /**
     * 
     * @param string $filePath
     *
     * @return bool
     */
    public static function isFileSafe(string $filePath): bool
    {
        $parts = \explode('.', $filePath);
        $safe = [
            'avi', 'css', 'csv', 'eot', 'gif', 'gz', 'ico', 'jpeg', 'jpg', 'js',
            'json', 'map', 'mkv', 'mp4', 'ogg', 'pdf', 'png', 'sql', 'svg',
            'ttf', 'webm', 'woff', 'woff2', 'xls', 'xlsx', 'zip'
        ];
        return \count($parts) > 1 ? \in_array(\end($parts), $safe, true) : true;
    }

    /**
     * Adds this route to the ap routes.
     *
     * @param string $newRoute
     * @param string $controllerName
     * @param string $optionalId
     */
    public function setRoute(string $newRoute, string $controllerName, string $optionalId = '')
    {
        if (!empty($optionalId)) {
            /// if optionaId, then remove previous items with that data
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
     * Deploy all dinamic files.
     */
    private function deploy()
    {
        if (false === \file_exists(\FS_FOLDER . \DIRECTORY_SEPARATOR . 'Dinamic')) {
            $pluginManager = new \FacturaScripts\Core\Base\PluginManager();
            $pluginManager->deploy();
        }
    }

    /**
     * Return the mime type from given file.
     *
     * @param string $filePath
     *
     * @return string
     */
    private function getMime(string $filePath)
    {
        if ('.css' === \substr($filePath, -4)) {
            return 'text/css';
        }

        if ('.js' === \substr($filePath, -3)) {
            return 'application/javascript';
        }

        return \mime_content_type($filePath);
    }

    /**
     * Return the uri from the request.
     *
     * @return bool|string
     */
    private function getUri()
    {
        $uri = \filter_input(\INPUT_SERVER, 'REQUEST_URI');
        $uri2 = \is_null($uri) ? \filter_var($_SERVER['REQUEST_URI'], \FILTER_SANITIZE_URL) : $uri;
        $uriArray = \explode('?', $uri2);

        return \substr($uriArray[0], \strlen(FS_ROUTE));
    }

    /**
     * Returns an array with the list of plugins in the plugin.list file.
     *
     * @return array
     */
    private function loadFromFile(): array
    {
        if (\file_exists(self::ROUTE_LIST_FILE)) {
            $content = \file_get_contents(self::ROUTE_LIST_FILE);
            if ($content !== false) {
                return \json_decode($content, true);
            }
        }

        return [];
    }

    /**
     * 
     * @param string $uri
     * @param string $pageName
     *
     * @return App
     */
    private function newAppController(string $uri, string $pageName = '')
    {
        return \FS_DEBUG ? new AppDebugController($uri, $pageName) : new AppController($uri, $pageName);
    }

    /**
     * Save the routes in a file.
     *
     * @return bool
     */
    private function save(): bool
    {
        $content = \json_encode($this->routes);
        return \file_put_contents(self::ROUTE_LIST_FILE, $content) !== false;
    }
}
