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

namespace FacturaScripts\Core;

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Lib\MyFilesToken;
use FacturaScripts\Core\DataSrc\Divisas;
use FacturaScripts\Core\Lib\AssetManager;
use FacturaScripts\Core\Lib\MultiRequestProtection;
use FacturaScripts\Core\Model\AttachedFile;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

/**
 * Una clase para renderizar plantillas HTML con Twig.
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
final class Html
{
    const HTML_CHARS = ['<', '>', '"', "'"];
    const HTML_REPLACEMENTS = ['&lt;', '&gt;', '&quot;', '&#39;'];

    /** @var array */
    private static $functions = [];

    /** @var FilesystemLoader */
    private static $loader;

    /** @var array */
    private static $paths = [];

    /** @var bool */
    private static $plugins = true;

    /** @var Environment */
    private static $twig;

    public static function addFunction(TwigFunction $function): void
    {
        self::$functions[] = $function;
    }

    public static function addPath(string $name, string $path): void
    {
        self::$paths[$name] = $path;
    }

    public static function disablePlugins(bool $disable = true): void
    {
        self::$plugins = !$disable;
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public static function render(string $template, array $params = []): string
    {
        $templateVars = [
            'assetManager' => new AssetManager(),
            'debugBarRender' => Tools::config('debug') ? new DebugBar() : false,
            'i18n' => new Translator(),
            'log' => new MiniLog()
        ];
        return self::twig()->render($template, array_merge($params, $templateVars));
    }

    private static function assetFunction(): TwigFunction
    {
        return new TwigFunction('asset', function ($string) {
            if (null === $string) {
                return '';
            }

            $path = FS_ROUTE . '/';
            return substr($string, 0, strlen($path)) == $path ?
                $string :
                str_replace('//', '/', $path . $string);
        });
    }

    private static function attachedFileFunction(): TwigFunction
    {
        return new TwigFunction('attachedFile', function ($id) {
            $attached = new AttachedFile();
            $attached->loadFromCode($id);
            return $attached;
        });
    }

    private static function cacheFunction(): TwigFunction
    {
        return new TwigFunction('cache', function (string $key) {
            return Cache::get($key);
        });
    }

    private static function configFunction(): TwigFunction
    {
        return new TwigFunction('config', function (string $key, $default = null) {
            $constants = [$key, strtoupper($key), 'FS_' . strtoupper($key)];
            foreach ($constants as $constant) {
                if (defined($constant)) {
                    return constant($constant);
                }
            }

            return $default;
        });
    }

    private static function executionTimeFunction(): TwigFunction
    {
        return new TwigFunction('executionTime', function () {
            return Kernel::getExecutionTime();
        });
    }

    private static function fixHtmlFunction(): TwigFunction
    {
        return new TwigFunction(
            'fixHtml',
            function ($txt) {
                return $txt === null ?
                    null :
                    str_replace(self::HTML_REPLACEMENTS, self::HTML_CHARS, $txt);
            },
            [
                'is_safe' => ['html'],
                'is_safe_callback' => ['html']
            ]
        );
    }

    private static function formTokenFunction(): TwigFunction
    {
        return new TwigFunction(
            'formToken',
            function (bool $input = true) {
                $tokenClass = new MultiRequestProtection();
                return $input ?
                    '<input type="hidden" name="multireqtoken" value="' . $tokenClass->newToken() . '"/>' :
                    $tokenClass->newToken();
            },
            [
                'is_safe' => ['html'],
                'is_safe_callback' => ['html']
            ]
        );
    }

    private static function getIncludeViews(): TwigFunction
    {
        return new TwigFunction('getIncludeViews', function (string $fileParent, string $position) {
            $files = [];
            $fileParentTemp = explode('/', $fileParent);
            $fileParent = str_replace('.html.twig', '', end($fileParentTemp));

            foreach (Plugins::enabled() as $pluginName) {
                $path = FS_FOLDER . '/Plugins/' . $pluginName . '/Extension/View/';
                if (false === file_exists($path)) {
                    continue;
                }

                $ficheros = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
                foreach ($ficheros as $f) {
                    if ($f->isDir()) {
                        continue;
                    }

                    $file = explode('_', str_replace('.html.twig', '', $f->getFilename()));
                    if (count($file) <= 1) {
                        continue;
                    }

                    // comprobamos que el archivo empiece por el nombre del fichero que se está incluyendo
                    if ($file[0] !== $fileParent) {
                        continue;
                    }

                    // comprobamos que la posición del archivo sea la solicitada
                    if ($file[1] !== $position) {
                        continue;
                    }

                    $arrayFile = [
                        'path' => '@PluginExtension' . $pluginName . '/' . $f->getFilename(),
                        'file' => $file[0],
                        'position' => $file[1]
                    ];

                    if (false === isset($file[2])) {
                        $file[2] = '10';
                    }

                    $arrayFile['order'] = str_pad($file[2], 5, "0", STR_PAD_LEFT);
                    $files[] = $arrayFile;
                }
            }
            if (empty($files)) {
                return $files;
            }

            usort($files, function ($a, $b) {
                return strcmp($a['file'], $b['file']) // status ascending
                    ?: strcmp($a['position'], $b['position']) // start ascending
                        ?: strcmp($a['order'], $b['order']) // mh ascending
                    ;
            });

            return $files;
        });
    }

    /**
     * @throws LoaderError
     */
    private static function loadPluginFolders(): void
    {
        // Core namespace
        self::$loader->addPath(FS_FOLDER . '/Core/View', 'Core');

        // Plugin namespace
        foreach (Plugins::enabled() as $pluginName) {
            $pluginPath = FS_FOLDER . '/Plugins/' . $pluginName . '/View';
            if (file_exists($pluginPath)) {
                self::$loader->addPath($pluginPath, 'Plugin' . $pluginName);
                if (FS_DEBUG) {
                    self::$loader->prependPath($pluginPath);
                }
            }

            $pluginExtensionPath = FS_FOLDER . '/Plugins/' . $pluginName . '/Extension/View';
            if (file_exists($pluginExtensionPath)) {
                self::$loader->addPath($pluginExtensionPath, 'PluginExtension' . $pluginName);
                if (FS_DEBUG) {
                    self::$loader->prependPath($pluginExtensionPath);
                }
            }
        }
    }

    private static function moneyFunction(): TwigFunction
    {
        return new TwigFunction('money', function (?float $number, string $coddivisa = '') {
            if (empty($coddivisa)) {
                $coddivisa = Tools::settings('default', 'coddivisa');
            }

            // cargamos la configuración de divisas
            $symbol = Divisas::get($coddivisa)->simbolo;
            $decimals = Tools::settings('default', 'decimals');
            $decimalSeparator = Tools::settings('default', 'decimal_separator');
            $thousandsSeparator = Tools::settings('default', 'thousands_separator');
            $currencyPosition = Tools::settings('default', 'currency_position');

            return $currencyPosition === 'right' ?
                number_format($number, $decimals, $decimalSeparator, $thousandsSeparator) . ' ' . $symbol :
                $symbol . ' ' . number_format($number, $decimals, $decimalSeparator, $thousandsSeparator);
        });
    }

    private static function myFilesUrlFunction(): TwigFunction
    {
        return new TwigFunction('myFilesUrl', function (string $path, bool $permanent = false, string $expiration = '') {
            return $path . '?myft=' . MyFilesToken::get($path, $permanent, $expiration);
        });
    }

    private static function numberFunction(): TwigFunction
    {
        return new TwigFunction('number', function (?float $number, ?int $decimals = null) {
            if ($decimals === null) {
                $decimals = Tools::settings('default', 'decimals');
            }

            // cargamos la configuración
            $decimalSeparator = Tools::settings('default', 'decimal_separator');
            $thousandsSeparator = Tools::settings('default', 'thousands_separator');

            return number_format($number, $decimals, $decimalSeparator, $thousandsSeparator);
        });
    }

    private static function settingsFunction(): TwigFunction
    {
        return new TwigFunction('settings', function (string $group, string $property, $default = null) {
            return Tools::settings($group, $property, $default);
        });
    }

    private static function transFunction(): TwigFunction
    {
        return new TwigFunction('trans', function (string $txt, array $parameters = [], string $langCode = '') {
            $trans = new Translator();
            return empty($langCode) ?
                $trans->trans($txt, $parameters) :
                $trans->customTrans($langCode, $txt, $parameters);
        });
    }

    private static function bytesFunction(): TwigFunction
    {
        return new TwigFunction('bytes', function ($size, int $decimals = 2) {
            return Tools::bytes($size, $decimals);
        });
    }

    /**
     * @throws LoaderError
     */
    private static function twig(): Environment
    {
        if (false === defined('FS_DEBUG')) {
            define('FS_DEBUG', true);
        }

        // cargamos las rutas para las plantillas
        $path = FS_DEBUG ? FS_FOLDER . '/Core/View' : FS_FOLDER . '/Dinamic/View';
        self::$loader = new FilesystemLoader($path);
        if (self::$plugins) {
            self::loadPluginFolders();
        }
        foreach (self::$paths as $name => $customPath) {
            self::$loader->addPath($customPath, $name);
            if (FS_DEBUG) {
                self::$loader->prependPath($customPath);
            }
        }

        // cargamos las opciones de twig
        $options = ['debug' => FS_DEBUG];
        if (self::$plugins) {
            $options['cache'] = FS_FOLDER . '/MyFiles/Cache/Twig';
            $options['auto_reload'] = true;
        }
        self::$twig = new Environment(self::$loader, $options);

        if (FS_DEBUG) {
            self::$twig->addExtension(new DebugExtension());
        }

        // cargamos las funciones de twig
        self::$twig->addFunction(self::assetFunction());
        self::$twig->addFunction(self::attachedFileFunction());
        self::$twig->addFunction(self::cacheFunction());
        self::$twig->addFunction(self::configFunction());
        self::$twig->addFunction(self::executionTimeFunction());
        self::$twig->addFunction(self::fixHtmlFunction());
        self::$twig->addFunction(self::formTokenFunction());
        self::$twig->addFunction(self::getIncludeViews());
        self::$twig->addFunction(self::moneyFunction());
        self::$twig->addFunction(self::myFilesUrlFunction());
        self::$twig->addFunction(self::numberFunction());
        self::$twig->addFunction(self::settingsFunction());
        self::$twig->addFunction(self::transFunction());
        self::$twig->addFunction(self::bytesFunction());
        foreach (self::$functions as $function) {
            self::$twig->addFunction($function);
        }

        return self::$twig;
    }
}
