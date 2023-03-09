<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Cache;
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
     * @var array
     */
    public $coreUpdateWarnings = [];

    /**
     * @var array
     */
    private $forjaJson = [];

    /**
     * @var PluginManager
     */
    private $pluginManager;

    /**
     * @var TelemetryManager
     */
    public $telemetryManager;

    /**
     * @var array
     */
    public $updaterItems = [];

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'updater';
        $data['icon'] = 'fas fa-cloud-download-alt';
        return $data;
    }

    /**
     * Returns FacturaScripts core version.
     *
     * @return float
     */
    public function getCoreVersion(): float
    {
        return PluginManager::CORE_VERSION;
    }

    /**
     * @param Response $response
     * @param User $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->pluginManager = new PluginManager();
        $this->telemetryManager = new TelemetryManager();

        // Folders writable?
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
     * Remove downloaded file.
     */
    private function cancelAction()
    {
        $fileName = 'update-' . $this->request->get('item', '') . '.zip';
        if (file_exists(FS_FOLDER . DIRECTORY_SEPARATOR . $fileName)) {
            unlink(FS_FOLDER . DIRECTORY_SEPARATOR . $fileName);
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
        $this->updaterItems = $this->getUpdateItems();
        foreach ($this->updaterItems as $key => $item) {
            if ($item['id'] != $idItem) {
                continue;
            }

            if (file_exists(FS_FOLDER . DIRECTORY_SEPARATOR . $item['filename'])) {
                unlink(FS_FOLDER . DIRECTORY_SEPARATOR . $item['filename']);
            }

            $downloader = new DownloadTools();
            $url = $this->telemetryManager->signUrl($item['url']);
            if ($downloader->download($url, FS_FOLDER . DIRECTORY_SEPARATOR . $item['filename'])) {
                $this->toolBox()->i18nLog()->notice('download-completed');
                $this->updaterItems[$key]['downloaded'] = true;
                Cache::clear();
                break;
            }

            $this->toolBox()->i18nLog()->error('download-error');
        }

        // ¿Hay que desactivar algo?
        $disable = $this->request->get('disable', '');
        foreach (explode(',', $disable) as $plugin) {
            $this->pluginManager->disable($plugin);
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
                return;

            case 'claim-install':
                $this->redirect($this->telemetryManager->claimUrl());
                return;

            case 'download':
                $this->downloadAction();
                return;

            case 'post-update':
                $this->postUpdateAction();
                break;

            case 'register':
                if ($this->telemetryManager->install()) {
                    $this->toolBox()->i18nLog()->notice('record-updated-correctly');
                    break;
                }
                $this->toolBox()->i18nLog()->error('record-save-error');
                break;

            case 'unlink':
                if ($this->telemetryManager->unlink()) {
                    $this->telemetryManager = new TelemetryManager();
                    $this->toolBox()->i18nLog()->notice('unlink-install-ok');
                    break;
                }
                $this->toolBox()->i18nLog()->error('unlink-install-ko');
                break;

            case 'update':
                $this->updateAction();
                return;
        }

        $this->updaterItems = $this->getUpdateItems();
        $this->setCoreWarnings();
    }

    private function getUpdateItems(): array
    {
        $downloader = new DownloadTools();
        $this->forjaJson = json_decode($downloader->getContents(self::UPDATE_CORE_URL), true);
        if (empty($this->forjaJson)) {
            return [];
        }

        $items = [];
        foreach ($this->forjaJson as $projectData) {
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

        Cache::set('UPDATE_ITEMS', $items);
        return $items;
    }

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
                'downloaded' => file_exists(FS_FOLDER . DIRECTORY_SEPARATOR . $fileName),
                'filename' => $fileName,
                'id' => $projectData['project'],
                'name' => $projectData['name'],
                'stable' => $build['stable'],
                'url' => self::UPDATE_CORE_URL . '/' . $projectData['project'] . '/' . $build['version'],
                'version' => $build['version'],
                'mincore' => 0,
                'maxcore' => 0
            ];

            if ($build['stable']) {
                $items[] = $item;
                return;
            }

            if (empty($beta) && $build['beta'] && ToolBox::appSettings()->get('default', 'enableupdatesbeta', false)) {
                $beta = $item;
            }
        }

        if (!empty($beta)) {
            $items[] = $beta;
        }
    }

    private function getUpdateItemsPlugin(array &$items, array $pluginUpdate, float $installedVersion)
    {
        $beta = [];
        $fileName = 'update-' . $pluginUpdate['project'] . '.zip';
        foreach ($pluginUpdate['builds'] as $build) {
            if ($build['version'] <= $installedVersion) {
                continue;
            }

            $item = [
                'description' => $this->toolBox()->i18n()->trans('plugin-update', ['%pluginName%' => $pluginUpdate['name'], '%version%' => $build['version']]),
                'downloaded' => file_exists(FS_FOLDER . DIRECTORY_SEPARATOR . $fileName),
                'filename' => $fileName,
                'id' => $pluginUpdate['project'],
                'name' => $pluginUpdate['name'],
                'stable' => $build['stable'],
                'url' => self::UPDATE_CORE_URL . '/' . $pluginUpdate['project'] . '/' . $build['version'],
                'version' => $build['version'],
                'mincore' => $build['mincore'],
                'maxcore' => $build['maxcore']
            ];

            if ($build['stable']) {
                $items[] = $item;
                return;
            }

            if (empty($beta) && $build['beta'] && ToolBox::appSettings()->get('default', 'enableupdatesbeta', false)) {
                $beta = $item;
            }
        }

        if (!empty($beta)) {
            $items[] = $beta;
        }
    }

    private function postUpdateAction()
    {
        $plugName = $this->request->get('init', '');
        if ($plugName) {
            // run Init::update() when plugin is updated
            $this->pluginManager->initPlugin($plugName);
            $this->pluginManager->deploy(true, true);
            return;
        }

        Migrations::run();
        $this->pluginManager->deploy(true, true);
    }

    private function setCoreWarnings()
    {
        // comprobamos si hay actualización del core
        $newCore = 0;
        foreach ($this->updaterItems as $item) {
            if ($item['id'] === self::CORE_PROJECT_ID) {
                $newCore = $item['version'];
                break;
            }
        }
        if (empty($newCore)) {
            return;
        }

        // comprobamos los plugins instalados
        foreach ($this->pluginManager->installedPlugins() as $plugin) {
            // ¿El plugin está activo?
            if (false === $plugin['enabled']) {
                continue;
            }

            // ¿Funcionará con el nuevo core?
            if ($this->willItWorkOnNewCore($plugin, $newCore)) {
                continue;
            }

            // ¿Hay actualización para el nuevo core?
            if ($this->willPluginNeedUpdate($plugin, $newCore)) {
                $this->coreUpdateWarnings[$plugin['name']] = self::toolBox()::i18n()->trans('plugin-need-update', ['%plugin%' => $plugin['name']]);
                continue;
            }

            $this->coreUpdateWarnings[$plugin['name']] = self::toolBox()::i18n()->trans('plugin-need-update-but', ['%plugin%' => $plugin['name']]);
        }
    }

    /**
     * Extract zip file and update all files.
     */
    private function updateAction()
    {
        $idItem = $this->request->get('item', '');
        $fileName = 'update-' . $idItem . '.zip';

        // open the zip file
        $zip = new ZipArchive();
        $zipStatus = $zip->open(FS_FOLDER . DIRECTORY_SEPARATOR . $fileName, ZipArchive::CHECKCONS);
        if ($zipStatus !== true) {
            $this->toolBox()->log()->critical('ZIP ERROR: ' . $zipStatus);
            return;
        }

        // get the name of the plugin to init after update (if the plugin is enabled)
        $init = '';
        foreach ($this->getUpdateItems() as $item) {
            if ($idItem == self::CORE_PROJECT_ID) {
                break;
            }

            if ($item['id'] == $idItem && in_array($item['name'], $this->pluginManager->enabledPlugins())) {
                $init = $item['name'];
                break;
            }
        }

        // extract core/plugin zip file
        $done = ($idItem == self::CORE_PROJECT_ID) ? $this->updateCore($zip, $fileName) : $this->updatePlugin($zip, $fileName);
        if ($done) {
            $this->pluginManager->deploy(true, false);
            Cache::clear();
            $this->toolBox()->i18nLog()->notice('reloading');
            $this->redirect($this->getClassName() . '?action=post-update&init=' . $init, 3);
        }
    }

    private function updateCore(ZipArchive $zip, string $fileName): bool
    {
        // extract zip content
        if (false === $zip->extractTo(FS_FOLDER)) {
            $this->toolBox()->log()->critical('ZIP EXTRACT ERROR: ' . $fileName);
            $zip->close();
            return false;
        }

        // remove zip file
        $zip->close();
        unlink(FS_FOLDER . DIRECTORY_SEPARATOR . $fileName);

        // update folders
        foreach (['Core', 'node_modules', 'vendor'] as $folder) {
            $origin = FS_FOLDER . DIRECTORY_SEPARATOR . self::CORE_ZIP_FOLDER . DIRECTORY_SEPARATOR . $folder;
            $dest = FS_FOLDER . DIRECTORY_SEPARATOR . $folder;
            if (false === file_exists($origin)) {
                $this->toolBox()->log()->critical('COPY ERROR: ' . $origin);
                return false;
            }

            FileManager::delTree($dest);
            if (false === FileManager::recurseCopy($origin, $dest)) {
                $this->toolBox()->log()->critical('COPY ERROR2: ' . $origin);
                return false;
            }
        }

        // update files
        $origin = FS_FOLDER . DIRECTORY_SEPARATOR . self::CORE_ZIP_FOLDER . DIRECTORY_SEPARATOR . 'index.php';
        $dest = FS_FOLDER . DIRECTORY_SEPARATOR . 'index.php';
        copy($origin, $dest);

        // remove zip folder
        FileManager::delTree(FS_FOLDER . DIRECTORY_SEPARATOR . self::CORE_ZIP_FOLDER);
        return true;
    }

    private function updatePlugin(ZipArchive $zip, string $fileName): bool
    {
        $zip->close();

        // use plugin manager to update
        $return = $this->pluginManager->install($fileName, 'plugin.zip', true);

        // remove zip file
        unlink(FS_FOLDER . DIRECTORY_SEPARATOR . $fileName);
        return $return;
    }

    private function willItWorkOnNewCore(array $plugin, float $newCore): bool
    {
        // buscamos información del plugin en la forja
        foreach ($this->forjaJson as $item) {
            if ($item['name'] != $plugin['name']) {
                continue;
            }

            // buscamos la versión que hay instalada
            foreach ($item['builds'] as $build) {
                if ($build['version'] == $plugin['version']) {
                    // si soporta un core mayor o igual al que estamos actualizando, entonces funcionará
                    return $build['maxcore'] >= $newCore;
                }
            }
        }

        return false;
    }

    private function willPluginNeedUpdate(array $plugin, float $newCore): bool
    {
        // buscamos información del plugin en la forja
        foreach ($this->forjaJson as $item) {
            if ($item['name'] === $plugin['name']) {
                // si soporta un core mayor o igual al que estamos actualizando, entonces funcionará
                return $item['maxcore'] >= $newCore;
            }
        }

        return false;
    }
}
