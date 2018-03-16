<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\App;

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Base\PluginManager;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Dinamic\Lib\AssetManager;
use Twig_Environment;
use Twig_Extension_Debug;
use Twig_Function;
use Twig_Loader_Filesystem;

/**
 * Description of WebRender
 *
 * @author Carlos García Gómez
 */
class WebRender
{

    /**
     * Translation engine.
     *
     * @var Translator
     */
    private $i18n;

    /**
     * Loads template from the filesystem.
     *
     * @var Twig_Loader_Filesystem
     */
    private $loader;

    /**
     * App log manager.
     *
     * @var MiniLog
     */
    private $miniLog;

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
        if (!defined('FS_DEBUG')) {
            define('FS_DEBUG', true);
        }

        $this->i18n = new Translator();
        $path = FS_DEBUG ? FS_FOLDER . '/Core/View' : FS_FOLDER . '/Dinamic/View';
        $this->loader = new Twig_Loader_Filesystem($path);
        $this->miniLog = new MiniLog();
        $this->pluginManager = new PluginManager();
    }

    /**
     * Return Twig environment with default options for Twig.
     *
     * @return Twig_Environment
     */
    public function getTwig()
    {
        $twig = new Twig_Environment($this->loader, $this->getOptions());

        /// asset functions
        $assetFunction = new Twig_Function('asset', function ($string) {
            return FS_ROUTE . '/' . $string;
        });
        $twig->addFunction($assetFunction);

        /// assetCombine functions
        $assetCombineFunction = new Twig_Function('assetCombine', function ($fileList) {
            return AssetManager::combine($fileList);
        });
        $twig->addFunction($assetCombineFunction);

        /// debug extension
        $twig->addExtension(new Twig_Extension_Debug());

        return $twig;
    }

    /**
     * Add all paths from Core and Plugins folders.
     */
    public function loadPluginFolders()
    {
        /// Core namespace
        $this->loader->addPath(FS_FOLDER . '/Core/View', 'Core');

        foreach ($this->pluginManager->enabledPlugins() as $pluginName) {
            $pluginPath = FS_FOLDER . '/Plugins/' . $pluginName . '/View';
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
    public function render($template, $params)
    {
        $templateVars = [
            'i18n' => $this->i18n,
            'log' => $this->miniLog,
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
        return FS_DEBUG ? ['debug' => true] : ['cache' => FS_FOLDER . '/MyFiles/Cache/Twig'];
    }
}
