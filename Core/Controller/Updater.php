<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\FileManager;
use FacturaScripts\Core\Base\Migrations;
use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Http;
use FacturaScripts\Core\Internal\Forja;
use FacturaScripts\Core\Internal\Plugin;
use FacturaScripts\Core\Kernel;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Telemetry;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\User;
use ZipArchive;

/**
 * Description of Updater
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Updater extends Controller
{
    const CORE_ZIP_FOLDER = 'facturascripts';
    const UPDATE_CORE_URL = 'https://facturascripts.com/DownloadBuild';

    /** @var array */
    public $coreUpdateWarnings = [];

    /** @var Telemetry */
    public $telemetryManager;

    /** @var array */
    public $updaterItems = [];

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'updater';
        $data['icon'] = 'fa-solid fa-cloud-download-alt';
        return $data;
    }

    public static function getCoreVersion(): float
    {
        return Kernel::version();
    }

    public static function getUpdateItems(): array
    {
        $items = [];

        // comprobamos si se puede actualizar el core
        if (Forja::canUpdateCore()) {
            $item = self::getUpdateItemsCore();
            if (!empty($item)) {
                $items[] = $item;
            }
        }

        // comprobamos si se puede actualizar algún plugin
        foreach (Plugins::list() as $plugin) {
            $item = self::getUpdateItemsPlugin($plugin);
            if (!empty($item)) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * @param Response $response
     * @param User $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        $this->telemetryManager = new Telemetry();

        // Folders writable?
        $folders = FileManager::notWritableFolders();
        if ($folders) {
            Tools::log()->warning('folder-not-writable');
            foreach ($folders as $folder) {
                Tools::log()->warning($folder);
            }
            return;
        }

        $action = $this->request->get('action', '');
        $this->execAction($action);
    }

    /**
     * Remove downloaded file.
     */
    private function cancelAction(): void
    {
        $fileName = 'update-' . $this->request->get('item', '') . '.zip';
        if (file_exists(Tools::folder($fileName))) {
            unlink(Tools::folder($fileName));
            Tools::log()->notice('record-deleted-correctly');
        }

        Tools::log()->notice('reloading');
        $this->redirect($this->getClassName() . '?action=post-update', 3);
    }

    /**
     * Download selected update.
     */
    private function downloadAction(): void
    {
        $idItem = $this->request->get('item', '');
        $this->updaterItems = self::getUpdateItems();
        foreach ($this->updaterItems as $key => $item) {
            if ($item['id'] != $idItem) {
                continue;
            }

            if (file_exists(Tools::folder($item['filename']))) {
                unlink(Tools::folder($item['filename']));
            }

            $url = $this->telemetryManager->signUrl($item['url']);
            $http = Http::get($url);
            if ($http->saveAs(Tools::folder($item['filename']))) {
                Tools::log()->notice('download-completed');
                $this->updaterItems[$key]['downloaded'] = true;
                break;
            }

            Tools::log()->error('download-error', [
                '%body%' => $http->body(),
                '%error%' => $http->errorMessage(),
                '%status%' => $http->status(),
            ]);
        }

        // ¿Hay que desactivar algo?
        $disable = $this->request->get('disable', '');
        foreach (explode(',', $disable) as $plugin) {
            Plugins::disable($plugin);
        }
    }

    protected function execAction(string $action): void
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
                    Tools::log()->notice('record-updated-correctly');
                    break;
                }
                Tools::log()->error('record-save-error');
                break;

            case 'unlink':
                if ($this->telemetryManager->unlink()) {
                    $this->telemetryManager = new Telemetry();
                    Tools::log()->notice('unlink-install-ok');
                    break;
                }
                Tools::log()->error('unlink-install-ko');
                break;

            case 'update':
                $this->updateAction();
                return;
        }

        $this->updaterItems = self::getUpdateItems();
        $this->setCoreWarnings();
    }

    private static function getUpdateItemsCore(): array
    {
        $fileName = 'update-' . Forja::CORE_PROJECT_ID . '.zip';
        foreach (Forja::getBuilds(Forja::CORE_PROJECT_ID) as $build) {
            if ($build['version'] <= self::getCoreVersion()) {
                continue;
            }

            $item = [
                'description' => Tools::lang()->trans('core-update', ['%version%' => $build['version']]),
                'downloaded' => file_exists(Tools::folder($fileName)),
                'filename' => $fileName,
                'id' => Forja::CORE_PROJECT_ID,
                'name' => 'CORE',
                'stable' => $build['stable'],
                'url' => self::UPDATE_CORE_URL . '/' . Forja::CORE_PROJECT_ID . '/' . $build['version'],
                'version' => $build['version'],
                'mincore' => 0,
                'maxcore' => 0
            ];

            if ($build['stable']) {
                return $item;
            }

            if ($build['beta'] && Tools::settings('default', 'enableupdatesbeta', false)) {
                return $item;
            }
        }

        return [];
    }

    private static function getUpdateItemsPlugin(Plugin $plugin): array
    {
        $id = $plugin->forja('idplugin', 0);
        $fileName = 'update-' . $id . '.zip';
        foreach (Forja::getBuilds($id) as $build) {
            if ($build['version'] <= $plugin->version) {
                continue;
            }

            $item = [
                'description' => Tools::lang()->trans('plugin-update', [
                    '%pluginName%' => $plugin->name,
                    '%version%' => $build['version']
                ]),
                'downloaded' => file_exists(Tools::folder($fileName)),
                'filename' => $fileName,
                'id' => $id,
                'name' => $plugin->name,
                'stable' => $build['stable'],
                'url' => self::UPDATE_CORE_URL . '/' . $id . '/' . $build['version'],
                'version' => $build['version'],
                'mincore' => $build['mincore'],
                'maxcore' => $build['maxcore']
            ];

            if ($build['stable']) {
                return $item;
            }

            if ($build['beta'] && Tools::settings('default', 'enableupdatesbeta', false)) {
                return $item;
            }
        }

        return [];
    }

    private function postUpdateAction(): void
    {
        $plugName = $this->request->get('init', '');
        if ($plugName) {
            Plugins::deploy(true, true);
            return;
        }

        Migrations::run();
        Plugins::deploy(true, true);
    }

    private function setCoreWarnings(): void
    {
        // comprobamos si hay actualización del core
        $newCore = 0;
        foreach ($this->updaterItems as $item) {
            if ($item['id'] === Forja::CORE_PROJECT_ID) {
                $newCore = $item['version'];
                break;
            }
        }
        if (empty($newCore)) {
            return;
        }

        // comprobamos los plugins instalados
        foreach (Plugins::list() as $plugin) {
            // ¿El plugin está activo?
            if (false === $plugin->enabled) {
                continue;
            }

            // ¿Funcionará con el nuevo core?
            if ($this->willItWorkOnNewCore($plugin, $newCore)) {
                continue;
            }

            // ¿Hay actualización para el nuevo core?
            if ($plugin->forja('maxcore', 0) >= $newCore) {
                $this->coreUpdateWarnings[$plugin->name] = Tools::lang()->trans('plugin-need-update', [
                    '%plugin%' => $plugin->name
                ]);
                continue;
            }

            $this->coreUpdateWarnings[$plugin->name] = Tools::lang()->trans('plugin-need-update-but', [
                '%plugin%' => $plugin->name
            ]);
        }
    }

    /**
     * Extract zip file and update all files.
     */
    private function updateAction(): void
    {
        $idItem = $this->request->get('item', '');
        $fileName = 'update-' . $idItem . '.zip';

        // open the zip file
        $zip = new ZipArchive();
        $zipStatus = $zip->open(Tools::folder($fileName), ZipArchive::CHECKCONS);
        if ($zipStatus !== true) {
            Tools::log()->critical('ZIP ERROR: ' . $zipStatus);
            return;
        }

        // get the name of the plugin to init after update (if the plugin is enabled)
        $init = '';
        foreach (self::getUpdateItems() as $item) {
            if ($idItem == Forja::CORE_PROJECT_ID) {
                break;
            }

            if ($item['id'] == $idItem && Plugins::isEnabled($item['name'])) {
                $init = $item['name'];
                break;
            }
        }

        // extract core/plugin zip file
        $done = ($idItem == Forja::CORE_PROJECT_ID) ?
            $this->updateCore($zip, $fileName) :
            $this->updatePlugin($zip, $fileName);

        if ($done) {
            Plugins::deploy(true, false);
            Cache::clear();
            $this->setTemplate(false);
            $this->redirect($this->getClassName() . '?action=post-update&init=' . $init, 3);
        }
    }

    private function updateCore(ZipArchive $zip, string $fileName): bool
    {
        // extract zip content
        if (false === $zip->extractTo(FS_FOLDER)) {
            Tools::log()->critical('ZIP EXTRACT ERROR: ' . $fileName);
            $zip->close();
            return false;
        }

        // remove zip file
        $zip->close();
        unlink(Tools::folder($fileName));

        // update folders
        foreach (['Core', 'node_modules', 'vendor'] as $folder) {
            $origin = Tools::folder(self::CORE_ZIP_FOLDER, $folder);
            $dest = Tools::folder($folder);
            if (false === file_exists($origin)) {
                Tools::log()->critical('COPY ERROR: ' . $origin);
                return false;
            }

            FileManager::delTree($dest);
            if (false === FileManager::recurseCopy($origin, $dest)) {
                Tools::log()->critical('COPY ERROR2: ' . $origin);
                return false;
            }
        }

        // update files
        foreach (['index.php', 'replace_index_to_restore.php'] as $name) {
            $origin = Tools::folder(self::CORE_ZIP_FOLDER, $name);
            $dest = Tools::folder($name);
            copy($origin, $dest);
        }

        // remove zip folder
        FileManager::delTree(Tools::folder(self::CORE_ZIP_FOLDER));
        return true;
    }

    private function updatePlugin(ZipArchive $zip, string $fileName): bool
    {
        $zip->close();

        // use plugin manager to update
        $return = Plugins::add($fileName, 'plugin.zip', true);

        // remove zip file
        unlink(Tools::folder($fileName));
        return $return;
    }

    private function willItWorkOnNewCore(Plugin $plugin, float $newCore): bool
    {
        // buscamos información del plugin en la forja
        foreach (Forja::getBuildsByName($plugin->name) as $build) {
            if ($build['version'] == $plugin->version) {
                // si soporta un core mayor o igual al que estamos actualizando, entonces funcionará
                return $build['maxcore'] >= $newCore;
            }
        }

        return false;
    }
}
