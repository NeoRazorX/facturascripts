<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\DownloadTools;
use FacturaScripts\Core\Base\FileManager;
use FacturaScripts\Core\Base\Migrations;
use FacturaScripts\Core\Base\PluginManager;
use FacturaScripts\Core\Base\TelemetryManager;
use FacturaScripts\Dinamic\Model\User;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

/**
 * Description of Updater
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Updater extends Controller
{

    const CORE_PROJECT_ID = 1;
    const CORE_ZIP_FOLDER = 'facturascripts';
    const UPDATE_CORE_URL = 'https://facturascripts.com/DownloadBuild';

    /**
     *
     * @var PluginManager
     */
    private $pluginManager;

    /**
     *
     * @var TelemetryManager
     */
    public $telemetryManager;

    /**
     *
     * @var array
     */
    public $updaterItems = [];

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['submenu'] = 'control-panel';
        $data['title'] = 'updater';
        $data['icon'] = 'fas fa-cloud-download-alt';
        return $data;
    }

    /**
     * Returns FacturaScripts core version.
     * 
     * @return float
     */
    public function getCoreVersion()
    {
        return PluginManager::CORE_VERSION;
    }

    /**
     * 
     * @param Response              $response
     * @param User                  $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->pluginManager = new PluginManager();
        $this->telemetryManager = new TelemetryManager();

        /// Folders writables?
        $folders = FileManager::notWritableFolders();
        if ($folders) {
            $this->toolBox()->i18nLog()->warning('folder-not-writable');
            foreach ($folders as $folder) {
                $this->toolBox()->log()->warning($folder);
            }
            return;
        }

        $action = $this->request->get('action', '');
        $this->execAction($action);
    }

    /**
     * Removed downloaded file.
     */
    private function cancelAction()
    {
        $idItem = $this->request->get('item', '');
        $fileName = 'update-' . $idItem . '.zip';
        if (\file_exists(\FS_FOLDER . DIRECTORY_SEPARATOR . $fileName)) {
            \unlink(\FS_FOLDER . DIRECTORY_SEPARATOR . $fileName);
            $this->toolBox()->i18nLog()->notice('record-deleted-correctly');
        }

        $this->toolBox()->i18nLog()->notice('reloading');
        $this->redirect($this->getClassName() . '?action=post-update', 3);
    }

    /**
     * Download selected update.
     */
    private function downloadAction()
    {
        $idItem = $this->request->get('item', '');
        foreach ($this->updaterItems as $key => $item) {
            if ($item['id'] != $idItem) {
                continue;
            }

            if (\file_exists(\FS_FOLDER . DIRECTORY_SEPARATOR . $item['filename'])) {
                \unlink(\FS_FOLDER . DIRECTORY_SEPARATOR . $item['filename']);
            }

            $downloader = new DownloadTools();
            $url = $this->telemetryManager->signUrl($item['url']);
            if ($downloader->download($url, \FS_FOLDER . DIRECTORY_SEPARATOR . $item['filename'])) {
                $this->toolBox()->i18nLog()->notice('download-completed');
                $this->updaterItems[$key]['downloaded'] = true;
                $this->toolBox()->cache()->clear();
                break;
            }

            $this->toolBox()->i18nLog()->error('download-error');
        }
    }

    /**
     * Execute selected action.
     * 
     * @param string $action
     */
    protected function execAction(string $action)
    {
        switch ($action) {
            case 'cancel':
                $this->cancelAction();
                $this->updaterItems = $this->getUpdateItems();
                break;

            case 'claim-install':
                $this->redirect($this->telemetryManager->claimUrl());
                break;

            case 'download':
                $this->updaterItems = $this->getUpdateItems();
                $this->downloadAction();
                break;

            case 'post-update':
                $this->updaterItems = $this->getUpdateItems();
                Migrations::run();
                $this->pluginManager->deploy(true, true);
                break;

            case 'register':
                if ($this->telemetryManager->install()) {
                    $this->toolBox()->i18nLog()->notice('record-updated-correctly');
                } else {
                    $this->toolBox()->i18nLog()->error('record-save-error');
                }
                $this->updaterItems = $this->getUpdateItems();
                break;

            case 'update':
                if ($this->updateAction()) {
                    $this->pluginManager->deploy(true, false);
                    $this->toolBox()->cache()->clear();
                    $this->toolBox()->i18nLog()->notice('reloading');
                    $this->redirect($this->getClassName() . '?action=post-update', 3);
                }
                break;

            default:
                $this->updaterItems = $this->getUpdateItems();
                break;
        }
    }

    /**
     * 
     * @return array
     */
    private function getUpdateItems(): array
    {
        $cacheData = $this->toolBox()->cache()->get('UPDATE_ITEMS');
        if (\is_array($cacheData)) {
            return $cacheData;
        }

        $downloader = new DownloadTools();
        $json = \json_decode($downloader->getContents(self::UPDATE_CORE_URL), true);
        if (empty($json)) {
            return [];
        }

        $items = [];
        foreach ($json as $projectData) {
            if ($projectData['project'] === self::CORE_PROJECT_ID) {
                $this->getUpdateItemsCore($items, $projectData);
                continue;
            }

            foreach ($this->pluginManager->installedPlugins() as $installed) {
                if ($projectData['name'] === $installed['name']) {
                    $this->getUpdateItemsPlugin($items, $projectData, $installed['version']);
                    break;
                }
            }
        }

        $this->toolBox()->cache()->set('UPDATE_ITEMS', $items);
        return $items;
    }

    /**
     * 
     * @param array $items
     * @param array $projectData
     */
    private function getUpdateItemsCore(array &$items, array $projectData)
    {
        $beta = [];
        $fileName = 'update-' . $projectData['project'] . '.zip';
        foreach ($projectData['builds'] as $build) {
            if ($build['version'] <= $this->getCoreVersion()) {
                continue;
            }

            $item = [
                'description' => $this->toolBox()->i18n()->trans('core-update', ['%version%' => $build['version']]),
                'downloaded' => \file_exists(\FS_FOLDER . DIRECTORY_SEPARATOR . $fileName),
                'filename' => $fileName,
                'id' => $projectData['project'],
                'name' => $projectData['name'],
                'stable' => $build['stable'],
                'url' => self::UPDATE_CORE_URL . '/' . $projectData['project'] . '/' . $build['version']
            ];

            if ($build['stable']) {
                $items[] = $item;
                return;
            }

            if (empty($beta) && $build['beta']) {
                $beta = $item;
            }
        }

        if (!empty($beta)) {
            $items[] = $beta;
        }
    }

    /**
     * 
     * @param array $items
     * @param array $pluginUpdate
     * @param float $installedVersion
     */
    private function getUpdateItemsPlugin(array &$items, array $pluginUpdate, $installedVersion)
    {
        $beta = [];
        $fileName = 'update-' . $pluginUpdate['project'] . '.zip';
        foreach ($pluginUpdate['builds'] as $build) {
            if ($build['version'] <= $installedVersion) {
                continue;
            }

            $item = [
                'description' => $this->toolBox()->i18n()->trans('plugin-update', ['%pluginName%' => $pluginUpdate['name'], '%version%' => $build['version']]),
                'downloaded' => \file_exists(\FS_FOLDER . DIRECTORY_SEPARATOR . $fileName),
                'filename' => $fileName,
                'id' => $pluginUpdate['project'],
                'name' => $pluginUpdate['name'],
                'stable' => $build['stable'],
                'url' => self::UPDATE_CORE_URL . '/' . $pluginUpdate['project'] . '/' . $build['version']
            ];

            if ($build['stable']) {
                $items[] = $item;
                return;
            }

            if (empty($beta) && $build['beta']) {
                $beta = $item;
            }
        }

        if (!empty($beta)) {
            $items[] = $beta;
        }
    }

    /**
     * Extract zip file and update all files.
     * 
     * @return bool
     */
    private function updateAction(): bool
    {
        $idItem = $this->request->get('item', '');
        $fileName = 'update-' . $idItem . '.zip';

        $zip = new ZipArchive();
        $zipStatus = $zip->open(\FS_FOLDER . DIRECTORY_SEPARATOR . $fileName, ZipArchive::CHECKCONS);
        if ($zipStatus !== true) {
            $this->toolBox()->log()->critical('ZIP ERROR: ' . $zipStatus);
            return false;
        }

        return $idItem == self::CORE_PROJECT_ID ? $this->updateCore($zip, $fileName) : $this->updatePlugin($zip, $fileName);
    }

    /**
     * 
     * @param ZipArchive $zip
     * @param string     $fileName
     *
     * @return bool
     */
    private function updateCore($zip, $fileName): bool
    {
        /// extract zip content
        if (false === $zip->extractTo(\FS_FOLDER)) {
            $this->toolBox()->log()->critical('ZIP EXTRACT ERROR: ' . $fileName);
            $zip->close();
            return false;
        }

        /// remove zip file
        $zip->close();
        \unlink(\FS_FOLDER . DIRECTORY_SEPARATOR . $fileName);

        /// update folders
        foreach (['Core', 'node_modules', 'vendor'] as $folder) {
            $origin = \FS_FOLDER . DIRECTORY_SEPARATOR . self::CORE_ZIP_FOLDER . DIRECTORY_SEPARATOR . $folder;
            $dest = \FS_FOLDER . DIRECTORY_SEPARATOR . $folder;
            if (false === \file_exists($origin)) {
                $this->toolBox()->log()->critical('COPY ERROR: ' . $origin);
                return false;
            }

            FileManager::delTree($dest);
            if (false === FileManager::recurseCopy($origin, $dest)) {
                $this->toolBox()->log()->critical('COPY ERROR2: ' . $origin);
                return false;
            }
        }

        /// update files
        $origin = \FS_FOLDER . DIRECTORY_SEPARATOR . self::CORE_ZIP_FOLDER . DIRECTORY_SEPARATOR . 'index.php';
        $dest = \FS_FOLDER . DIRECTORY_SEPARATOR . 'index.php';
        \copy($dest, $origin);

        /// remove zip folder
        FileManager::delTree(\FS_FOLDER . DIRECTORY_SEPARATOR . self::CORE_ZIP_FOLDER);
        return true;
    }

    /**
     * 
     * @param ZipArchive $zip
     * @param string     $fileName
     *
     * @return bool
     */
    private function updatePlugin($zip, $fileName): bool
    {
        $zip->close();

        /// use plugin manager to update
        $return = $this->pluginManager->install($fileName, 'plugin.zip', true);

        /// remove zip file
        \unlink(\FS_FOLDER . DIRECTORY_SEPARATOR . $fileName);
        return $return;
    }
}
