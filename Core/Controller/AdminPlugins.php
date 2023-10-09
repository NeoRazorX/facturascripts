<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Internal\Forja;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Dinamic\Model\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

/**
 * AdminPlugins.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AdminPlugins extends Controller
{
    /** @var array */
    public $pluginList = [];

    /** @var array */
    public $remotePluginList = [];

    public function getMaxFileUpload(): float
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
     * Runs the controller's private logic.
     *
     * @param Response $response
     * @param User $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        $action = $this->request->get('action', '');
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
                    Plugins::deploy(true, true);
                    Cache::clear();
                }
                break;
        }

        $this->pluginList = Plugins::list();
        $this->loadRemotePluginList();
    }

    private function disablePluginAction(): void
    {
        if (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return;
        } elseif (false === $this->validateFormToken()) {
            return;
        }

        $pluginName = $this->request->get('plugin', '');
        Plugins::disable($pluginName);
        Cache::clear();
    }

    private function enablePluginAction(): void
    {
        if (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return;
        } elseif (false === $this->validateFormToken()) {
            return;
        }

        $pluginName = $this->request->get('plugin', '');
        Plugins::enable($pluginName);
        Cache::clear();
    }

    private function loadRemotePluginList(): void
    {
        if (defined('FS_DISABLE_ADD_PLUGINS') && FS_DISABLE_ADD_PLUGINS) {
            return;
        }

        $installedPlugins = Plugins::list();
        foreach (Forja::plugins() as $item) {
            // plugin is already installed?
            foreach ($installedPlugins as $plugin) {
                if ($plugin->name == $item['name']) {
                    continue 2;
                }
            }

            $this->remotePluginList[] = $item;
        }
    }

    private function rebuildAction(): void
    {
        if (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-update');
            return;
        } elseif (false === $this->validateFormToken()) {
            return;
        }

        Plugins::deploy(true, true);
        Cache::clear();
        $this->toolBox()->i18nLog()->notice('rebuild-completed');
    }

    private function removePluginAction(): void
    {
        if (false === $this->permissions->allowDelete) {
            $this->toolBox()->i18nLog()->warning('not-allowed-delete');
            return;
        } elseif (false === $this->validateFormToken()) {
            return;
        }

        $pluginName = $this->request->get('plugin', '');
        Plugins::remove($pluginName);
        Cache::clear();
    }

    private function uploadPluginAction(): void
    {
        if (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-update');
            return;
        } elseif (false === $this->validateFormToken()) {
            return;
        }

        $ok = true;
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

            if (false === Plugins::add($uploadFile->getPathname(), $uploadFile->getClientOriginalName())) {
                $ok = false;
            }
            unlink($uploadFile->getPathname());
        }

        Cache::clear();
        if ($ok) {
            $this->toolBox()->i18nLog()->notice('reloading');
            $this->redirect($this->url(), 3);
        }
    }
}
