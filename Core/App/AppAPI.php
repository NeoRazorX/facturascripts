<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
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

use Symfony\Component\HttpFoundation\Response;
use FacturaScripts\Core\Base\Security\ApiAuth;
/**
 * Description of App
 *
 * @author Carlos García Gómez
 */
class AppAPI extends App
{

    public function auth()
    {
        $config =['request'=>$this->request,'response'=>$this->response];
        $auth = new ApiAuth($config);
        return $auth->startAuth();
    }

    /**
     * Ejecuta la API.
     *
     * @return boolean
     */
    public function run()
    {
        $this->response->headers->set('Access-Control-Allow-Origin', '*');
        $this->response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE');
        $this->response->headers->set('Content-Type', 'application/json');
        $auth = $this->auth();
        if($auth['success']){
            $this->response->headers->set('X-AUTH-TOKEN', $auth['success']['token']);
            if (!$this->dataBase->connected()) {
                $this->response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
                $this->response->setContent(json_encode(['error' => 'DB-ERROR']));
                return false;
            } elseif ($this->isIPBanned()) {
                $this->response->setStatusCode(Response::HTTP_FORBIDDEN);
                $this->response->setContent(json_encode(['error' => 'IP-BANNED']));
                return false;
            }
            return $this->selectVersion();
        }
        $this->response->setStatusCode(Response::HTTP_FORBIDDEN);
        $this->response->setContent(json_encode($auth['error']));
        return false;
    }

    private function selectVersion()
    {
        $version = $this->request->get('v', '');
        if ($version == '3') {
            return $this->selectResource();
        }

        $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
        $this->response->setContent(json_encode(['error' => 'API-VERSION-NOT-FOUND']));
        return true;
    }

    private function selectResource()
    {
        $map = $this->getResourcesMap();

        $resourceName = $this->request->get('resource', '');
        if ($resourceName == '') {
            $this->exposeResources($map);
            return true;
        }

        $modelName = "FacturaScripts\\Dinamic\\Model\\" . $map[$resourceName];
        $cod = $this->request->get('cod', '');

        if ($cod == '') {
            return $this->processResource($modelName);
        }

        return $this->processResourceParam($modelName, $cod);
    }

    private function processResource($modelName)
    {
        try {
            $model = new $modelName();
            $where = [];
            $order = [];
            $offset = (int) $this->request->get('offset', 0);
            $limit = (int) $this->request->get('limit', 50);

            switch ($this->request->getMethod()) {
                case 'POST':
                    $data = [];
                    break;

                case 'PUT':
                    $data = [];
                    break;

                case 'DELETE':
                    $data = [];
                    break;

                default:
                    $data = $model->all($where, $order, $offset, $limit);
                    break;
            }

            $this->response->setContent(json_encode($data));
            return true;
        } catch (Exception $ex) {
            $this->response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->response->setContent(json_encode(['error' => 'API-ERROR']));
            return false;
        }
    }

    private function processResourceParam($modelName, $cod)
    {
        try {
            $model = new $modelName();

            switch ($this->request->getMethod()) {
                case 'POST':
                    $data = [];
                    break;

                case 'PUT':
                    $data = [];
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
        } catch (Exception $ex) {
            $this->response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->response->setContent(json_encode(['error' => 'API-ERROR']));
            return false;
        }
    }

    private function getResourcesMap()
    {
        $resources = [];
        foreach (scandir($this->folder . '/Dinamic/Model') as $fName) {
            if (substr($fName, -4) == '.php') {
                $modelName = substr($fName, 0, -4);

                /// convertimos en plural
                if (substr($modelName, -1) == 's') {
                    $plural = strtolower($modelName);
                } else if (substr($modelName, -3) == 'ser' || substr($modelName, -4) == 'tion') {
                    $plural = strtolower($modelName) . 's';
                } else if (in_array(substr($modelName, -1), ['a', 'e', 'i', 'o', 'u', 'k', 'y'])) {
                    $plural = strtolower($modelName) . 's';
                } else if (substr(strtolower($modelName), -5) == 'model') {
                    $plural = strtolower($modelName) . 'os';
                } else {
                    $plural = strtolower($modelName) . 'es';
                }

                $resources[$plural] = $modelName;
            }
        }

        return $resources;
    }

    private function exposeResources(&$map)
    {
        $json = ['resources' => []];

        foreach (array_keys($map) as $key) {
            $json['resources'][] = $key;
        }

        $this->response->setContent(json_encode($json));
    }
}
