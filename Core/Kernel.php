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

    public static function getErrorInfo(int $code, string $message, string $file, int $line): array
    {
        // calculamos un hash para el error, de forma que en la web podamos dar respuesta automáticamente
        $errorUrl = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
        $errorMessage = self::cleanErrorMessage($message);
        $errorFile = str_replace(FS_FOLDER, '', $file);
        $errorHash = md5($code . $errorFile . $line . $errorMessage);
        $reportUrl = 'https://facturascripts.com/errores/' . $errorHash;
        $reportQr = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($reportUrl);

        return [
            'code' => $code,
            'message' => $errorMessage,
            'file' => $errorFile,
            'line' => $line,
            'hash' => $errorHash,
            'url' => $errorUrl,
            'report_url' => $reportUrl,
            'report_qr' => $reportQr,
            'core_version' => self::version(),
            'php_version' => phpversion(),
            'os' => PHP_OS,
            'plugin_list' => implode(',', Plugins::enabled()),
        ];
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

    public static function shutdown(): void
    {
        $error = error_get_last();
        if (!isset($error)) {
            return;
        }

        // limpiamos el buffer si es necesario
        if (ob_get_length() > 0) {
            ob_end_clean();
        }

        http_response_code(500);

        $info = self::getErrorInfo($error['type'], $error['message'], $error['file'], $error['line']);

        // comprobamos si el content-type es json
        if (isset($_SERVER['CONTENT_TYPE']) && 'application/json' === $_SERVER['CONTENT_TYPE']) {
            header('Content-Type: application/json');
            echo json_encode(['error' => $error['message'], 'info' => $info]);
            return;
        }

        // comprobamos si el content-type es text/plain
        if (isset($_SERVER['CONTENT_TYPE']) && 'text/plain' === $_SERVER['CONTENT_TYPE']) {
            header('Content-Type: text/plain');
            echo $error['message'];
            return;
        }

        echo '<!doctype html>'
            . '<html lang="en">'
            . '<head>'
            . '<meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>Fatal error #' . $info['code'] . '</title>'
            . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"'
            . ' integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">'
            . '</head>'
            . '<body class="bg-danger">'
            . '<div class="container mt-5 mb-5">'
            . '<div class="row justify-content-center">'
            . '<div class="col-sm-6">'
            . '<div class="card shadow">'
            . '<div class="card-body">'
            . '<img src="' . $info['report_qr'] . '" alt="' . $info['hash'] . '" class="float-end">'
            . '<h1 class="mt-0">Fatal error #' . $info['code'] . '</h1>'
            . '<p>' . nl2br($info['message']) . '</p>'
            . '<p class="mb-0">Url: ' . $info['url'] . '</p>';

        if (Tools::config('debug', false)) {
            echo '<p class="mb-0">File: ' . $info['file'] . ', line: ' . $info['line'] . '</p>';
        }

        echo '<p class="mb-0">Hash: ' . $info['hash'] . '</p>';

        if (Tools::config('debug', false)) {
            echo '<p class="mb-0">Core: ' . $info['core_version'] . ', plugins: ' . $info['plugin_list'] . '</p>'
                . '<p class="mb-0">PHP: ' . $info['php_version'] . ', OS: ' . $info['os'] . '</p>';
        }

        echo '</div>'
            . '<div class="card-footer">'
            . '<form method="post" action="' . $info['report_url'] . '" target="_blank">'
            . '<input type="hidden" name="error_code" value="' . $info['code'] . '">'
            . '<input type="hidden" name="error_message" value="' . $info['message'] . '">'
            . '<input type="hidden" name="error_file" value="' . $info['file'] . '">'
            . '<input type="hidden" name="error_line" value="' . $info['line'] . '">'
            . '<input type="hidden" name="error_hash" value="' . $info['hash'] . '">'
            . '<input type="hidden" name="error_url" value="' . $info['url'] . '">'
            . '<input type="hidden" name="error_core_version" value="' . $info['core_version'] . '">'
            . '<input type="hidden" name="error_plugin_list" value="' . $info['plugin_list'] . '">'
            . '<input type="hidden" name="error_php_version" value="' . $info['php_version'] . '">'
            . '<input type="hidden" name="error_os" value="' . $info['os'] . '">'
            . '<button type="submit" class="btn btn-secondary">Read more / Leer más</button>'
            . '</form>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</body>'
            . '</html>';
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
        return 2023.15;
    }

    private static function cleanErrorMessage(string $message): string
    {
        return str_replace([FS_FOLDER, 'Stack trace:'], ['', "\nStack trace:"], $message);
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
