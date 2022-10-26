<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Cache;
use FacturaScripts\Dinamic\Model\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

/**
 * AdminPlugins.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AdminPlugins extends Base\Controller
{
    const PLUGIN_LIST_URL = 'https://facturascripts.com/PluginInfoList';

    /**
     * Plugin Manager.
     *
     * @var Base\PluginManager
     */
    public $pluginManager;

    public function getAllPlugins(): array
    {
        if (FS_DISABLE_ADD_PLUGINS) {
            return [];
        }

        $downloadTools = new Base\DownloadTools();
        $json = json_decode($downloadTools->getContents(self::PLUGIN_LIST_URL, 3), true);
        if (empty($json)) {
            return [];
        }

        $list = [];
        foreach ($json as $item) {
            // plugin is already installed?
            $item['installed'] = false;
            foreach ($this->getPlugins() as $plug) {
                if ($plug['name'] == $item['name']) {
                    $item['installed'] = true;
                    break;
                }
            }

            $list[] = $item;
        }

        return $list;
    }

    /**
     * Return the max file size that can be uploaded.
     *
     * @return float
     */
    public function getMaxFileUpload()
    {
        return UploadedFile::getMaxFilesize() / 1024 / 1024;
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'plugins';
        $data['icon'] = 'fas fa-plug';
        return $data;
    }

    /**
     * Return installed plugins without hidden ones.
     *
     * @return array
     */
    public function getPlugins(): array
    {
        $installedPlugins = $this->pluginManager->installedPlugins();
        if (false === defined('FS_HIDDEN_PLUGINS')) {
            return $installedPlugins;
        }

        // exclude hidden plugins
        $hiddenPlugins = explode(',', FS_HIDDEN_PLUGINS);
        foreach ($installedPlugins as $key => $plugin) {
            if (in_array($plugin['name'], $hiddenPlugins, false)) {
                unset($installedPlugins[$key]);
            }
        }
        return $installedPlugins;
    }

    /**
     * Runs the controller's private logic.
     *
     * @param Response $response
     * @param User $user
     * @param Base\ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->pluginManager = new Base\PluginManager();

        $action = $this->request->get('action', '');
        $this->execAction($action);
    }

    private function disablePluginAction(): void
    {
        if (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return;
        }

        $pluginName = $this->request->get('plugin', '');
        $this->pluginManager->disable($pluginName);
        Cache::clear();
    }

    private function enablePluginAction(): void
    {
        if (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return;
        }

        $pluginName = $this->request->get('plugin', '');
        $this->pluginManager->enable($pluginName);
        Cache::clear();
    }

    /**
     * Execute main actions.
     *
     * @param string $action
     */
    private function execAction(string $action)
    {
        switch ($action) {
            case 'disable':
                $this->disablePluginAction();
                break;

            case 'enable':
                $this->enablePluginAction();
                break;

            case 'rebuild':
                $this->rebuildAction();
                break;

            case 'remove':
                $this->removePluginAction();
                break;

            case 'upload':
                $this->uploadPluginAction();
                break;

            default:
                if (FS_DEBUG) {
                    // On debug mode, always deploy the contents of Dinamic.
                    $this->pluginManager->deploy(true, true);
                    Cache::clear();
                }
                break;
        }
    }

    private function rebuildAction(): void
    {
        $this->pluginManager->deploy(true, true);

        $init = $this->request->query->get('init', '');
        foreach (explode(',', $init) as $name) {
            $this->pluginManager->initPlugin($name);
        }

        Cache::clear();
        $this->toolBox()->i18nLog()->notice('rebuild-completed');
    }

    private function removePluginAction(): void
    {
        if (false === $this->permissions->allowDelete) {
            $this->toolBox()->i18nLog()->warning('not-allowed-delete');
            return;
        }

        $pluginName = $this->request->get('plugin', '');
        $this->pluginManager->remove($pluginName);
        Cache::clear();
    }

    private function uploadPluginAction(): void
    {
        // check user permissions
        if (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-update');
            return;
        }

        // valid request?
        $token = $this->request->request->get('multireqtoken', '');
        if (empty($token) || false === $this->multiRequestProtection->validate($token)) {
            $this->toolBox()->i18nLog()->warning('invalid-request');
            return;
        }

        // duplicated request?
        if ($this->multiRequestProtection->tokenExist($token)) {
            $this->toolBox()->i18nLog()->warning('duplicated-request');
            return;
        }

        $pluginNames = [];
        $uploadFiles = $this->request->files->get('plugin', []);
        foreach ($uploadFiles as $uploadFile) {
            if (false === $uploadFile->isValid()) {
                $this->toolBox()->log()->error($uploadFile->getErrorMessage());
                continue;
            }

            if ($uploadFile->getMimeType() !== 'application/zip') {
                $this->toolBox()->i18nLog()->error('file-not-supported');
                continue;
            }

            $this->pluginManager->install($uploadFile->getPathname(), $uploadFile->getClientOriginalName());
            $pluginNames[] = $this->pluginManager->getLastPluginName();
            unlink($uploadFile->getPathname());
        }

        if ($this->pluginManager->deploymentRequired()) {
            Cache::clear();
            $this->toolBox()->i18nLog()->notice('reloading');
            $this->redirect($this->url() . '?action=rebuild&init=' . implode(',', $pluginNames), 3);
        }
    }
}
