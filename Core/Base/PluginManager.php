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

    const MIN_VERSION = 2018;
    const PLUGIN_LIST_FILE = FS_FOLDER . DIRECTORY_SEPARATOR . 'MyFiles' . DIRECTORY_SEPARATOR . 'plugin.json';
    const PLUGIN_PATH = FS_FOLDER . DIRECTORY_SEPARATOR . 'Plugins' . DIRECTORY_SEPARATOR;

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
     * PluginManager constructor.
     */
    public function __construct()
    {
        if (self::$enabledPlugins === null) {
            self::$enabledPlugins = $this->loadFromFile();
            self::$i18n = new Translator();
            self::$minilog = new MiniLog();
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
        $pluginDeploy->deploy(self::PLUGIN_PATH, $this->enabledPlugins(), $clean);
    }

    /**
     * Disable the indicated plugin.
     *
     * @param string $pluginName
     */
    public function disable($pluginName)
    {
        foreach (self::$enabledPlugins as $i => $value) {
            if ($value['name'] === $pluginName) {
                unset(self::$enabledPlugins[$i]);
                $this->save();
                $this->deploy();
                $this->initControllers();
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
        foreach ($this->installedPlugins() as $plugin) {
            if ($plugin['name'] === $pluginName) {
                self::$enabledPlugins[] = $plugin;
                $this->save();
                $this->deploy(false);
                $this->initControllers();
                self::$minilog->info(self::$i18n->trans('plugin-enabled', ['%pluginName%' => $pluginName]));
                break;
            }
        }
    }

    /**
     * Returns the list of active plugins.
     *
     * @return array
     */
    public function enabledPlugins()
    {
        $enabled = [];
        foreach (self::$enabledPlugins as $value) {
            $enabled[] = $value['name'];
        }

        return $enabled;
    }

    /**
     * Initialize the controllers dynamically.
     */
    public function initControllers()
    {
        $cache = new Cache();
        $menuManager = new MenuManager();
        $menuManager->init();
        $pageNames = [];

        $files = $this->scanFolder(FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . 'Controller');
        foreach ($files as $fileName) {
            if (substr($fileName, -4) !== '.php') {
                continue;
            }

            $controllerName = substr($fileName, 0, -4);
            $controllerNamespace = 'FacturaScripts\\Dinamic\\Controller\\' . $controllerName;

            if (!class_exists($controllerNamespace)) {
                /// we force the loading of the file because at this point the autoloader will not find it
                require FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . $controllerName . '.php';
            }

            try {
                $controller = new $controllerNamespace($cache, self::$i18n, self::$minilog, $controllerName);
                $menuManager->selectPage($controller->getPageData());
                $pageNames[] = $controllerName;
            } catch (Exception $exc) {
                self::$minilog->critical(self::$i18n->trans('cant-load-controller', ['%controllerName%' => $controllerName]));
            }
        }
        
        $menuManager->removeOld($pageNames);
        $menuManager->reload();
    }

    /**
     * Install a new plugin if is compatible.
     *
     * @param string $zipPath
     * @param string $zipName
     * 
     * @return bool
     */
    public function install($zipPath, $zipName = 'plugin.zip')
    {
        $zipFile = new ZipArchive();
        $result = $zipFile->open($zipPath, ZipArchive::CHECKCONS);
        if (true !== $result) {
            self::$minilog->error('ZIP error: ' . $result);
            return false;
        }

        /// get facturascripts.ini on plugin zip
        $zipIndex = $zipFile->locateName('facturascripts.ini', ZipArchive::FL_NOCASE | ZipArchive::FL_NODIR);
        if (false === $zipIndex) {
            self::$minilog->error(self::$i18n->trans('plugin-not-compatible', ['%pluginName%' => $zipName]));
            return false;
        }

        $pathINI = $zipFile->getNameIndex($zipIndex);
        $folderPluginZip = explode('/', $pathINI);

        /// get plugin information
        $info = $this->getPluginInfo($zipName, $zipFile->getFromIndex($zipIndex));
        if (!$info['compatible']) {
            self::$minilog->error(self::$i18n->trans('plugin-not-compatible', ['%pluginName%' => $zipName]));
            return false;
        }

        /// Removing previous version
        if (is_dir(self::PLUGIN_PATH . $info['name'])) {
            $this->delTree(self::PLUGIN_PATH . $info['name']);
        }

        /// Extract new version
        $zipFile->extractTo(self::PLUGIN_PATH);
        $zipFile->close();

        /// Rename folder Plugin
        if ($folderPluginZip[0] !== $info['name']) {
            rename(self::PLUGIN_PATH . $folderPluginZip[0], self::PLUGIN_PATH . $info['name']);
        }

        self::$minilog->info(self::$i18n->trans('plugin-installed', ['%pluginName%' => $info['name']]));
        return true;
    }

    /**
     * Returns the list of installed plugins.
     *
     * @return array
     */
    public function installedPlugins()
    {
        $plugins = [];
        foreach ($this->scanFolder(self::PLUGIN_PATH) as $folder) {
            $iniPath = self::PLUGIN_PATH . $folder . '/facturascripts.ini';
            $iniContent = file_exists($iniPath) ? file_get_contents($iniPath) : [];
            $plugins[] = $this->getPluginInfo($folder, $iniContent);
        }

        return $plugins;
    }

    /**
     * Remove a plugin only if it's disabled.
     *
     * @param string $pluginName
     *
     * @return bool
     */
    public function remove($pluginName)
    {
        /// can't remove enabled plugins
        if (in_array($pluginName, self::$enabledPlugins)) {
            self::$minilog->error(self::$i18n->trans('plugin-enabled', ['%pluginName%' => $pluginName]));
            return false;
        }

        $pluginPath = self::PLUGIN_PATH . $pluginName;
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
        $files = is_dir($dir) ? $this->scanFolder($dir) : [];
        foreach ($files as $file) {
            is_dir($dir . '/' . $file) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        return is_dir($dir) ? rmdir($dir) : unlink($dir);
    }

    private function getPluginInfo($pluginName, $iniContent)
    {
        $info = [
            'compatible' => false,
            'description' => 'Incompatible',
            'enabled' => false,
            'min_version' => 0.0,
            'name' => $pluginName,
            'require' => [],
            'version' => 1,
        ];

        $ini = parse_ini_string($iniContent);
        if ($ini !== false) {
            foreach (['name', 'version', 'description', 'min_version'] as $key) {
                $info[$key] = isset($ini[$key]) ? $ini[$key] : $info[$key];
            }

            if (isset($ini['require'])) {
                $info['require'] = explode(',', $ini['require']);
            }

            if ($info['min_version'] >= 2018 && $info['min_version'] <= self::MIN_VERSION) {
                $info['compatible'] = true;
            } else {
                $info['description'] = self::$i18n->trans('incompatible-with-facturascripts', ['%version%' => self::MIN_VERSION]);
            }

            $info['enabled'] = in_array($info['name'], $this->enabledPlugins());
        }

        return $info;
    }

    /**
     * Returns an array with the list of plugins in the plugin.list file.
     *
     * @return array
     */
    private function loadFromFile()
    {
        if (file_exists(self::PLUGIN_LIST_FILE)) {
            $content = file_get_contents(self::PLUGIN_LIST_FILE);
            if ($content !== false) {
                return json_decode($content, true);
            }
        }

        return [];
    }

    /**
     * Save the list of plugins in a file.
     * 
     * @return bool
     */
    private function save()
    {
        $content = json_encode(self::$enabledPlugins);
        return file_put_contents(self::PLUGIN_LIST_FILE, $content) !== false;
    }

    /**
     * Returns an array with all files and folders.
     *
     * @param string $folderPath
     *
     * @return Array
     */
    private function scanFolder($folderPath)
    {
        return array_diff(scandir($folderPath, SCANDIR_SORT_ASCENDING), ['.', '..']);
    }
}
