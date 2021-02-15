<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
final class PluginManager
{

    /**
     * FacturaScripts core version.
     */
    const CORE_VERSION = 2021.04;

    /**
     * Path to list plugins on file.
     */
    const PLUGIN_LIST_FILE = \FS_FOLDER . DIRECTORY_SEPARATOR . 'MyFiles' . DIRECTORY_SEPARATOR . 'plugins.json';

    /**
     * Plugin path folder.
     */
    const PLUGIN_PATH = \FS_FOLDER . DIRECTORY_SEPARATOR . 'Plugins' . DIRECTORY_SEPARATOR;

    /**
     * Indicates if a deployment is necessary.
     *
     * @var bool
     */
    private static $deploymentRequired = false;

    /**
     * List of active plugins.
     *
     * @var array
     */
    private static $enabledPlugins;

    /**
     * PluginManager constructor.
     */
    public function __construct()
    {
        if (self::$enabledPlugins === null) {
            self::$enabledPlugins = $this->loadFromFile();
        }

        if (false === \defined('FS_DISABLE_ADD_PLUGINS')) {
            \define('FS_DISABLE_ADD_PLUGINS', false);
        }

        if (false === \defined('FS_DISABLE_RM_PLUGINS')) {
            \define('FS_DISABLE_RM_PLUGINS', false);
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
     * 
     * @return bool
     */
    public function deploymentRequired()
    {
        return self::$deploymentRequired;
    }

    /**
     * Disable the indicated plugin.
     *
     * @param string $pluginName
     *
     * @return bool
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
            ToolBox::i18nLog()->notice('plugin-disabled', ['%pluginName%' => $pluginName]);
            return true;
        }

        return false;
    }

    /**
     * Activate the indicated plugin.
     *
     * @param string $pluginName
     *
     * @return bool
     */
    public function enable(string $pluginName)
    {
        /// is pluginName enabled?
        if (\in_array($pluginName, $this->enabledPlugins())) {
            return true;
        }

        foreach ($this->installedPlugins() as $plugin) {
            if ($plugin['name'] !== $pluginName) {
                continue;
            }

            if ($this->checkRequire($plugin['require'])) {
                $plugin['enabled'] = true;
                self::$enabledPlugins[] = $plugin;
                $this->save();
                $this->deploy(false, true);
                $this->initPlugin($pluginName);
                ToolBox::i18nLog()->notice('plugin-enabled', ['%pluginName%' => $pluginName]);
                return true;
            }
        }

        return false;
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
     * Installs a new plugin.
     *
     * @param string $zipPath
     * @param string $zipName
     * @param bool   $force
     *
     * @return bool
     */
    public function install(string $zipPath, string $zipName = 'plugin.zip', bool $force = false): bool
    {
        if (FS_DISABLE_ADD_PLUGINS && !$force) {
            ToolBox::i18nLog()->warning('plugin-installation-disabled');
            return false;
        }

        $zipFile = new ZipArchive();
        if (false === $this->testZipFile($zipFile, $zipPath, $zipName)) {
            return false;
        }

        /// get the facturascripts.ini file inside the zip
        $zipIndex = $zipFile->locateName('facturascripts.ini', ZipArchive::FL_NODIR);
        $pathINI = $zipFile->getNameIndex($zipIndex);
        $info = $this->getPluginInfo($zipName, $zipFile->getFromIndex($zipIndex));
        if (false === $info['compatible']) {
            $errorTag = empty($info['min_version']) ? 'plugin-unsupported-version' : 'plugin-needs-fs-version';
            ToolBox::i18nLog()->error(
                $errorTag, ['%pluginName%' => $zipName, '%minVersion%' => $info['min_version'], '%version%' => self::CORE_VERSION]
            );
            return false;
        }

        /// Removing previous version
        if (\is_dir(self::PLUGIN_PATH . $info['name'])) {
            ToolBox::files()->delTree(self::PLUGIN_PATH . $info['name']);
        }

        /// Extract new version
        if (false === $zipFile->extractTo(self::PLUGIN_PATH)) {
            ToolBox::log()->error('ZIP EXTRACT ERROR: ' . $zipName);
            $zipFile->close();
            return false;
        }

        $zipFile->close();

        /// Rename folder Plugin
        $folderPluginZip = \explode('/', $pathINI);
        if ($folderPluginZip[0] !== $info['name']) {
            \rename(self::PLUGIN_PATH . $folderPluginZip[0], self::PLUGIN_PATH . $info['name']);
        }

        /// Deployment required?
        if (\in_array($info['name'], $this->enabledPlugins())) {
            self::$deploymentRequired = true;
        }

        ToolBox::i18nLog()->notice('plugin-installed', ['%pluginName%' => $info['name']]);
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

        foreach (ToolBox::files()->scanFolder(self::PLUGIN_PATH, false) as $folder) {
            $iniPath = self::PLUGIN_PATH . $folder . DIRECTORY_SEPARATOR . 'facturascripts.ini';
            $iniContent = \file_exists($iniPath) ? \file_get_contents($iniPath) : '';
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
            ToolBox::i18nLog()->warning('plugin-removal-disabled');
            return false;
        }

        /// can't remove enabled plugins
        if (\in_array($pluginName, $this->enabledPlugins())) {
            ToolBox::i18nLog()->warning('plugin-enabled', ['%pluginName%' => $pluginName]);
            return false;
        }

        $pluginPath = self::PLUGIN_PATH . $pluginName;
        if (\is_dir($pluginPath) || \is_file($pluginPath)) {
            ToolBox::files()->delTree($pluginPath);
            ToolBox::i18nLog()->notice('plugin-deleted', ['%pluginName%' => $pluginName]);
            return true;
        }

        ToolBox::i18nLog()->error('plugin-delete-error', ['%pluginName%' => $pluginName]);
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

            if (false === $found) {
                ToolBox::i18nLog()->warning('plugin-needed', ['%pluginName%' => $req]);
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
            if (\in_array($pluginDisabled, $value['require'])) {
                ToolBox::i18nLog()->warning('plugin-disabled', ['%pluginName%' => $value['name']]);
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
            'version' => 1
        ];

        $ini = \parse_ini_string($iniContent);
        if ($ini !== false) {
            foreach (['name', 'version', 'description', 'min_version'] as $key) {
                $info[$key] = $ini[$key] ?? $info[$key];
            }

            if (isset($ini['require'])) {
                $info['require'] = \explode(',', $ini['require']);
            }

            if ($info['min_version'] >= 2018 && $info['min_version'] <= self::CORE_VERSION) {
                $info['compatible'] = true;
                $info['description'] = ('Incompatible' === $info['description']) ? ToolBox::i18n()->trans('compatible') : $info['description'];
            } else {
                $info['description'] = ToolBox::i18n()->trans('incompatible-with-facturascripts', ['%version%' => self::CORE_VERSION]);
            }

            $info['enabled'] = \in_array($info['name'], $this->enabledPlugins());
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
        if (\class_exists($pluginClass)) {
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
        if (\file_exists(self::PLUGIN_LIST_FILE)) {
            $content = \file_get_contents(self::PLUGIN_LIST_FILE);
            if ($content !== false) {
                return \json_decode($content, true);
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
        $content = \json_encode(self::$enabledPlugins);
        return \file_put_contents(self::PLUGIN_LIST_FILE, $content) !== false;
    }

    /**
     * 
     * @param ZipArchive $zipFile
     * @param string     $zipPath
     * @param string     $zipName
     *
     * @return bool
     */
    private function testZipFile(&$zipFile, $zipPath, $zipName): bool
    {
        $result = $zipFile->open($zipPath, ZipArchive::CHECKCONS);
        if (true !== $result) {
            ToolBox::log()->error('ZIP error: ' . $result);
            return false;
        }

        /// get the facturascripts.ini file inside the zip
        $zipIndex = $zipFile->locateName('facturascripts.ini', ZipArchive::FL_NODIR);
        if (false === $zipIndex) {
            ToolBox::i18nLog()->error('plugin-not-compatible', ['%pluginName%' => $zipName, '%version%' => self::CORE_VERSION]);
            return false;
        }

        /// the zip must contain the plugin folder
        $pathINI = $zipFile->getNameIndex($zipIndex);
        if (\count(explode('/', $pathINI)) !== 2) {
            ToolBox::i18nLog()->error('zip-error-wrong-structure');
            return false;
        }

        /// get folders inside the zip file
        $folders = [];
        for ($index = 0; $index < $zipFile->numFiles; $index++) {
            $data = $zipFile->statIndex($index);
            $path = \explode('/', $data['name']);
            if (\count($path) > 1) {
                $folders[$path[0]] = $path[0];
            }
        }

        //// the zip must contain a single plugin
        if (\count($folders) != 1) {
            ToolBox::i18nLog()->error('zip-error-wrong-structure');
            return false;
        }

        return true;
    }
}
