<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
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
 * Manage list of available plugins and main actions with they.
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class Plugins extends Base\Controller
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
     * User language.
     * @var string
     */
    public $lang;

    /**
     * Plugin Manager.
     * @var Base\PluginManager
     */
    public $pm;

    /**
     * Runs the controller's private logic.
     *
     * @param Response $response
     * @param Model\User|null $user
     */
    public function privateCore(&$response, $user)
    {
        parent::privateCore($response, $user);
        $this->lang = $this->request->cookies->get('fsLang');
        $this->pm = new Base\PluginManager();
        $this->enabledPlugins = $this->pm->enabledPlugins();

        $this->disablePlugin($this->request->get('disable', ''));
        $this->removePlugin($this->request->get('remove', ''));
        $this->enablePlugin($this->request->get('enable', ''));
        $this->uploadPlugin($this->request->files->get('plugin'));

        $this->enabledPlugins = $this->pm->enabledPlugins();
        $this->postMaxSize = $this->returnKBytes(ini_get('post_max_size'));
        $this->uploadMaxFileSize = $this->returnKBytes(ini_get('upload_max_filesize'));
    }

    /**
     * Returns basic page attributes.
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'plugins';
        $pagedata['icon'] = 'fa-puzzle-piece';
        $pagedata['menu'] = 'admin';

        return $pagedata;
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
     */
    private function disablePlugin($disablePlugin)
    {
        if (!empty($disablePlugin)) {
            if (in_array($disablePlugin, $this->enabledPlugins)) {
                $this->pm->disable($disablePlugin);
                $this->miniLog->error($this->i18n->trans('plugin-disabled'));
                $this->pm->deploy();
            } else {
                $this->miniLog->error($this->i18n->trans('plugin-is-not-yet-enabled'));
            }
        }
    }

    /**
     * Remove and disable the plugin name received.
     *
     * @param string $removePlugin
     */
    private function removePlugin($removePlugin)
    {
        if (!empty($removePlugin)) {
            if (is_dir($this->pm->getPluginPath() . $removePlugin)) {
                $this->pm->disable($removePlugin);
                $this->pm->deploy();
                $this->miniLog->error($this->i18n->trans('plugin-deleted', [$removePlugin]));
                $this->delTree($this->pm->getPluginPath() . $removePlugin);
            } else {
                $this->miniLog->error($this->i18n->trans('plugin-yet-deleted', [$removePlugin]));
            }
        }
    }

    /**
     * Enable the plugin name received.
     *
     * @param string $enablePlugin
     */
    private function enablePlugin($enablePlugin)
    {
        if (!empty($enablePlugin)) {
            if (!in_array($enablePlugin, $this->enabledPlugins)) {
                $this->pm->enable($enablePlugin);
                $this->miniLog->info($this->i18n->trans('plugin-enabled'));
            } else {
                $this->miniLog->info($this->i18n->trans('plugin-yet-enabled'));
            }
            $this->pm->deploy();
        }
    }

    /**
     * Upload and enable a plugin.
     *
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile[] $uploadFiles
     */
    private function uploadPlugin($uploadFiles)
    {
        if ($uploadFiles === null) {
            //$this->miniLog->error($this->i18n->trans('file-not-uploaded'));
        } else {
            foreach ($uploadFiles as $uploadFile) {
                if ($uploadFile->getMimeType() === 'application/zip') {
                    $listFilesBefore = array_diff(scandir($this->pm->getPluginPath(), SCANDIR_SORT_ASCENDING), ['.', '..']);
                    $result = $this->unzipFile($uploadFile->getPathname(), $this->pm->getPluginPath(), $listFilesBefore);
                    if ($result === true) {
                        $listFilesAfter = array_diff(scandir($this->pm->getPluginPath(), SCANDIR_SORT_ASCENDING), ['.', '..']);
                        /// Contains added files on a list
                        $diffFolders = array_diff($listFilesAfter, $listFilesBefore);
                        foreach ($diffFolders as $folder) {
                            $pluginName = $this->getVerifiedPluginName($folder);
                            $this->miniLog->info($this->i18n->trans('plugin-installed', [$pluginName]));
                            $this->enablePlugin($pluginName);
                        }
                    } else {
                        $this->miniLog->error($this->i18n->trans('can-not-open-zip-file', [$result]));
                    }
                    unlink($uploadFile->getPathname());
                } else {
                    $this->miniLog->error($this->i18n->trans('file-not-supported'));
                }
            }
        }
    }

    /**
     * Return the verified name, if its different than extracted folder, also rename it.
     *
     * @param string $pluginUnziped
     *
     * @return bool|string
     */
    private function getVerifiedPluginName($pluginUnziped)
    {
        /// If contains any '-', assume that is like 'pluginname-branch-commitid'
        /// Better verify it from facturascripts.ini field name
        $pluginFolder = substr($pluginUnziped, 0, strpos($pluginUnziped, '-'));
        $pluginFolder = empty($pluginFolder) ? $pluginUnziped : $pluginFolder;
        if ($pluginUnziped !== $pluginFolder) {
            $folder = $this->pm->getPluginPath() . $pluginFolder;
            if (file_exists($folder) && is_dir($folder)) {
                $this->miniLog->info($this->i18n->trans('removing-previous-version', [$pluginFolder]));
                $this->delTree($folder);
            }
            if (!@rename($this->pm->getPluginPath() . $pluginUnziped, $folder)) {
                $this->miniLog->error($this->i18n->trans('plugin-can-not-renamed', [$pluginUnziped, $pluginFolder]));
            } else {
                $this->miniLog->info($this->i18n->trans('plugin-renamed', [$pluginUnziped, $pluginFolder]));
            }
        }
        return $pluginFolder;
    }

    /**
     * Unzip the file path to destiny folder.
     *
     * @param string $filePath
     * @param string $destinyFolder
     * @param array $listFilesBefore
     *
     * @return mixed
     */
    private function unzipFile($filePath, $destinyFolder, &$listFilesBefore)
    {
        $zipFile = new \ZipArchive();
        $result = $zipFile->open($filePath);
        $folder = str_replace('/', '', $zipFile->getNameIndex(0));
        $pluginName = $this->getVerifiedPluginName($folder);
        if (is_dir($destinyFolder . $pluginName)) {
            $this->miniLog->info($this->i18n->trans('removing-previous-version', [$pluginName]));
            $this->delTree($destinyFolder . $pluginName);
            /// Update the list before, if we delete an existing folder
            $listFilesBefore = array_diff(scandir($this->pm->getPluginPath(), SCANDIR_SORT_ASCENDING), ['.', '..']);
        }
        if ($result === true) {
            $zipFile->extractTo($destinyFolder);
            $zipFile->close();
        }
        return $result;
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
        $files = array_diff(scandir($dir, SCANDIR_SORT_ASCENDING), ['.', '..']);
        foreach ($files as $file) {
            is_dir($dir . '/' . $file) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    /**
     * Return the unit of $val in KBytes.
     *
     * @param string $val
     *
     * @return int|string
     */
    private function returnKBytes($val)
    {
        $value = substr(trim($val), 0, -1);
        $last = strtolower(substr($val, -1));
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
        }
        return $value;
    }
}
