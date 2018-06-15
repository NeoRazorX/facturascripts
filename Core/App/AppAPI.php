<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\App;

use FacturaScripts\Core\Model\ApiKey;
use FacturaScripts\Core\Model\ApiAccess;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use Symfony\Component\HttpFoundation\Response;

/**
 * AppAPI is the class used for API.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Ángel Guzmán Maeso <angel@guzmanmaeso.com>
 * @author Rafael San José Tovar (http://www.x-netdigital.com) <info@rsanjoseo.com>
 */
class AppAPI extends App
{

    /**
     * Contains the ApiKey model
     *
     * @var int $apiKey
     */
    protected $apiKey;

    /**
     * Runs the API.
     *
     * @return bool
     */
    public function run(): bool
    {
        $this->response->headers->set('Access-Control-Allow-Origin', '*');
        $this->response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE');
        $this->response->headers->set('Content-Type', 'application/json');

        if ($this->isDisabled()) {
            $this->fatalError('API-DISABLED', Response::HTTP_NOT_FOUND);
            return false;
        }

        if (!$this->dataBase->connected()) {
            $this->fatalError('DB-ERROR', Response::HTTP_INTERNAL_SERVER_ERROR);
            return false;
        }

        if ($this->isIPBanned()) {
            $this->fatalError('IP-BANNED', Response::HTTP_FORBIDDEN);
            return false;
        }

        if (!$this->checkAuthToken()) {
            $this->fatalError('AUTH-TOKEN-INVALID', Response::HTTP_FORBIDDEN);
            return false;
        }

        if (!$this->isAllowed()) {
            $this->fatalError('FORBIDDEN', Response::HTTP_FORBIDDEN);
            return false;
        }

        return $this->selectVersion();
    }

    /**
     * Check authentication using one of the supported tokens.
     * In the header you have to pass a token using the header 'Token' or the
     * standard 'X-Auth-Token', returning true if the token passed by any of
     * those headers is valid.
     *
     * @return boolean
     */
    private function checkAuthToken(): bool
    {
        $altToken = $this->request->headers->get('Token', '');
        $token = $this->request->headers->get('X-Auth-Token', $altToken);
        if (empty($token)) {
            return false;
        }

        $this->apiKey = new ApiKey();
        return $this->apiKey->loadFromCode('', [new DataBaseWhere('apikey', $token)]);
    }

    /**
     * returns true if the token user has the requested access to the resource
     *
     * @return bool
     */
    public function isAllowed(): bool
    {
        $method = $this->request->getMethod();

        $apiAccess = new ApiAccess();
        if ($apiAccess === null) {
            return false;
        }

        $where = [new DataBaseWhere('idapikey', $this->apiKey->id)];
        $constraints = $apiAccess->all($where);
        $result = null;

        $resource = $this->getUriParam(2);
        if ($resource === '') {
            return true;
        }

        foreach ($constraints as $value) {
            if ($value->resource === $resource) {
                $result = (array) $value;
            }
        }

        if ($result !== null) {
            if ($method == 'POST' && $result['allowpost']) {
                return true;
            }
            if ($method == 'GET' && $result['allowget']) {
                return true;
            }
            if ($method == 'PUT' && $result['allowput']) {
                return true;
            }
            if ($method == 'DELETE' && $result['allowdelete']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Expose resource.
     *
     * @param array $map
     * @throws \UnexpectedValueException
     */
    private function exposeResources(&$map)
    {
        $json = ['resources' => []];
        foreach (array_keys($map) as $key) {
            $json['resources'][] = $key;
        }

        $this->response->setContent(json_encode($json));
    }

    /**
     * Go through all the files in the /Dinamic/Lib/API, collecting the name of
     * all available resources in each of them, and adding them to an array that
     * is returned.
     *
     * @return array
     */
    private function getResourcesMap(): array
    {
        $resources = [[]];
        // Loop all controllers in /Dinamic/Lib/API
        foreach (scandir(FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . 'Lib' . DIRECTORY_SEPARATOR . 'API', SCANDIR_SORT_NONE) as $resource) {
            if (substr($resource, -4) === '.php') {
                // The name of the class will be the same as that of the file
                // without the php extension.
                //
                // Classes will be descendants of Base/APIResourceClass.
                $class = substr('FacturaScripts\\Dinamic\\Lib\\API\\' . $resource, 0, -4);
                $APIClass = new $class($this->response, $this->request, $this->miniLog, $this->i18n, []);
                if (isset($APIClass) && method_exists($APIClass, 'getResources')) {
                    // getResources obtains an associative array of arrays generated
                    // with setResource ('name'). These arrays keep the name of the class
                    // and the resource so that they can be invoked later.
                    //
                    // This allows using different API extensions, and not just the
                    // usual Lib/API/APIModel.
                    $resources[] = $APIClass->getResources();
                }
                unset($APIClass);
            }
        }
        $resources = array_merge(...$resources);
        ksort($resources);
        // Returns an ordered array with all available resources.
        return $resources;
    }

    /**
     * Check if API is disabled
     *
     * @return bool
     */
    private function isDisabled(): bool
    {
        return $this->settings->get('default', 'enable_api', false) !== 'true';
    }

    /**
     * Selects the resource
     *
     * @return bool
     */
    private function selectResource(): bool
    {
        $resourceName = $this->getUriParam(2);
        $map = $this->getResourcesMap();

        // If no command, expose resources and exit
        if ($resourceName === '') {
            $this->exposeResources($map);
            return true;
        }

        $param = 3;
        $params = [];
        while (($cad = $this->getUriParam($param)) !== '') {
            $params[] = $cad;
            $param++;
        }

        if (!isset($map[$resourceName]['API'])) {
            $this->fatalError('invalid-resource', Response::HTTP_BAD_REQUEST);
            return false;
        }

        $APIClass = new $map[$resourceName]['API']($this->response, $this->request, $this->miniLog, $this->i18n, $params);
        if (isset($APIClass) && method_exists($APIClass, 'processResource')) {
            return $APIClass->processResource($map[$resourceName]['Name']);
        }

        $this->fatalError('database-error', Response::HTTP_INTERNAL_SERVER_ERROR);
        return false;
    }

    /**
     * Selects the API version if it is supported
     *
     * @return bool
     */
    private function selectVersion(): bool
    {
        if ($this->getUriParam(1) === '3') {
            return $this->selectResource();
        }

        $this->fatalError('API-VERSION-NOT-FOUND', Response::HTTP_NOT_FOUND);
        return true;
    }

    /**
     * Return an array with the error message, and the corresponding status.
     *
     * @param string $text
     * @param int    $status
     */
    protected function fatalError(string $text, int $status)
    {
        $this->response->setStatusCode($status);
        $this->response->setContent(json_encode(['error' => $text]));
    }
}
