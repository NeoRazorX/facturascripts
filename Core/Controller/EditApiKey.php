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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\ApiAccess;

/**
 * Controller to edit a single item from the ApiKey model.
 *
 * @author Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 */
class EditApiKey extends EditController
{
    public function getAccessRules(): array
    {
        $rules = [];
        foreach ($this->getResources() as $resource) {
            $rules[$resource] = [
                'allowget' => false,
                'allowpost' => false,
                'allowput' => false,
                'allowdelete' => false
            ];
        }

        $accessModel = new ApiAccess();
        $where = [new DataBaseWhere('idapikey', $this->request->query->get('code'))];
        foreach ($accessModel->all($where, [], 0, 0) as $access) {
            $rules[$access->resource]['allowget'] = $access->allowget;
            $rules[$access->resource]['allowpost'] = $access->allowpost;
            $rules[$access->resource]['allowput'] = $access->allowput;
            $rules[$access->resource]['allowdelete'] = $access->allowdelete;
        }

        return $rules;
    }

    public function getModelClassName(): string
    {
        return 'ApiKey';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'api-key';
        $data['icon'] = 'fa-solid fa-key';
        return $data;
    }

    /**
     * Load views.
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        $this->createViewsAccess();
    }

    protected function createViewsAccess(string $viewName = 'ApiAccess')
    {
        $this->addHtmlView($viewName, 'Tab/ApiAccess', 'ApiAccess', 'rules', 'fa-solid fa-check-square');
    }

    protected function editRulesAction(): bool
    {
        // check user permissions
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-update');
            return true;
        } elseif (false === $this->validateFormToken()) {
            return true;
        }

        $allowGet = $this->request->request->getArray('allowget');
        $allowPut = $this->request->request->getArray('allowput');
        $allowPost = $this->request->request->getArray('allowpost');
        $allowDelete = $this->request->request->getArray('allowdelete');

        // update current access rules
        $accessModel = new ApiAccess();
        $where = [new DataBaseWhere('idapikey', $this->request->query->get('code'))];
        $rules = $accessModel->all($where, [], 0, 0);
        foreach ($rules as $access) {
            $access->allowget = in_array($access->resource, $allowGet);
            $access->allowput = in_array($access->resource, $allowPut);
            $access->allowpost = in_array($access->resource, $allowPost);
            $access->allowdelete = in_array($access->resource, $allowDelete);
            $access->save();
        }

        // add new rules
        foreach ($allowGet as $resource) {
            $found = false;
            foreach ($rules as $rule) {
                if ($rule->resource === $resource) {
                    $found = true;
                    break;
                }
            }
            if ($found) {
                continue;
            }

            // add
            $newAccess = new ApiAccess();
            $newAccess->idapikey = $this->request->query->get('code');
            $newAccess->resource = $resource;
            $newAccess->allowget = in_array($resource, $allowGet);
            $newAccess->allowput = in_array($resource, $allowPut);
            $newAccess->allowpost = in_array($resource, $allowPost);
            $newAccess->allowdelete = in_array($resource, $allowDelete);
            $newAccess->save();
        }

        Tools::log()->notice('record-updated-correctly');
        return true;
    }

    /**
     * Run the actions that alter data before reading it.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        if ($action == 'edit-rules') {
            return $this->editRulesAction();
        }

        return parent::execPreviousAction($action);
    }

    /**
     * List of all available resources.
     *
     * @source Based on Core/App/AppAPI.php function getResourcesMap()
     *
     * @return array
     */
    protected function getResources(): array
    {
        $resources = [];

        $path = FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . 'Lib' . DIRECTORY_SEPARATOR . 'API';
        foreach (scandir($path, SCANDIR_SORT_NONE) as $resource) {
            if (substr($resource, -4) !== '.php') {
                continue;
            }

            $class = substr('\\FacturaScripts\\Dinamic\\Lib\\API\\' . $resource, 0, -4);
            $APIClass = new $class($this->response, $this->request, []);
            if (isset($APIClass) && method_exists($APIClass, 'getResources')) {
                foreach ($APIClass->getResources() as $name => $data) {
                    $resources[] = $name;
                }
            }
        }

        // agregamos los recursos custom y de los plugins
        $resources = array_merge($resources, ApiRoot::getCustomResources());

        sort($resources);
        return $resources;
    }

    /**
     * Load view data.
     *
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $mainViewName = $this->getMainViewName();
        switch ($viewName) {
            case $mainViewName:
                parent::loadData($viewName, $view);
                if (false === $view->model->exists()) {
                    $view->model->nick = $this->user->nick;
                } elseif ($view->model->fullaccess) {
                    // si la clave es de acceso total, no se muestran los permisos
                    $this->setSettings('ApiAccess', 'active', false);
                }
                break;
        }
    }
}
