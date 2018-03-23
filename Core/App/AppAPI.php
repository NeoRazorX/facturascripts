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
namespace FacturaScripts\Core\App;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use Symfony\Component\HttpFoundation\Response;

/**
 * AppAPI is the class used for API.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AppAPI extends App
{

    /**
     * Runs the API.
     *
     * @return bool
     */
    public function run()
    {
        $this->response->headers->set('Access-Control-Allow-Origin', '*');
        $this->response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE');
        $this->response->headers->set('Content-Type', 'application/json');

        if ($this->isDisabled()) {
            $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
            $this->response->setContent(json_encode(['error' => 'API-DISABLED']));

            return false;
        }

        if (!$this->dataBase->connected()) {
            $this->response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->response->setContent(json_encode(['error' => 'DB-ERROR']));

            return false;
        }

        if ($this->isIPBanned()) {
            $this->response->setStatusCode(Response::HTTP_FORBIDDEN);
            $this->response->setContent(json_encode(['error' => 'IP-BANNED']));

            return false;
        }

        return $this->selectVersion();
    }

    /**
     * Check if API is disabled
     *
     * @return mixed
     */
    private function isDisabled()
    {
        return $this->settings->get('default', 'enable_api', false) !== 'true';
    }

    /**
     * Selects the API version if it is supported
     *
     * @return bool
     */
    private function selectVersion()
    {
        if ($this->getUriParam(1) === '3') {
            return $this->selectResource();
        }

        $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
        $this->response->setContent(json_encode(['error' => 'API-VERSION-NOT-FOUND']));

        return true;
    }

    /**
     * Selects the resource
     *
     * @return bool
     */
    private function selectResource()
    {
        $map = $this->getResourcesMap();

        $resourceName = $this->getUriParam(2);
        if ($resourceName === '') {
            $this->exposeResources($map);

            return true;
        }

        $modelName = 'FacturaScripts\\Dinamic\\Model\\' . $map[$resourceName];
        $cod = $this->getUriParam(3);

        if ($cod === '') {
            return $this->processResource($modelName);
        }

        return $this->processResourceParam($modelName, $cod);
    }

    /**
     * This method is equivalent to $this->request->get($key, $default),
     * but always return an array, as expected for some parameters like operation, filter or sort.
     *
     * @param string $key
     * @param string $default
     *
     * @return array
     */
    private function getRequestArray($key, $default = '')
    {
        $array = $this->request->get($key, $default);

        return is_array($array) ? $array : []; /// if is string has bad format
    }

    /**
     * Returns the where clauses.
     *
     * @param array  $filter
     * @param array  $operation
     * @param string $defaultOperation
     *
     * @return DataBaseWhere[]
     */
    private function getWhereValues($filter, $operation, $defaultOperation = 'AND')
    {
        $where = [];
        foreach ($filter as $key => $value) {
            if (!isset($operation[$key])) {
                $operation[$key] = $defaultOperation;
            }
            $where[] = new DataBaseWhere($key, $value, 'LIKE', $operation[$key]);
        }

        return $where;
    }

    /**
     * Process the resource, allowing POST/PUT/DELETE/GET ALL actions
     *
     * @param string $modelName
     *
     * @return bool
     */
    private function processResource($modelName)
    {
        try {
            $model = new $modelName();
            $offset = (int) $this->request->get('offset', 0);
            $limit = (int) $this->request->get('limit', 50);
            $operation = $this->getRequestArray('operation');
            $filter = $this->getRequestArray('filter');
            $order = $this->getRequestArray('sort');
            $where = $this->getWhereValues($filter, $operation);

            switch ($this->request->getMethod()) {
                case 'POST':
                    foreach($this->request->request->all() as $key => $value) {
                        $model->{$key} = $value;
                    }
                    if($model->save()) {
                        $data = (array) $model;
                    } else {
                        $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);

                        $data = [];
                        foreach($this->miniLog->read() as $msg) {
                            $data['error'] = $msg;
                        }
                    }
                    break;

                default:
                    $data = $model->all($where, $order, $offset, $limit);
                    break;
            }

            $this->response->setContent(json_encode($data));

            return true;
        } catch (\Exception $ex) {
            $this->response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->response->setContent(json_encode(['error' => 'API-ERROR']));

            return false;
        }
    }

    /**
     * Process resource with parameters
     *
     * @param string $modelName
     * @param string $cod
     *
     * @return bool
     */
    private function processResourceParam($modelName, $cod)
    {
        try {
            $model = new $modelName();

            switch ($this->request->getMethod()) {
                case 'PUT':
                    $model = $model->get($cod);
                    foreach($this->request->request->all() as $key => $value) {
                        $model->{$key} = $value;
                    }
                    if($model->save()) {
                        $data = $model;
                    } else {
                        $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);

                        $data = [];
                        foreach($this->miniLog->read() as $msg) {
                            $data['error'] = $msg;
                        }
                    }
                    break;

                case 'DELETE':
                    $object = $model->get($cod);
                    $data = $object->delete();
                    break;

                default:
                    $data = $model->get($cod);
                    break;
            }

            $this->response->setContent(json_encode($data));

            return true;
        } catch (\Exception $ex) {
            $this->response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->response->setContent(json_encode(['error' => 'API-ERROR']));

            return false;
        }
    }

    /**
     * Load resource map
     *
     * @return array
     */
    private function getResourcesMap()
    {
        $resources = [];
        foreach (scandir(FS_FOLDER . '/Dinamic/Model', SCANDIR_SORT_ASCENDING) as $fName) {
            if (substr($fName, -4) === '.php') {
                $modelName = substr($fName, 0, -4);

                /// Conversion to plural
                if (substr($modelName, -1) === 's') {
                    $plural = strtolower($modelName);
                } elseif (substr($modelName, -3) === 'ser' || substr($modelName, -4) === 'tion') {
                    $plural = strtolower($modelName) . 's';
                } elseif (in_array(substr($modelName, -1), ['a', 'e', 'i', 'o', 'u', 'k'], false)) {
                    $plural = strtolower($modelName) . 's';
                } else {
                    $plural = strtolower($modelName) . 'es';
                }

                $resources[$plural] = $modelName;
            }
        }

        return $resources;
    }

    /**
     * Expose resource
     *
     * @param array $map
     */
    private function exposeResources(&$map)
    {
        $json = ['resources' => []];

        foreach (array_keys($map) as $key) {
            $json['resources'][] = $key;
        }

        $this->response->setContent(json_encode($json));
    }
}
