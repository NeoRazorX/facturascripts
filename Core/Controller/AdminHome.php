<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Model;
use Symfony\Component\HttpFoundation\Response;

/**
 * AdminHome manage the basic settings.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AdminHome extends Base\Controller
{

    /**
     * List of enabled plugins.
     * @var array
     */
    public $enabledPlugins;

    /**
     * PHP Upload Max File Size.
     * @var int
     */
    public $uploadMaxFileSize;

    /**
     * PHP Post Max Size.
     * @var int
     */
    public $postMaxSize;

    /**
     * Plugin Manager.
     * @var Base\PluginManager
     */
    public $pluginManager;
    
    /**
     * Runs the controller's private logic.
     *
     * @param Response $response
     * @param Model\User $user
     * @param Base\ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        /// For now, always deploy the contents of Dinamic, for testing purposes
        $this->pluginManager = new Base\PluginManager();
        $this->pluginManager->deploy();
        $this->cache->clear();

        $this->enabledPlugins = $this->pluginManager->enabledPlugins();
        $this->postMaxSize = $this->returnKBytes(ini_get('post_max_size'));
        $this->uploadMaxFileSize = $this->returnKBytes(ini_get('upload_max_filesize'));

        $action = $this->request->get('action', '');
        $this->execAction($action);
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'admin';
        $pageData['title'] = 'control-panel';
        $pageData['icon'] = 'fa-wrench';

        return $pageData;
    }

    /**
     * Restores .htaccess to default settings
     */
    private function checkHtaccess()
    {
        if (!file_exists(FS_FOLDER . '/.htaccess')) {
            // TODO: Don't assume that the example exists
            $txt = file_get_contents(FS_FOLDER . '/htaccess-sample');
            file_put_contents('.htaccess', $txt);
        }
    }

    /**
     * Execute main actions.
     *
     * @param $action
     */
    private function execAction($action)
    {
        switch ($action) {
            case 'upload':
                $this->uploadPlugin($this->request->files->get('plugin', []));
                /// Refresh enabled plugins lists after upload
                $this->enabledPlugins = $this->pluginManager->enabledPlugins();
                break;

            case 'enable':
                $this->enablePlugin($this->request->get('plugin', ''));
                /// Refresh enabled plugins lists after enable
                $this->enabledPlugins = $this->pluginManager->enabledPlugins();
                break;

            case 'disable':
                $this->disablePlugin($this->request->get('plugin', ''));
                /// Refresh enabled plugins lists after disable
                $this->enabledPlugins = $this->pluginManager->enabledPlugins();
                break;

            case 'remove':
                $this->removePlugin($this->request->get('plugin', ''));
                /// Refresh enabled plugins lists after remove
                $this->enabledPlugins = $this->pluginManager->enabledPlugins();
                break;

            default:
                $this->checkHtaccess();
                break;
        }
    }

    /**
     * Returns if file exists.
     *
     * @param string $file
     *
     * @return bool
     */
    public function fileExists($file)
    {
        return file_exists($file);
    }

    /**
     * Disable the plugin name received.
     *
     * @param string $disablePlugin
     *
     * @return bool
     */
    private function disablePlugin($disablePlugin)
    {
        if (!empty($disablePlugin)) {
            if (in_array($disablePlugin, $this->enabledPlugins, false)) {
                $this->pluginManager->disable($disablePlugin);
                $this->miniLog->error($this->i18n->trans('plugin-disabled'));
                $this->pluginManager->deploy();
                return true;
            }

            $this->miniLog->error($this->i18n->trans('plugin-is-not-yet-enabled'));
        }

        return false;
    }

    /**
     * Remove and disable the plugin name received.
     *
     * @param string $removePlugin
     *
     * @return bool
     */
    private function removePlugin($removePlugin)
    {
        if (!empty($removePlugin)) {
            $this->pluginManager->disable($removePlugin);
            $pluginPath = $this->pluginManager->getPluginPath() . $removePlugin;
            if (is_dir($pluginPath) || is_file($pluginPath)) {
                $this->pluginManager->deploy();
                $this->delTree($this->pluginManager->getPluginPath() . $removePlugin);
                $this->miniLog->error($this->i18n->trans('plugin-deleted', ['%pluginName%' => $removePlugin]));
                return true;
            }

            $this->miniLog->error($this->i18n->trans('plugin-yet-deleted', ['%pluginName%' => $removePlugin]));
        }

        return false;
    }

    /**
     * Enable the plugin name received.
     *
     * @param string $enablePlugin
     *
     * @return bool
     */
    private function enablePlugin($enablePlugin)
    {
        if (!empty($enablePlugin)) {
            if (!in_array($enablePlugin, $this->enabledPlugins, false)) {
                $this->pluginManager->enable($enablePlugin);
                $this->miniLog->info($this->i18n->trans('plugin-enabled'));
                $this->pluginManager->deploy();
                $this->enabledPlugins = $this->pluginManager->enabledPlugins();
                return true;
            }

            $this->miniLog->info($this->i18n->trans('plugin-yet-enabled'));
        }

        return false;
    }

    /**
     * Upload and enable a plugin.
     *
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile[] $uploadFiles
     */
    private function uploadPlugin($uploadFiles)
    {
        foreach ($uploadFiles as $uploadFile) {
            if ($uploadFile->getMimeType() === 'application/zip') {
                $listFilesBefore = array_diff(scandir($this->pluginManager->getPluginPath(), SCANDIR_SORT_ASCENDING), ['.', '..']);
                $result = $this->unzipFile($uploadFile->getPathname(), $this->pluginManager->getPluginPath(), $listFilesBefore);
                switch ((int) $result) {
                    case 1:
                        $listFilesAfter = array_diff(scandir($this->pluginManager->getPluginPath(), SCANDIR_SORT_ASCENDING), ['.', '..']);
                        /// Contains added files on a list
                        $diffFolders = array_diff($listFilesAfter, $listFilesBefore);
                        foreach ($diffFolders as $folder) {
                            if (is_dir($this->pluginManager->getPluginPath() . $folder)) {
                                $pluginName = $this->getVerifiedPluginName($uploadFile->getPathname());
                                $this->miniLog->info($this->i18n->trans('plugin-installed', ['%pluginName%' => $pluginName]));
                                $this->enablePlugin($pluginName);
                            } elseif (is_file($this->pluginManager->getPluginPath() . $folder)) {
                                $this->delTree($this->pluginManager->getPluginPath() . $folder);
                            }
                        }
                        break;
                    case 0:
                        $this->miniLog->error($this->i18n->trans('can-not-open-zip-file', ['%zipFile%' => $result]));
                        break;
                    case -1:
                        $this->miniLog->error($this->i18n->trans('plugin-missing-ini', ['%pluginName%' => $result]));
                        break;
                    case -2:
                        $this->miniLog->error($this->i18n->trans('plugin-name-missing-ini', ['%pluginName%' => $result]));
                        break;
                }
                unlink($uploadFile->getPathname());
            } else {
                $this->miniLog->error($this->i18n->trans('file-not-supported'));
            }
        }
    }

    /**
     * Return the verified name, if its different than extracted folder, also rename it.
     *
     * @param string $pluginUnzipped
     *
     * @return string|false
     */
    private function getVerifiedPluginName($pluginUnzipped)
    {
        $zipFile = new \ZipArchive();
        $result = $zipFile->open($pluginUnzipped);
        if ($result) {
            $fsIni = $zipFile->getFromName($zipFile->getNameIndex(0) . 'facturascripts.ini');
            $zipFile->close();
            if (!$fsIni) {
                return -1;
            }
            $fsIniContent = parse_ini_string($fsIni);
            if (!array_key_exists('name', $fsIniContent)) {
                return -2;
            }
            return $fsIniContent['name'];
        }
        return false;
    }

    /**
     * Unzip the file path to destiny folder.
     *
     * @param string $filePath
     * @param string $destinyFolder
     * @param array $listFilesBefore
     *
     * @return bool|int|string
     */
    private function unzipFile($filePath, $destinyFolder, &$listFilesBefore)
    {
        $zipFile = new \ZipArchive();
        $result = $zipFile->open($filePath);
        $folderPlugin = str_replace('/', '', $zipFile->getNameIndex(0));
        if ($result) {
            $pluginName = $this->getVerifiedPluginName($filePath);
            if ($pluginName) {
                if (is_dir($destinyFolder . $pluginName)) {
                    $this->miniLog->info($this->i18n->trans('removing-previous-version', ['%pluginName%' => $pluginName]));
                    $this->delTree($destinyFolder . $pluginName);
                    /// Update the list before, if we delete an existing folder
                    $listFilesBefore = array_diff(scandir($this->pluginManager->getPluginPath(), SCANDIR_SORT_ASCENDING), ['.', '..']);
                }
                if ($result === true) {
                    $zipFile->extractTo($destinyFolder);
                    $zipFile->close();
                }
                if ($folderPlugin !== $pluginName) {
                    if (!@rename($destinyFolder . $folderPlugin, $destinyFolder . $pluginName)) {
                        $this->miniLog->error($this->i18n->trans('plugin-can-not-renamed', ['%folderPlugin%' => $folderPlugin, '%pluginName%' => $pluginName]));
                    } else {
                        $this->miniLog->info($this->i18n->trans('plugin-renamed', ['%folderPlugin%' => $folderPlugin, '%pluginName%' => $pluginName]));
                    }
                }
                return $result;
            }
            return false;
        }
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
     * Return the unit of $val in KBytes.
     *
     * @param string $val
     *
     * @return int
     */
    private function returnKBytes($val)
    {
        $value = (int) substr(trim($val), 0, -1);
        $last = strtolower(substr($val, -1));
        switch ($last) {
            case 'g':
                $value *= 1024;
            // no break - Pass all cases to transform to KB
            case 'm':
                $value *= 1024;
            // no break - Pass all cases to transform to KB
        }
        return $value;
    }
}
