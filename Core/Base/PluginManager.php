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
     * Path of the plugin.list file.
     *
     * @var string
     */
    private static $pluginListFile;

    /**
     * Path for plugins.
     *
     * @var string
     */
    private $pluginPath;

    /**
     * PluginManager constructor.
     */
    public function __construct()
    {
        $this->pluginPath = FS_FOLDER . DIRECTORY_SEPARATOR . 'Plugins' . DIRECTORY_SEPARATOR;
        if (self::$pluginListFile === null) {
            self::$deployedControllers = false;
            self::$i18n = new Translator();
            self::$minilog = new MiniLog();
            self::$pluginListFile = FS_FOLDER . DIRECTORY_SEPARATOR . 'plugin.list';
            self::$enabledPlugins = $this->loadFromFile();
        }
    }

    /**
     * Returns the plugin path folder.
     *
     * @return string
     */
    public function getPluginPath()
    {
        return $this->pluginPath;
    }

    /**
     * Returns an array with the list of plugins in the plugin.list file.
     *
     * @return array
     */
    private function loadFromFile()
    {
        if (file_exists(self::$pluginListFile)) {
            $list = explode(',', trim(file_get_contents(self::$pluginListFile)));
            if (count($list) === 1 && empty($list[0])) {
                return [];
            }
            return $list;
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
     * Returns the list of installed plugins.
     *
     * @return array
     */
    public function installedPlugins()
    {
        return array_diff(scandir($this->getPluginPath(), SCANDIR_SORT_ASCENDING), ['.', '..']);
    }

    /**
     * Activate the indicated plugin.
     *
     * @param string $pluginName
     */
    public function enable($pluginName)
    {
        if (file_exists($this->pluginPath . $pluginName)) {
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
                $this->cleanFolder(FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . $folder);
            }

            $this->createFolder(FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . $folder);

            /// examine the plugins
            foreach (self::$enabledPlugins as $pluginName) {
                if (file_exists($this->pluginPath . $pluginName . DIRECTORY_SEPARATOR . $folder)) {
                    $this->linkFiles($folder, 'Plugins', $pluginName);
                }
            }

            /// examine the core
            if (file_exists(FS_FOLDER . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . $folder)) {
                $this->linkFiles($folder);
            }
        }

        if (self::$deployedControllers === false) {
            /// finally we started the drivers to complete the menu
            $this->initControllers();
        }
    }

    /**
     * Initialize the controllers dynamically.
     */
    private function initControllers()
    {
        self::$deployedControllers = true;
        $cache = new Cache();
        $menuManager = new MenuManager();
        $menuManager->init();

        $files = array_diff(scandir(FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . 'Controller', SCANDIR_SORT_ASCENDING), ['.', '..']);
        foreach ($files as $fileName) {
            if (substr($fileName, -3) === 'php') {
                $controllerName = substr($fileName, 0, -4);
                $controllerNamespace = 'FacturaScripts\\Dinamic\\Controller\\' . $controllerName;

                if (!class_exists($controllerNamespace)) {
                    /// we force the loading of the file because at this point the autoloader will not find it
                    require FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . $controllerName . '.php';
                }

                try {
                    $controller = new $controllerNamespace($cache, self::$i18n, self::$minilog, $controllerName);
                    $menuManager->selectPage($controller->getPageData());
                } catch (Exception $exc) {
                    self::$minilog->critical(self::$i18n->trans('cant-load-controller', ['%controllerName%' => $controllerName]));
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
                if (is_dir($folder . DIRECTORY_SEPARATOR . $item)) {
                    $done = $this->cleanFolder($folder . DIRECTORY_SEPARATOR . $item . DIRECTORY_SEPARATOR);
                } else {
                    $done = unlink($folder . DIRECTORY_SEPARATOR . $item);
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
            self::$minilog->critical(self::$i18n->trans('cant-create-folder', ['%folderName%' => $folder]));
            return false;
        }
        return true;
    }

    /**
     * Link the files.
     *
     * @param string $folder
     * @param string $place
     * @param string $pluginName
     */
    private function linkFiles($folder, $place = 'Core', $pluginName = '')
    {
        if (empty($pluginName)) {
            $path = FS_FOLDER . DIRECTORY_SEPARATOR . $place . DIRECTORY_SEPARATOR . $folder;
        } else {
            $path = FS_FOLDER . DIRECTORY_SEPARATOR . 'Plugins' . DIRECTORY_SEPARATOR . $pluginName . DIRECTORY_SEPARATOR . $folder;
        }

        foreach ($this->scanFolders($path) as $fileName) {
            $infoFile = pathinfo($fileName);
            if (is_dir($path . DIRECTORY_SEPARATOR . $fileName)) {
                $this->createFolder(FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $fileName);
            } elseif ($infoFile['filename'] !== '' && is_file($path . DIRECTORY_SEPARATOR . $fileName)) {
                if ($infoFile['extension'] === 'php') {
                    $this->linkClassFile($fileName, $folder, $place, $pluginName);
                } else {
                    $filePath = $path . DIRECTORY_SEPARATOR . $fileName;
                    $this->linkFile($fileName, $folder, $filePath);
                }
            }
        }
    }

    /**
     * Link classes dynamically.
     *
     * @param string $fileName
     * @param string $folder
     * @param string $place
     * @param string $pluginName
     */
    private function linkClassFile($fileName, $folder, $place, $pluginName)
    {
        if (!file_exists(FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $fileName)) {
            if (empty($pluginName)) {
                $namespace = "FacturaScripts\\" . $place . '\\' . $folder;
                $newNamespace = "FacturaScripts\\Dinamic\\" . $folder;
            } else {
                $namespace = "FacturaScripts\Plugins\\" . $pluginName . '\\' . $folder;
                $newNamespace = "FacturaScripts\Dinamic\\" . $folder;
            }

            $paths = explode(DIRECTORY_SEPARATOR, $fileName);
            for ($key = 0; $key < count($paths) - 1; $key++) {
                $namespace .= "\\" . $paths[$key];
                $newNamespace .= "\\" . $paths[$key];
            }

            $className = basename($fileName, '.php');
            $txt = '<?php namespace ' . $newNamespace . ";\n\n"
                . '/**' . "\n"
                . ' * Class created by Core/Base/PluginManager' . "\n"
                . ' * @package ' . $newNamespace . "\n"
                . ' * @author Carlos García Gómez <carlos@facturascripts.com>' . "\n"
                . ' */' . "\n"
                . 'class ' . $className . ' extends \\' . $namespace . '\\' . $className . "\n{\n}\n";

            file_put_contents(FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $fileName, $txt);
        }
    }

    /**
     * Link other static files.
     *
     * @param string $fileName
     * @param string $folder
     * @param string $filePath
     */
    private function linkFile($fileName, $folder, $filePath)
    {
        if (!file_exists(FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $fileName)) {
            @copy($filePath, FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $fileName);
        }
    }

    /**
     * Makes a recursive scan in folders inside a root folder and extracts the list of files
     * and pass its to an array as result.
     *
     * @param string $folder
     *
     * @return array $result
     */
    private function scanFolders($folder)
    {
        $result = [];
        $rootFolder = array_diff(scandir($folder, SCANDIR_SORT_ASCENDING), ['.', '..']);
        foreach ($rootFolder as $item) {
            $newItem = $folder . DIRECTORY_SEPARATOR . $item;
            if (is_file($newItem)) {
                $result[] = $item;
                continue;
            }
            $result[] = $item;
            foreach ($this->scanFolders($newItem) as $item2) {
                $result[] = $item . DIRECTORY_SEPARATOR . $item2;
            }
        }
        return $result;
    }
}
