<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Base;

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
     * FacturaScripts core version.
     */
    const CORE_VERSION = 2018.018;

    /**
     * Path to list plugins on file.
     */
    const PLUGIN_LIST_FILE = FS_FOLDER . DIRECTORY_SEPARATOR . 'MyFiles' . DIRECTORY_SEPARATOR . 'plugins.json';

    /**
     * Plugin path folder.
     */
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

        if (!defined('FS_DISABLE_ADD_PLUGINS')) {
            define('FS_DISABLE_ADD_PLUGINS', false);
        }

        if (!defined('FS_DISABLE_RM_PLUGINS')) {
            define('FS_DISABLE_RM_PLUGINS', false);
        }
    }

    /**
     * Deploy all the necessary files in the Dinamic folder to be able to use plugins
     * with the autoloader, but following the priority system of FacturaScripts.
     *
     * @param bool $clean
     * @param bool $initControllers
     */
    public function deploy(bool $clean = true, bool $initControllers = false)
    {
        $pluginDeploy = new PluginDeploy();
        $pluginDeploy->deploy(self::PLUGIN_PATH, $this->enabledPlugins(), $clean);

        if ($initControllers) {
            $pluginDeploy->initControllers();
        }
    }

    /**
     * Disable the indicated plugin.
     *
     * @param string $pluginName
     */
    public function disable(string $pluginName)
    {
        foreach (self::$enabledPlugins as $key => $value) {
            if ($value['name'] !== $pluginName) {
                continue;
            }

            unset(self::$enabledPlugins[$key]);
            $this->disableByDependecy($pluginName);
            $this->save();
            $this->deploy(true, true);
            self::$minilog->notice(self::$i18n->trans('plugin-disabled', ['%pluginName%' => $pluginName]));
            break;
        }
    }

    /**
     * Activate the indicated plugin.
     *
     * @param string $pluginName
     */
    public function enable(string $pluginName)
    {
        /// is pluginName enabled?
        foreach (self::$enabledPlugins as $value) {
            if ($value['name'] === $pluginName) {
                return;
            }
        }

        foreach ($this->installedPlugins() as $plugin) {
            if ($plugin['name'] !== $pluginName) {
                continue;
            }

            if ($this->checkRequire($plugin['require'])) {
                self::$enabledPlugins[] = $plugin;
                $this->save();
                $this->deploy(false, true);
                $this->initPlugin($pluginName);
                self::$minilog->notice(self::$i18n->trans('plugin-enabled', ['%pluginName%' => $pluginName]));
            }
            break;
        }
    }

    /**
     * Returns the list of active plugins.
     *
     * @return array
     */
    public function enabledPlugins(): array
    {
        $enabled = [];
        foreach (self::$enabledPlugins as $value) {
            $enabled[] = $value['name'];
        }

        return $enabled;
    }

    /**
     * Install a new plugin if is compatible.
     *
     * @param string $zipPath
     * @param string $zipName
     *
     * @return bool
     */
    public function install(string $zipPath, string $zipName = 'plugin.zip'): bool
    {
        if (FS_DISABLE_ADD_PLUGINS) {
            return false;
        }

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
            FileManager::delTree(self::PLUGIN_PATH . $info['name']);
        }

        /// Extract new version
        $zipFile->extractTo(self::PLUGIN_PATH);
        $zipFile->close();

        /// Rename folder Plugin
        if ($folderPluginZip[0] !== $info['name']) {
            rename(self::PLUGIN_PATH . $folderPluginZip[0], self::PLUGIN_PATH . $info['name']);
        }

        self::$minilog->notice(self::$i18n->trans('plugin-installed', ['%pluginName%' => $info['name']]));
        return true;
    }

    /**
     * Returns the list of installed plugins.
     *
     * @return array
     */
    public function installedPlugins(): array
    {
        $plugins = [];

        foreach (FileManager::scanFolder(self::PLUGIN_PATH, false) as $folder) {
            $iniPath = self::PLUGIN_PATH . $folder . DIRECTORY_SEPARATOR . 'facturascripts.ini';
            $iniContent = file_exists($iniPath) ? file_get_contents($iniPath) : '';
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
    public function remove(string $pluginName): bool
    {
        if (FS_DISABLE_RM_PLUGINS) {
            return false;
        }

        /// can't remove enabled plugins
        if (in_array($pluginName, self::$enabledPlugins)) {
            self::$minilog->error(self::$i18n->trans('plugin-enabled', ['%pluginName%' => $pluginName]));
            return false;
        }

        $pluginPath = self::PLUGIN_PATH . $pluginName;
        if (is_dir($pluginPath) || is_file($pluginPath)) {
            FileManager::delTree($pluginPath);
            self::$minilog->notice(self::$i18n->trans('plugin-deleted', ['%pluginName%' => $pluginName]));
            return true;
        }

        self::$minilog->notice(self::$i18n->trans('plugin-delete-error', ['%pluginName%' => $pluginName]));
        return false;
    }

    /**
     * Check for plugins needed.
     *
     * @param array $require
     *
     * @return bool
     */
    private function checkRequire(array $require): bool
    {
        if (empty($require)) {
            return true;
        }

        foreach ($require as $req) {
            $found = false;
            foreach ($this->enabledPlugins() as $pluginName) {
                if ($pluginName === $req) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                self::$minilog->warning(self::$i18n->trans('plugin-needed', ['%pluginName%' => $req]));
                return false;
            }
        }

        return true;
    }

    /**
     * Disables plugins that depends on $pluginDisabled
     *
     * @param string $pluginDisabled
     */
    private function disableByDependecy(string $pluginDisabled)
    {
        foreach (self::$enabledPlugins as $key => $value) {
            if (in_array($pluginDisabled, $value['require'])) {
                self::$minilog->info(self::$i18n->trans('plugin-disabled', ['%pluginName%' => $value['name']]));
                unset(self::$enabledPlugins[$key]);
                $this->disableByDependecy($value['name']);
            }
        }
    }

    /**
     * Return plugin information.
     *
     * @param string $pluginName
     * @param string $iniContent
     *
     * @return array
     */
    private function getPluginInfo(string $pluginName, string $iniContent): array
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
                $info[$key] = $ini[$key] ?? $info[$key];
            }

            if (isset($ini['require'])) {
                $info['require'] = explode(',', $ini['require']);
            }

            if ($info['min_version'] >= 2018 && $info['min_version'] <= self::CORE_VERSION) {
                $info['compatible'] = true;
                $info['description'] = ('Incompatible' === $info['description']) ? self::$i18n->trans('compatible') : $info['description'];
            } else {
                $info['description'] = self::$i18n->trans('incompatible-with-facturascripts', ['%version%' => self::CORE_VERSION]);
            }

            $info['enabled'] = in_array($info['name'], $this->enabledPlugins());
        }

        return $info;
    }

    /**
     * 
     * @param string $pluginName
     */
    private function initPlugin(string $pluginName)
    {
        $pluginClass = "FacturaScripts\\Plugins\\{$pluginName}\\Init";
        if (class_exists($pluginClass)) {
            $initObject = new $pluginClass();
            $initObject->update();
        }
    }

    /**
     * Returns an array with the list of plugins in the plugin.list file.
     *
     * @return array
     */
    private function loadFromFile(): array
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
    private function save(): bool
    {
        $content = json_encode(self::$enabledPlugins);
        return file_put_contents(self::PLUGIN_LIST_FILE, $content) !== false;
    }
}
