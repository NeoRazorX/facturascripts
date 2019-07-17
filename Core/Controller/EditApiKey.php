<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use Exception;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Dinamic\Model;

/**
 * Controller to edit a single item from the ApiKey model.
 *
 * @author Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 */
class EditApiKey extends EditController
{

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
     * Add the indicated resource list to the api key.
     *
     * @param int   $idApiKey
     * @param array $apiAccess
     * @param bool  $state
     *
     * @throws Exception
     */
    protected function addResourcesToApiKey($idApiKey, $apiAccess, $state = false)
    {
        // add Pages to Rol
        if (!Model\ApiAccess::addResourcesToApiKey($idApiKey, $apiAccess, $state)) {
            throw new \Exception($this->i18n->trans('cancel-process'));
        }
    }

    /**
     * Load views.
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        $this->addEditListView('EditApiAccess', 'ApiAccess', 'rules', 'fas fa-check-square');

        /// settings
        $this->views['EditApiAccess']->settings['btnNew'] = false;
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
            case 'add-api-access-enabled':
                return $this->addResourcesWith(true);

            case 'add-api-access-disabled':
                return $this->addResourcesWith();

            default:
                return parent::execPreviousAction($action);
        }
    }

    /**
     * Add the indicated resource list to the api key.
     *
     * @param bool $state
     *
     * @return bool
     */
    protected function addResourcesWith($state = false)
    {
        $idApiKey = $this->request->get('code', '');
        $resources = $this->getResources();
        if (empty($resources) || empty($idApiKey)) {
            return true;
        }

        $this->dataBase->beginTransaction();
        try {
            $this->addResourcesToApiKey((int) $idApiKey, $resources, $state);
            $this->dataBase->commit();
        } catch (Exception $e) {
            $this->dataBase->rollback();
            $this->miniLog->notice($e->getMessage());
        }

        return true;
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
            if (substr($resource, -4) === '.php') {
                $class = substr('FacturaScripts\\Dinamic\\Lib\\API\\' . $resource, 0, -4);
                $APIClass = new $class($this->response, $this->request, $this->miniLog, $this->i18n, []);
                if (isset($APIClass) && method_exists($APIClass, 'getResources')) {
                    foreach ($APIClass->getResources() as $name => $data) {
                        $resources[] = $name;
                    }
                }
                unset($APIClass);
            }
        }

        sort($resources);
        return $resources;
    }

    /**
     * Load view data.
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'EditApiAccess':
                $idApiKey = $this->getViewModelValue($this->getMainViewName(), 'id');
                $where = [new DataBaseWhere('idapikey', $idApiKey)];
                $view->loadData('', $where, ['resource' => 'ASC'], 0, 0);
                if (!$this->views[$this->active]->model->exists()) {
                    $this->views[$this->active]->model->nick = $this->user->nick;
                }
                break;

            default:
                parent::loadData($viewName, $view);
        }
    }
}
