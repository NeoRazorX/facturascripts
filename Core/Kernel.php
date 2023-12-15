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

namespace FacturaScripts\Core;

use Closure;
use Exception;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Contract\ErrorControllerInterface;
use FacturaScripts\Core\Error\DefaultError;

final class Kernel
{
    /** @var array */
    private static $routes = [];

    /** @var Closure[] */
    private static $routesCallbacks = [];

    /** @var array */
    private static $timers = [];

    public static function addRoute(string $route, string $controller, int $position = 0, string $customId = ''): void
    {
        // si el customId ya existe, eliminamos la ruta anterior
        if (!empty($customId)) {
            foreach (self::$routes as $key => $value) {
                if ($value['customId'] === $customId) {
                    unset(self::$routes[$key]);
                }
            }
        }

        // añadimos la nueva ruta
        self::$routes[$route] = [
            'controller' => $controller,
            'customId' => $customId,
            'position' => $position,
        ];
    }

    public static function addRoutes(Closure $closure): void
    {
        self::$routesCallbacks[] = $closure;
    }

    public static function getExecutionTime(int $decimals = 5): float
    {
        $start = self::$timers['kernel::init']['start'] ?? microtime(true);
        $diff = microtime(true) - $start;
        return round($diff, $decimals);
    }

    public static function getTimer(string $name): float
    {
        if (!array_key_exists($name, self::$timers)) {
            return 0.0;
        }

        $start = self::$timers[$name]['start'];
        $stop = self::$timers[$name]['stop'] ?? microtime(true);
        return round($stop - $start, 5);
    }

    public static function getTimers(): array
    {
        return self::$timers;
    }

    public static function init(): void
    {
        self::startTimer('kernel::init');

        // cargamos algunas constantes para dar soporte a versiones antiguas
        $constants = [
            'FS_CODPAIS' => ['property' => 'codpais', 'default' => 'ESP'],
            'FS_CURRENCY_POS' => ['property' => 'currency_position', 'default' => 'right'],
            'FS_ITEM_LIMIT' => ['property' => 'item_limit', 'default' => 50],
            'FS_NF0' => ['property' => 'decimals', 'default' => 2],
            'FS_NF1' => ['property' => 'decimal_separator', 'default' => ','],
            'FS_NF2' => ['property' => 'thousands_separator', 'default' => ' '],
        ];
        foreach ($constants as $key => $value) {
            if (!defined($key)) {
                define($key, Tools::settings('default', $value['property'], $value['default']));
            }
        }

        // cargamos el idioma almacenado en la cookie o el predeterminado
        $lang = $_COOKIE['fsLang'] ?? Tools::config('lang', 'es_ES');
        Translator::setDefaultLang($lang);

        // inicializamos el antiguo traductor
        ToolBox::i18n()->setDefaultLang($lang);

        self::stopTimer('kernel::init');
    }

    public static function rebuildRoutes(): void
    {
        self::$routes = [];
        self::loadDefaultRoutes();

        // cargamos la página por defecto
        $homePage = Tools::settings('default', 'homepage', 'Dashboard');

        // recorremos toda la lista de archivos de la carpeta Dinamic/Controller
        $dir = Tools::folder('Dinamic', 'Controller');
        foreach (Tools::folderScan($dir) as $file) {
            // si no es un archivo php, lo ignoramos
            if ('.php' !== substr($file, -4)) {
                continue;
            }

            // añadimos la ruta
            $route = substr($file, 0, -4);
            $controller = '\\FacturaScripts\\Dinamic\\Controller\\' . $route;
            self::addRoute('/' . $route, $controller);

            // si la ruta coincide con homepage, la añadimos como raíz
            if ($route === $homePage) {
                self::addRoute('/', $controller);
            }
        }

        // ejecutamos los callbacks para añadir rutas
        foreach (self::$routesCallbacks as $callback) {
            $callback(self::$routes);
        }

        // ordenamos colocando primero las que tienen una posición menor
        uasort(self::$routes, function ($a, $b) {
            return $a['position'] <=> $b['position'];
        });
    }

    public static function run(string $url): void
    {
        Kernel::startTimer('kernel::run');

        $route = Tools::config('route', '');
        $relativeUrl = substr($url, strlen($route));

        try {
            self::loadRoutes();
            self::runController($relativeUrl);
        } catch (Exception $exception) {
            error_clear_last();

            $handler = self::getErrorHandler($exception);
            $handler->run();
        }

        Kernel::stopTimer('kernel::run');
    }

    public static function saveRoutes(): bool
    {
        $filePath = Tools::folder('MyFiles', 'routes.json');
        $content = json_encode(self::$routes, JSON_PRETTY_PRINT);
        return false === file_put_contents($filePath, $content);
    }

    public static function startTimer(string $name): void
    {
        self::$timers[$name] = ['start' => microtime(true)];
    }

    public static function stopTimer(string $name): float
    {
        if (!array_key_exists($name, self::$timers)) {
            self::startTimer($name);
        }

        self::$timers[$name]['stop'] = microtime(true);

        return round(self::$timers[$name]['stop'] - self::$timers[$name]['start'], 5);
    }

    public static function version(): float
    {
        return 2023.16;
    }

    private static function getErrorHandler(Exception $exception): ErrorControllerInterface
    {
        if ($exception instanceof KernelException) {
            $dynClass = '\\FacturaScripts\\Dinamic\\Error\\' . $exception->handler;
            if (class_exists($dynClass)) {
                return new $dynClass($exception);
            }

            $mainClass = '\\FacturaScripts\\Core\\Error\\' . $exception->handler;
            return new $mainClass($exception);
        }

        return new DefaultError($exception);
    }

    private static function loadDefaultRoutes(): void
    {
        // añadimos las rutas por defecto
        $routes = [
            '/' => 'Dashboard',
            '/AdminPlugins' => 'AdminPlugins',
            '/api' => 'ApiRoot',
            '/api/*' => 'ApiRoot',
            '/Core/Assets/*' => 'Files',
            '/cron' => 'Cron',
            '/deploy' => 'Deploy',
            '/Dinamic/Assets/*' => 'Files',
            '/login' => 'Login',
            '/MyFiles/*' => 'Myfiles',
            '/node_modules/*' => 'Files',
            '/Plugins/*' => 'Files',
            '/Updater' => 'Updater',
        ];

        foreach ($routes as $route => $controller) {
            if (class_exists('\\FacturaScripts\\Dinamic\\Controller\\' . $controller)) {
                self::addRoute($route, '\\FacturaScripts\\Dinamic\\Controller\\' . $controller, 1);
                continue;
            }

            self::addRoute($route, '\\FacturaScripts\\Core\\Controller\\' . $controller, 1);
        }
    }

    private static function loadRoutes(): void
    {
        if ('' === Tools::config('db_name', '')) {
            self::addRoute('/', '\\FacturaScripts\\Core\\Controller\\Installer', 1);
            self::addRoute('/Core/Assets/*', '\\FacturaScripts\\Core\\Controller\\Files', 1);
            self::addRoute('/node_modules/*', '\\FacturaScripts\\Core\\Controller\\Files', 1);
            return;
        }

        self::loadDefaultRoutes();

        // añadimos las rutas del archivo MyFiles/routes.json file
        $routesFile = Tools::folder('MyFiles', 'routes.json');
        if (false === file_exists($routesFile)) {
            return;
        }

        $routes = json_decode(file_get_contents($routesFile), true);
        if (false === is_array($routes)) {
            return;
        }

        foreach ($routes as $route => $params) {
            self::addRoute($route, $params['controller'], $params['position'] ?? 0, $params['customId'] ?? '');
        }

        // ordenamos colocando primero las que tienen una posición menor
        uasort(self::$routes, function ($a, $b) {
            return $a['position'] <=> $b['position'];
        });
    }

    private static function runController(string $url): void
    {
        foreach (self::$routes as $route => $info) {
            $controller = $info['controller'];
            $class = explode('\\', $controller);

            // si la ruta no tiene namespace, lo añadimos
            if (count($class) === 1) {
                $controller = '\\FacturaScripts\\Dinamic\\Controller\\' . $controller;
                $class = explode('\\', $controller);
            }

            $name = end($class);

            // coincidencia exacta
            if ($url === $route) {
                $app = new $controller($name, $url);
                $app->run();
                return;
            }

            // coincidencia con comodín
            if (str_ends_with($route, '*') && 0 === strncmp($url, $route, strlen($route) - 1)) {
                $app = new $controller($name, $url);
                $app->run();
                return;
            }
        }

        throw new KernelException('PageNotFound', $url);
    }
}
