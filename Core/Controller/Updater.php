<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\PluginManager;
use FacturaScripts\Core\Base\TelemetryManager;
use FacturaScripts\Dinamic\Model\AttachedFile;
use FacturaScripts\Dinamic\Model\Diario;
use FacturaScripts\Dinamic\Model\IdentificadorFiscal;
use FacturaScripts\Dinamic\Model\LiquidacionComision;
use FacturaScripts\Dinamic\Model\Retencion;
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
    const UPDATE_CORE_URL = 'https://www.facturascripts.com/DownloadBuild';

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
        if (!empty($folders)) {
            $this->miniLog->warning($this->i18n->trans('folder-not-writable'));
            foreach ($folders as $folder) {
                $this->miniLog->warning($folder);
            }
            return;
        }

        $action = $this->request->get('action', '');
        $this->execAction($action);
    }

    /**
     * Downloads core zip.
     */
    private function download()
    {
        $idItem = $this->request->get('item', '');
        foreach ($this->updaterItems as $key => $item) {
            if ($item['id'] != $idItem) {
                continue;
            }

            if (file_exists(\FS_FOLDER . DIRECTORY_SEPARATOR . $item['filename'])) {
                unlink(\FS_FOLDER . DIRECTORY_SEPARATOR . $item['filename']);
            }

            $downloader = new DownloadTools();
            if ($downloader->download($item['url'], \FS_FOLDER . DIRECTORY_SEPARATOR . $item['filename'])) {
                $this->miniLog->notice($this->i18n->trans('download-completed'));
                $this->updaterItems[$key]['downloaded'] = true;
                $this->cache->clear();
            }
        }
    }

    /**
     * Execute selected action.
     * 
     * @param string $action
     */
    private function execAction(string $action)
    {
        switch ($action) {
            case 'download':
                $this->updaterItems = $this->getUpdateItems();
                $this->download();
                break;

            case 'post-update':
                $this->cache->clear();
                $this->updaterItems = $this->getUpdateItems();
                $this->initNewModels();
                break;
            
            case 'register':
                $this->telemetryManager->install();
                break;

            case 'update':
                if ($this->update()) {
                    $this->pluginManager->deploy(true, true);
                    $this->miniLog->notice($this->i18n->trans('reloading'));
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
        $cacheData = $this->cache->get('UPDATE_ITEMS');
        if (is_array($cacheData)) {
            return $cacheData;
        }

        $downloader = new DownloadTools();
        $json = json_decode($downloader->getContents(self::UPDATE_CORE_URL, 3), true);
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

        $this->cache->set('UPDATE_ITEMS', $items);
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
                'description' => $this->i18n->trans('core-update', ['%version%' => $build['version']]),
                'downloaded' => file_exists(\FS_FOLDER . DIRECTORY_SEPARATOR . $fileName),
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
                'description' => $this->i18n->trans('plugin-update', ['%pluginName%' => $pluginUpdate['name'], '%version%' => $build['version']]),
                'downloaded' => file_exists(\FS_FOLDER . DIRECTORY_SEPARATOR . $fileName),
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

    private function initNewModels()
    {
        new AttachedFile();
        new Diario();
        new IdentificadorFiscal();
        new Retencion();
        new LiquidacionComision();
    }

    /**
     * Extract zip file and update all files.
     * 
     * @return bool
     */
    private function update(): bool
    {
        $idItem = $this->request->get('item', '');
        $fileName = 'update-' . $idItem . '.zip';

        $zip = new ZipArchive();
        $zipStatus = $zip->open(\FS_FOLDER . DIRECTORY_SEPARATOR . $fileName, ZipArchive::CHECKCONS);
        if ($zipStatus !== true) {
            $this->miniLog->critical('ZIP ERROR: ' . $zipStatus);
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
        if (!$zip->extractTo(\FS_FOLDER)) {
            $this->miniLog->critical('ZIP EXTRACT ERROR: ' . $fileName);
            $zip->close();
            return false;
        }

        /// remove zip file
        $zip->close();
        unlink(\FS_FOLDER . DIRECTORY_SEPARATOR . $fileName);

        /// update folders
        foreach (['Core', 'node_modules', 'vendor'] as $folder) {
            $origin = \FS_FOLDER . DIRECTORY_SEPARATOR . self::CORE_ZIP_FOLDER . DIRECTORY_SEPARATOR . $folder;
            $dest = \FS_FOLDER . DIRECTORY_SEPARATOR . $folder;
            if (!file_exists($origin)) {
                $this->miniLog->critical('COPY ERROR: ' . $origin);
                return false;
            }

            FileManager::delTree($dest);
            if (!FileManager::recurseCopy($origin, $dest)) {
                $this->miniLog->critical('COPY ERROR2: ' . $origin);
                return false;
            }
        }

        /// update files
        $origin = \FS_FOLDER . DIRECTORY_SEPARATOR . self::CORE_ZIP_FOLDER . DIRECTORY_SEPARATOR . 'index.php';
        $dest = \FS_FOLDER . DIRECTORY_SEPARATOR . 'index.php';
        copy($dest, $origin);

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
        $return = $this->pluginManager->install($fileName);

        /// remove zip file
        unlink(\FS_FOLDER . DIRECTORY_SEPARATOR . $fileName);
        return $return;
    }
}
