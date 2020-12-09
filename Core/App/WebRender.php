<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\Utils;
use Twig\Environment;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;

/**
 * Description of WebRender
 *
 * @author Carlos García Gómez
 */
final class WebRender
{

    /**
     * FALSE if FacturaScripts is not installed already.
     *
     * @var bool
     */
    private $installed;

    /**
     * Loads template from the filesystem.
     *
     * @var FilesystemLoader
     */
    private $loader;

    /**
     * Plugin manager.
     *
     * @var PluginManager
     */
    private $pluginManager;

    /**
     * WebRender constructor.
     */
    public function __construct()
    {
        $this->installed = true;
        if (!defined('FS_DEBUG')) {
            define('FS_DEBUG', true);
            $this->installed = false;
        }

        $path = FS_DEBUG ? \FS_FOLDER . '/Core/View' : \FS_FOLDER . '/Dinamic/View';
        $this->loader = new FilesystemLoader($path);
        $this->pluginManager = new PluginManager();
    }

    /**
     * Return Twig environment with default options for Twig.
     *
     * @return Environment
     */
    public function getTwig()
    {
        $twig = new Environment($this->loader, $this->getOptions());

        /// asset functions
        $assetFunction = new TwigFunction('asset', function ($string) {
            $path = \FS_ROUTE . '/';
            if (substr($string, 0, strlen($path)) == $path) {
                return $string;
            }
            return str_replace('//', '/', $path . $string);
        });
        $twig->addFunction($assetFunction);

        /// fixHtml functions
        $fixHtmlFunction = new TwigFilter('fixHtml', function ($string) {
            return Utils::fixHtml($string);
        });
        $twig->addFilter($fixHtmlFunction);

        /// debug extension
        $twig->addExtension(new DebugExtension());

        return $twig;
    }

    /**
     * Add all paths from Core and Plugins folders.
     */
    public function loadPluginFolders()
    {
        /// Core namespace
        $this->loader->addPath(\FS_FOLDER . '/Core/View', 'Core');

        foreach ($this->pluginManager->enabledPlugins() as $pluginName) {
            $pluginPath = \FS_FOLDER . '/Plugins/' . $pluginName . '/View';
            if (!file_exists($pluginPath)) {
                continue;
            }

            /// plugin namespace
            $this->loader->addPath($pluginPath, 'Plugin' . $pluginName);
            if (FS_DEBUG) {
                $this->loader->prependPath($pluginPath);
            }
        }
    }

    /**
     * Returns the data into the standard output.
     *
     * @param string $template
     * @param array  $params
     *
     * @return string
     */
    public function render($template, $params = [])
    {
        $templateVars = [
            'i18n' => new Translator(),
            'log' => new MiniLog(),
        ];
        foreach ($params as $key => $value) {
            $templateVars[$key] = $value;
        }

        $twig = $this->getTwig();
        return $twig->render($template, $templateVars);
    }

    /**
     * Return default options for Twig.
     *
     * @return array
     */
    private function getOptions()
    {
        if ($this->installed) {
            return [
                'debug' => FS_DEBUG,
                'cache' => FS_FOLDER . '/MyFiles/Cache/Twig',
                'auto_reload' => true
            ];
        }

        return ['debug' => FS_DEBUG];
    }
}
