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
 * Description of admin_home
 * At this time, manage a list of available plugins and main actions with they.
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
     * User language.
     * @var string
     */
    public $lang;

    /**
     * Plugin Manager.
     * @var Base\PluginManager
     */
    public $pMng;

    /**
     * AdminHome constructor.
     *
     * @param Base\Cache      $cache
     * @param Base\Translator $i18n
     * @param Base\MiniLog    $miniLog
     * @param string          $className
     */
    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);

        /// Check for .htaccess
        $this->checkHtaccess();

        /// For now, always deploy the contents of Dinamic, for testing purposes
        $pluginManager = new Base\PluginManager();
        $pluginManager->deploy(true);

        $this->cache->clear();
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
     * Runs the controller's private logic.
     *
     * @param Response $response
     * @param Model\User|null $user
     */
    public function privateCore(&$response, $user)
    {
        parent::privateCore($response, $user);
        $this->lang = $this->request->cookies->get('fsLang');
        $this->pMng = new Base\PluginManager();
        $this->enabledPlugins = $this->pMng->enabledPlugins();

        $this->disablePlugin($this->request->get('disable', ''));
        $this->removePlugin($this->request->get('remove', ''));
        $this->enablePlugin($this->request->get('enable', ''));
        $this->uploadPlugin($this->request->files->get('plugin', []));

        $this->enabledPlugins = $this->pMng->enabledPlugins();
        $this->postMaxSize = $this->returnKBytes(ini_get('post_max_size'));
        $this->uploadMaxFileSize = $this->returnKBytes(ini_get('upload_max_filesize'));
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
            if (in_array($disablePlugin, $this->enabledPlugins)) {
                $this->pMng->disable($disablePlugin);
                $this->miniLog->error($this->i18n->trans('plugin-disabled'));
                $this->pMng->deploy();
                return true;
            }

            $this->miniLog->error($this->i18n->trans('plugin-is-not-yet-enabled'));
            return false;
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
            $this->pMng->disable($removePlugin);
            if (is_dir($this->pMng->getPluginPath() . $removePlugin)) {
                $this->pMng->deploy();
                $this->miniLog->error($this->i18n->trans('plugin-deleted', [$removePlugin]));
                $this->delTree($this->pMng->getPluginPath() . $removePlugin);
                return true;
            }
            $this->miniLog->error($this->i18n->trans('plugin-yet-deleted', [$removePlugin]));
            return false;
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
            if (!in_array($enablePlugin, $this->enabledPlugins)) {
                $this->pMng->enable($enablePlugin);
                $this->miniLog->info($this->i18n->trans('plugin-enabled'));
                $this->pMng->deploy();
                return true;
            }
            $this->miniLog->info($this->i18n->trans('plugin-yet-enabled'));
            return false;
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
                $listFilesBefore = array_diff(scandir($this->pMng->getPluginPath(), SCANDIR_SORT_ASCENDING), ['.', '..']);
                $result = $this->unzipFile($uploadFile->getPathname(), $this->pMng->getPluginPath(), $listFilesBefore);
                if ($result === true) {
                    $listFilesAfter = array_diff(scandir($this->pMng->getPluginPath(), SCANDIR_SORT_ASCENDING), ['.', '..']);
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

    /**
     * Return the verified name, if its different than extracted folder, also rename it.
     *
     * @param string $pluginUnzipped
     *
     * @return string
     */
    private function getVerifiedPluginName($pluginUnzipped)
    {
        /// If contains any '-', assume that is like 'pluginname-branch-commitid'
        /// Better verify it from facturascripts.ini field name
        $pluginFolder = substr($pluginUnzipped, 0, strpos($pluginUnzipped, '-')) ?: '';
        $pluginFolder = empty($pluginFolder) ? $pluginUnzipped : $pluginFolder;
        if ($pluginUnzipped !== $pluginFolder) {
            $folder = $this->pMng->getPluginPath() . $pluginFolder;
            if (file_exists($folder) && is_dir($folder)) {
                $this->miniLog->info($this->i18n->trans('removing-previous-version', [$pluginFolder]));
                $this->delTree($folder);
            }
            if (!@rename($this->pMng->getPluginPath() . $pluginUnzipped, $folder)) {
                $this->miniLog->error($this->i18n->trans('plugin-can-not-renamed', [$pluginUnzipped, $pluginFolder]));
            } else {
                $this->miniLog->info($this->i18n->trans('plugin-renamed', [$pluginUnzipped, $pluginFolder]));
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
            $listFilesBefore = array_diff(scandir($this->pMng->getPluginPath(), SCANDIR_SORT_ASCENDING), ['.', '..']);
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
