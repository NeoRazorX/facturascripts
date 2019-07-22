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
use FacturaScripts\Dinamic\Model\AttachedFile;
use FacturaScripts\Dinamic\Model\Diario;
use FacturaScripts\Dinamic\Model\IdentificadorFiscal;
use FacturaScripts\Dinamic\Model\LiquidacionComision;
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
    const UPDATE_CORE_URL = 'https://www.facturascripts.com/DownloadBuild';

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
    public function getVersion()
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

        /// Folders writables?
        $folders = FileManager::notWritableFolders();
        if (!empty($folders)) {
            $this->miniLog->alert($this->i18n->trans('folder-not-writable'));
            foreach ($folders as $folder) {
                $this->miniLog->alert($folder);
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
                $this->miniLog->info($this->i18n->trans('download-completed'));
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

            case 'update':
                if ($this->update()) {
                    $pluginManager = new PluginManager();
                    $pluginManager->deploy(true, true);
                    $this->redirect($this->getClassName() . '?action=post-update');
                }
                break;

            default:
                $this->updaterItems = $this->getUpdateItems();
                break;
        }
    }

    private function getUpdateItems(): array
    {
        $cacheData = $this->cache->get('UPDATE_ITEMS');
        if (is_array($cacheData)) {
            return $cacheData;
        }

        $downloader = new DownloadTools();
        $json = json_decode($downloader->getContents(self::UPDATE_CORE_URL), true);
        if (empty($json)) {
            return [];
        }

        $items = [];
        foreach ($json as $projectData) {
            if ($projectData['project'] === self::CORE_PROJECT_ID) {
                $this->getUpdateItemsCore($items, $projectData);
            }
        }

        $this->cache->set('UPDATE_ITEMS', $items);
        return $items;
    }

    private function getUpdateItemsCore(array &$items, array $projectData)
    {
        foreach ($projectData['builds'] as $build) {
            if ($build['stable'] && $build['version'] > PluginManager::CORE_VERSION) {
                $items[] = [
                    'id' => 'CORE',
                    'description' => 'Core component v' . $build['version'],
                    'downloaded' => file_exists(\FS_FOLDER . DIRECTORY_SEPARATOR . 'update-core.zip'),
                    'filename' => 'update-core.zip',
                    'url' => self::UPDATE_CORE_URL . '/' . $projectData['project'] . '/' . $build['version']
                ];
                break;
            }
        }
    }

    private function initNewModels()
    {
        new AttachedFile();
        new Diario();
        new IdentificadorFiscal();
        new LiquidacionComision();
    }

    /**
     * Extract zip file and update all files.
     * 
     * @return bool
     */
    private function update(): bool
    {
        $zip = new ZipArchive();
        $zipStatus = $zip->open(\FS_FOLDER . DIRECTORY_SEPARATOR . 'update-core.zip', ZipArchive::CHECKCONS);
        if ($zipStatus !== true) {
            $this->miniLog->critical('ZIP ERROR: ' . $zipStatus);
            return false;
        }

        $zip->extractTo(\FS_FOLDER);
        $zip->close();
        unlink(\FS_FOLDER . DIRECTORY_SEPARATOR . 'update-core.zip');

        foreach (['Core', 'node_modules', 'vendor'] as $folder) {
            $origin = \FS_FOLDER . DIRECTORY_SEPARATOR . 'facturascripts' . DIRECTORY_SEPARATOR . $folder;
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

        FileManager::delTree(\FS_FOLDER . DIRECTORY_SEPARATOR . 'facturascripts');
        return true;
    }
}
