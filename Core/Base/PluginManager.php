<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Base;

use Exception;

/**
 * FacturaScripts plugins manager.
 *
 * @package FacturaScripts\Core\Base
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class PluginManager
{

    /**
     * Prevents infinite loops by deploying plugins.
     *
     * @var bool
     */
    private static $deployedControllers;

    /**
     * List of active plugins.
     *
     * @var array
     */
    private static $enabledPlugins;

    /**
     * System translator.
     *
     * @var Translator
     */
    private static $i18n;

    /**
     * Manage the log of the entire application.
     *
     * @var MiniLog
     */
    private static $minilog;

    /**
     * Path of the plugin.list file
     *
     * @var string
     */
    private static $pluginListFile;

    /**
     * PluginManager constructor.
     */
    public function __construct()
    {
        if (self::$pluginListFile === null) {
            self::$deployedControllers = false;
            self::$i18n = new Translator();
            self::$minilog = new MiniLog();
            self::$pluginListFile = FS_FOLDER . '/plugin.list';
            self::$enabledPlugins = $this->loadFromFile();
        }
    }

    /**
     * Returns an array with the list of plugins in the plugin.list file.
     *
     * @return array
     */
    private function loadFromFile()
    {
        if (file_exists(self::$pluginListFile)) {
            return explode(',', file_get_contents(self::$pluginListFile));
        }

        return [];
    }

    /**
     * Save the list of plugins in a file.
     */
    private function save()
    {
        file_put_contents(self::$pluginListFile, implode(',', self::$enabledPlugins));
    }

    /**
     * Returns the list of active plugins.
     *
     * @return array
     */
    public function enabledPlugins()
    {
        return self::$enabledPlugins;
    }

    /**
     * Activate the indicated plugin.
     *
     * @param string $pluginName
     */
    public function enable($pluginName)
    {
        if (file_exists(FS_FOLDER . '/plugins/' . $pluginName)) {
            self::$enabledPlugins[] = $pluginName;
            $this->save();
        }
    }

    /**
     * Disable the indicated plugin.
     *
     * @param string $pluginName
     */
    public function disable($pluginName)
    {
        foreach (self::$enabledPlugins as $i => $value) {
            if ($value === $pluginName) {
                unset(self::$enabledPlugins[$i]);
                $this->save();
                break;
            }
        }
    }

    /**
     * Display all the necessary files in the Dinamic folder to be able to use plugins
     * and plugin models with the autoloader, but following the priority system of FacturaScripts.
     *
     * @param bool $clean
     */
    public function deploy($clean = true)
    {
        $folders = ['Assets', 'Controller', 'Model', 'Lib', 'Table', 'View', 'XMLView'];
        foreach ($folders as $folder) {
            if ($clean) {
                $this->cleanFolder(FS_FOLDER . '/Dinamic/' . $folder);
            }

            $this->createFolder(FS_FOLDER . '/Dinamic/' . $folder);

            /// we examine the plugins
            foreach (self::$enabledPlugins as $pluginName) {
                if (file_exists(FS_FOLDER . '/Plugins/' . $pluginName . '/' . $folder)) {
                    $this->linkFiles($folder, 'Plugins', $pluginName);
                }
            }

            /// we examine the core
            if (file_exists(FS_FOLDER . '/Core/' . $folder)) {
                $this->linkFiles($folder);
            }
        }

        if (self::$deployedControllers === false) {
            /// finally we started the drivers to complete the menu
            $this->deployControllers();
        }
    }

    /**
     * Prepare the controllers dynamically
     */
    private function deployControllers()
    {
        self::$deployedControllers = true;
        $cache = new Cache();
        $menuManager = new MenuManager();
        $menuManager->init();

        foreach (scandir(FS_FOLDER . '/Dinamic/Controller', SCANDIR_SORT_ASCENDING) as $fileName) {
            if ($fileName !== '.' && $fileName !== '..' && substr($fileName, -3) === 'php') {
                $controllerName = substr($fileName, 0, -4);
                $controllerNamespace = 'FacturaScripts\\Dinamic\\Controller\\' . $controllerName;

                if (!class_exists($controllerNamespace)) {
                    /// we force the loading of the file because at this point the autoloader will not find it
                    require FS_FOLDER . '/Dinamic/Controller/' . $controllerName . '.php';
                }

                try {
                    $controller = new $controllerNamespace($cache, self::$i18n, self::$minilog, $controllerName);
                    $menuManager->selectPage($controller->getPageData());
                } catch (Exception $exc) {
                    self::$minilog->critical(self::$i18n->trans('cant-load-controller', [$controllerName]));
                }
            }
        }
    }

    /**
     * Delete the $folder and its files.
     *
     * @param string $folder
     *
     * @return bool
     */
    private function cleanFolder($folder)
    {
        $done = true;

        if (file_exists($folder)) {
            /// Comprobamos los archivos que no son '.' ni '..'
            $items = array_diff(scandir($folder, SCANDIR_SORT_ASCENDING), ['.', '..']);

            /// Ahora recorremos y eliminamos lo que encontramos
            foreach ($items as $item) {
                if (is_dir($folder . '/' . $item)) {
                    $done = $this->cleanFolder($folder . '/' . $item . '/');
                } else {
                    $done = unlink($folder . '/' . $item);
                }
            }
        }

        return $done;
    }

    /**
     * Create the folder.
     *
     * @param string $folder
     *
     * @return bool
     */
    private function createFolder($folder)
    {
        if (!file_exists($folder) && !@mkdir($folder, 0775, true)) {
            self::$minilog->critical(self::$i18n->trans('cant-create-folder', [$folder]));

            return false;
        }

        return true;
    }

    /**
     * Link the files
     *
     * @param string $folder
     * @param string $place
     * @param string $pluginName
     */
    private function linkFiles($folder, $place = 'Core', $pluginName = '')
    {
        if (empty($pluginName)) {
            $path = FS_FOLDER . '/' . $place . '/' . $folder;
            $namespace = "\FacturaScripts\Core\\";
        } else {
            $path = FS_FOLDER . '/Plugins/' . $pluginName . '/' . $folder;
            $namespace = "\FacturaScripts\Plugins\\" . $pluginName . '\\';
        }

        // We add the files that are not '.' neither '..'
        $filesPath = array_diff(scandir($path, SCANDIR_SORT_ASCENDING), ['.', '..']);
        // Now we go through only files or folders
        foreach ($filesPath as $fileName) {
            $infoFile = pathinfo($fileName);
            if (is_file($path . '/' . $fileName) && $infoFile['filename'] !== '') {
                if ($infoFile['extension'] === 'php') {
                    $this->linkClassFile($fileName, $folder, $namespace);
                } elseif ($infoFile['extension'] === 'xml') {
                    $filePath = FS_FOLDER . '/' . $place . '/' . $folder . '/' . $fileName;
                    $this->linkXmlFile($fileName, $folder, $filePath);
                }
            }
        }
    }

    /**
     * Link classes dynamically.
     *
     * @param string $fileName
     * @param string $folder
     * @param string $namespace
     */
    private function linkClassFile($fileName, $folder, $namespace = "\FacturaScripts\Core\\")
    {
        if (!file_exists(FS_FOLDER . '/Dinamic/' . $folder . '/' . $fileName)) {
            $className = substr($fileName, 0, -4);
            $txt = '<?php namespace FacturaScripts\Dinamic\\' . $folder . ";\n\n"
                . '/**' . "\n"
                . ' * Clase cargada dinámicamente' . "\n"
                . ' * @package FacturaScripts\\Dinamic\\Controller' . "\n"
                . ' * @author Carlos García Gómez <carlos@facturascripts.com>' . "\n"
                . ' */' . "\n"
                . 'class ' . $className . ' extends ' . $namespace . $folder . '\\' . $className . "\n{\n}\n";

            file_put_contents(FS_FOLDER . '/Dinamic/' . $folder . '/' . $fileName, $txt);
        }
    }

    /**
     * Link the XML dynamically.
     *
     * @param string $fileName
     * @param string $folder
     * @param string $filePath
     */
    private function linkXmlFile($fileName, $folder, $filePath)
    {
        if (!file_exists(FS_FOLDER . '/Dinamic/' . $folder . '/' . $fileName)) {
            copy($filePath, FS_FOLDER . '/Dinamic/' . $folder . '/' . $fileName);
        }
    }
}
