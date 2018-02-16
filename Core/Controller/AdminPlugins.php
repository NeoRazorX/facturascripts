<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
use FacturaScripts\Core\Model\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * AdminPlugins.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AdminPlugins extends Base\Controller
{

    /**
     * Plugin Manager.
     *
     * @var Base\PluginManager
     */
    public $pluginManager;

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
        $pageData = parent::getPageData();
        $pageData['menu'] = 'admin';
        $pageData['submenu'] = 'control-panel';
        $pageData['title'] = 'plugins';
        $pageData['icon'] = 'fa-plug';

        return $pageData;
    }

    /**
     * Runs the controller's private logic.
     *
     * @param Response                      $response
     * @param User                          $user
     * @param Base\ControllerPermissions    $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        /// For now, always deploy the contents of Dinamic, for testing purposes
        $this->pluginManager = new Base\PluginManager();
        $this->pluginManager->deploy();
        $this->cache->clear();

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
    private function disablePlugin($pluginName)
    {
        if (!$this->permissions->allowUpdate) {
            $this->miniLog->alert($this->i18n->trans('not-allowed-modify'));
            return false;
        }

        $this->pluginManager->disable($pluginName);
        return true;
    }

    /**
     * Enable the plugin name received.
     *
     * @param string $pluginName
     *
     * @return bool
     */
    private function enablePlugin($pluginName)
    {
        if (!$this->permissions->allowUpdate) {
            $this->miniLog->alert($this->i18n->trans('not-allowed-modify'));
            return false;
        }

        $this->pluginManager->enable($pluginName);
        return true;
    }

    /**
     * Execute main actions.
     *
     * @param $action
     */
    private function execAction($action)
    {
        switch ($action) {
            case 'disable':
                $this->disablePlugin($this->request->get('plugin', ''));
                break;

            case 'enable':
                $this->enablePlugin($this->request->get('plugin', ''));
                break;

            case 'remove':
                $this->removePlugin($this->request->get('plugin', ''));
                break;

            case 'upload':
                $this->uploadPlugin($this->request->files->get('plugin', []));
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
    private function removePlugin($pluginName)
    {
        if (!$this->permissions->allowDelete) {
            $this->miniLog->alert($this->i18n->trans('not-allowed-delete'));
            return false;
        }

        $this->pluginManager->remove($pluginName);
        return true;
    }

    /**
     * Upload and enable a plugin.
     *
     * @param UploadedFile[] $uploadFiles
     */
    private function uploadPlugin($uploadFiles)
    {
        foreach ($uploadFiles as $uploadFile) {
            if (!$uploadFile->isValid()) {
                $this->miniLog->error($uploadFile->getErrorMessage());
                continue;
            }

            if ($uploadFile->getMimeType() !== 'application/zip') {
                $this->miniLog->error($this->i18n->trans('file-not-supported'));
                continue;
            }

            $this->pluginManager->install($uploadFile->getPathname(), $uploadFile->getClientOriginalName());
            unlink($uploadFile->getPathname());
        }
    }
}
