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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Dinamic\Model\ApiAccess;

/**
 * Controller to edit a single item from the ApiKey model.
 *
 * @author Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 */
class EditApiKey extends EditController
{

    /**
     * @return array
     */
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

    /**
     * Returns the model name.
     *
     * @return string
     */
    public function getModelClassName()
    {
        return 'ApiKey';
    }

    /**
     * Returns basic page attributes.
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'api-key';
        $data['icon'] = 'fas fa-key';
        return $data;
    }

    /**
     * Load views.
     */
    protected function createViews()
    {
        parent::createViews();
        $this->createViewsAccess();
        $this->setTabsPosition('bottom');
    }

    /**
     * @param string $viewName
     */
    protected function createViewsAccess(string $viewName = 'ApiAccess')
    {
        $this->addHtmlView($viewName, 'Tab/ApiAccess', 'ApiAccess', 'rules', 'fas fa-check-square');
    }

    /**
     * @return bool
     */
    protected function editRulesAction(): bool
    {
        // check user permissions
        if (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-update');
            return true;
        } elseif (false === $this->validateFormToken()) {
            return true;
        }

        $allowGet = $this->request->request->get('allowget');
        $allowPut = $this->request->request->get('allowput');
        $allowPost = $this->request->request->get('allowpost');
        $allowDelete = $this->request->request->get('allowdelete');

        // update current access rules
        $accessModel = new ApiAccess();
        $where = [new DataBaseWhere('idapikey', $this->request->query->get('code'))];
        $rules = $accessModel->all($where, [], 0, 0);
        foreach ($rules as $access) {
            $access->allowget = is_array($allowGet) && in_array($access->resource, $allowGet);
            $access->allowput = is_array($allowPut) && in_array($access->resource, $allowPut);
            $access->allowpost = is_array($allowPost) && in_array($access->resource, $allowPost);
            $access->allowdelete = is_array($allowDelete) && in_array($access->resource, $allowDelete);
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
            $newAccess->allowget = is_array($allowGet) && in_array($resource, $allowGet);
            $newAccess->allowput = is_array($allowPut) && in_array($resource, $allowPut);
            $newAccess->allowpost = is_array($allowPost) && in_array($resource, $allowPost);
            $newAccess->allowdelete = is_array($allowDelete) && in_array($resource, $allowDelete);
            $newAccess->save();
        }

        $this->toolBox()->i18nLog()->notice('record-updated-correctly');
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
        switch ($action) {
            case 'edit-rules':
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
                }
                break;
        }
    }
}
