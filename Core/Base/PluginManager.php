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
namespace FacturaScripts\Core\Base;

use Exception;
use ZipArchive;

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
     * Deploy all the necessary files in the Dinamic folder to be able to use plugins
     * with the autoloader, but following the priority system of FacturaScripts.
     *
     * @param bool $clean
     */
    public function deploy($clean = true)
    {
        $pluginDeploy = new PluginDeploy();
        $pluginDeploy->deploy($this->pluginPath, self::$enabledPlugins, $clean);

        if (self::$deployedControllers === false) {
            /// finally we started the drivers to complete the menu
            $this->initControllers();
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
                $this->deploy();
                self::$minilog->info(self::$i18n->trans('plugin-disabled', ['%pluginName%' => $pluginName]));
                break;
            }
        }
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
            $this->deploy(false);
            self::$minilog->info(self::$i18n->trans('plugin-enabled', ['%pluginName%' => $pluginName]));
        }
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
     * Returns the plugin path folder.
     *
     * @return string
     */
    public function getPluginPath()
    {
        return $this->pluginPath;
    }

    public function install($zipPath)
    {
        $zipFile = new ZipArchive();
        $result = $zipFile->open($zipPath, ZipArchive::CHECKCONS);
        if (true !== $result) {
            self::$minilog->error('ZIP error: ' . $result);
            return $result;
        }

        /// get folder on plugin zip
        $pathINI = $zipFile->getNameIndex($zipFile->locateName('facturascripts.ini', ZipArchive::FL_NOCASE | ZipArchive::FL_NODIR));
        $folderPluginZip = explode('/', $pathINI);

        /// get plugin name
        $pluginName = '';
        if ($pathINI) {
            $iniFile = $zipFile->getFromIndex($zipFile->locateName('facturascripts.ini', ZipArchive::FL_NOCASE | ZipArchive::FL_NODIR));
            $iniContent = parse_ini_string($iniFile);
            if (!empty($iniContent) && array_key_exists('name', $iniContent)) {
                $pluginName = $iniContent['name'];
            }
        }

        if ('' === $pluginName) {
            self::$minilog->error(self::$i18n->trans('plugin-not-compatible', ['%pluginName%' => $pluginName]));
            return false;
        }


        /// Removing previous version
        if (is_dir($this->pluginPath . $pluginName)) {
            $this->delTree($this->pluginPath . $pluginName);
        }

        /// Extract new version
        $zipFile->extractTo($this->pluginPath);
        $zipFile->close();

        /// Rename folder Plugin
        if ($folderPluginZip[0] !== $pluginName) {
            rename($this->pluginPath . $folderPluginZip[0], $this->pluginPath . $pluginName);
        }

        self::$minilog->info(self::$i18n->trans('plugin-installed', ['%pluginName%' => $pluginName]));
        return true;
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

    public function remove($pluginName)
    {
        /// can't remove enabled plugins
        if (in_array($pluginName, self::$enabledPlugins)) {
            self::$minilog->error(self::$i18n->trans('plugin-enabled', ['%pluginName%' => $pluginName]));
            return false;
        }

        $pluginPath = $this->getPluginPath() . $pluginName;
        if (is_dir($pluginPath) || is_file($pluginPath)) {
            $this->delTree($pluginPath);
            self::$minilog->info(self::$i18n->trans('plugin-deleted', ['%pluginName%' => $pluginName]));
            return true;
        }

        self::$minilog->info(self::$i18n->trans('plugin-delete-error', ['%pluginName%' => $pluginName]));
        return false;
    }

    /**
     * Recursive delete directory.
     *
     * @param string $dir
     *
     * @return bool
     */
    private function delTree($dir)
    {
        $files = [];
        if (is_dir($dir)) {
            $files = array_diff(scandir($dir, SCANDIR_SORT_ASCENDING), ['.', '..']);
        }
        foreach ($files as $file) {
            is_dir($dir . '/' . $file) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        return is_dir($dir) ? rmdir($dir) : unlink($dir);
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
        $txt = implode(',', array_unique(self::$enabledPlugins));
        file_put_contents(self::$pluginListFile, $txt);
    }
}
