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

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base;
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

    /**
     * @return array
     */
    public function getAllPlugins(): array
    {
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

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
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

    /**
     * Disable the plugin name received.
     *
     * @param string $pluginName
     *
     * @return bool
     */
    private function disablePlugin(string $pluginName): bool
    {
        if (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return false;
        }

        $this->pluginManager->disable($pluginName);
        $this->toolBox()->cache()->clear();
        return true;
    }

    /**
     * Enable the plugin name received.
     *
     * @param string $pluginName
     *
     * @return bool
     */
    private function enablePlugin(string $pluginName): bool
    {
        if (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return false;
        }

        $this->pluginManager->enable($pluginName);
        $this->toolBox()->cache()->clear();
        return true;
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
                $this->disablePlugin($this->request->get('plugin', ''));
                break;

            case 'enable':
                $this->enablePlugin($this->request->get('plugin', ''));
                break;

            case 'rebuild':
                $this->pluginManager->deploy(true, true);
                $this->toolBox()->cache()->clear();
                $this->toolBox()->i18nLog()->notice('rebuild-completed');
                break;

            case 'remove':
                $this->removePlugin($this->request->get('plugin', ''));
                break;

            case 'upload':
                $this->uploadPlugin($this->request->files->get('plugin', []));
                break;

            default:
                if (FS_DEBUG) {
                    // On debug mode, always deploy the contents of Dinamic.
                    $this->pluginManager->deploy(true, true);
                    $this->toolBox()->cache()->clear();
                }
                break;
        }
    }

    /**
     * Remove and disable the plugin name received.
     *
     * @param string $pluginName
     *
     * @return bool
     */
    private function removePlugin(string $pluginName): bool
    {
        if (false === $this->permissions->allowDelete) {
            $this->toolBox()->i18nLog()->warning('not-allowed-delete');
            return false;
        }

        $this->pluginManager->remove($pluginName);
        $this->toolBox()->cache()->clear();
        return true;
    }

    /**
     * Upload and enable a plugin.
     *
     * @param UploadedFile[] $uploadFiles
     */
    private function uploadPlugin(array $uploadFiles)
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
            unlink($uploadFile->getPathname());
        }

        if ($this->pluginManager->deploymentRequired()) {
            $this->toolBox()->cache()->clear();
            $this->toolBox()->i18nLog()->notice('reloading');
            $this->redirect($this->url() . '?action=rebuild', 3);
        }
    }
}
