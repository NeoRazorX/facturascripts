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

use Exception;

final class Kernel
{
    /** @var array */
    private static $routes = [];

    public static function addRoute(string $route, string $controller, int $position = 0): void
    {
        self::$routes[$route] = [
            'controller' => $controller,
            'position' => $position,
        ];
    }

    public static function init(): void
    {
        // cargamos algunas constantes para dar soporte a versiones antiguas
        $constants = [
            'FS_CODPAIS' => ['property' => 'codpais', 'default' => 'ESP'],
            'FS_NF0' => ['property' => 'decimals', 'default' => 2],
            'FS_NF1' => ['property' => 'decimal_separator', 'default' => ','],
            'FS_NF2' => ['property' => 'thousands_separator', 'default' => ' '],
            'FS_CURRENCY_POS' => ['property' => 'currency_position', 'default' => 'right'],
            'FS_ITEM_LIMIT' => ['property' => 'item_limit', 'default' => 50],
        ];
        foreach ($constants as $key => $value) {
            if (!defined($key)) {
                define($key, Tools::settings('default', $value['property'], $value['default']));
            }
        }
    }

    public static function run(string $url): void
    {
        // cargamos el idioma almacenado en la cookie o el predeterminado
        $lang = $_COOKIE['fsLang'] ?? Tools::config('lang', 'es_ES');
        Tools::lang()->setDefaultLang($lang);

        $route = Tools::config('route', '');
        $relativeUrl = substr($url, strlen($route));

        try {
            self::loadRoutes();
            self::runController($relativeUrl);
        } catch (KernelException $exception) {
            error_clear_last();
            $handler = $exception->getHandler($relativeUrl);
            $handler->run();
        } catch (Exception $exception) {
            error_clear_last();
            $exception = new KernelException('DefaultError', $exception->getMessage(), $exception->getCode());
            $handler = $exception->getHandler($relativeUrl);
            $handler->run();
        }
    }

    public static function saveRoutes(): bool
    {
        $filePath = Tools::folder('MyFiles', 'routes.json');
        $content = json_encode(self::$routes, JSON_PRETTY_PRINT);
        return false === file_put_contents($filePath, $content);
    }

    public static function shutdown(): void
    {
        $error = error_get_last();
        if (isset($error)) {
            // limpiamos el buffer si es necesario
            if (ob_get_length() > 0) {
                ob_end_clean();
            }

            http_response_code(500);

            // comprobamos si el content-type es json
            if (isset($_SERVER['CONTENT_TYPE']) && 'application/json' === $_SERVER['CONTENT_TYPE']) {
                header('Content-Type: application/json');
                echo json_encode(['error' => $error['message']]);
                return;
            }

            echo '<h1 style="margin: 50px auto 5px auto">Error ' . $error['type'] . '</h1>';
            echo '<p style="margin: 0 auto 0 auto">' . nl2br($error['message']) . '</p>';
            echo '<p style="margin: 0 auto 0 auto">File: ' . $error['file'] . '</p>';
            echo '<p style="margin: 0 auto 0 auto">Line: ' . $error['line'] . '</p>';
        }
    }

    public static function version(): float
    {
        return 2023.02;
    }

    private static function loadRoutes(): void
    {
        self::addRoute('/', '\\FacturaScripts\\Core\\Controller\\Dashboard');
        self::addRoute('/AdminPlugins', '\\FacturaScripts\\Core\\Controller\\AdminPlugins');
        self::addRoute('/api/*', '\\FacturaScripts\\Core\\Controller\\ApiRoot');
        self::addRoute('/deploy', '\\FacturaScripts\\Core\\Controller\\Deploy');
        self::addRoute('/Dinamic/*', '\\FacturaScripts\\Core\\Controller\\Files');
        self::addRoute('/install', '\\FacturaScripts\\Core\\Controller\\Installer');
        self::addRoute('/login', '\\FacturaScripts\\Core\\Controller\\Login');
        self::addRoute('/MyFiles/*', '\\FacturaScripts\\Core\\Controller\\Myfiles');
        self::addRoute('/node_modules/*', '\\FacturaScripts\\Core\\Controller\\Files');
        self::addRoute('/Plugins/*', '\\FacturaScripts\\Core\\Controller\\Files');

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
            self::addRoute($route, $params['controller']);
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
