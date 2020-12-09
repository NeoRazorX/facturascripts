<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use Exception;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\ApiAccess;
use FacturaScripts\Dinamic\Model\ApiKey;
use Symfony\Component\HttpFoundation\Response;

/**
 * AppAPI is the class used for API.
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Ángel Guzmán Maeso       <angel@guzmanmaeso.com>
 * @author Rafael San José Tovar    <info@rsanjoseo.com>
 */
final class AppAPI extends App
{

    const API_VERSION = 3;

    /**
     * Contains the ApiKey model
     *
     * @var ApiKey $apiKey
     */
    protected $apiKey;

    /**
     * Returns the data into the standard output.
     */
    public function render()
    {
        $this->response->headers->set('Access-Control-Allow-Origin', '*');
        $this->response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
        $this->response->headers->set('Content-Type', 'application/json');
        parent::render();
    }

    /**
     * Runs the API.
     *
     * @return bool
     */
    public function run(): bool
    {
        if (false === parent::run()) {
            return false;
        } elseif ($this->isDisabled()) {
            $this->die(Response::HTTP_NOT_FOUND, 'api-disabled');
            return false;
        } elseif ($this->request->server->get('REQUEST_METHOD') == 'OPTIONS') {
            $allowHeaders = $this->request->server->get('HTTP_ACCESS_CONTROL_REQUEST_HEADERS');
            $this->response->headers->set('Access-Control-Allow-Headers', $allowHeaders);
            return false;
        } elseif (false === $this->checkAuthToken()) {
            $this->ipWarning();
            $this->die(Response::HTTP_FORBIDDEN, 'auth-token-invalid');
            return false;
        } elseif (false === $this->isAllowed()) {
            $this->ipWarning();
            $this->die(Response::HTTP_FORBIDDEN, 'forbidden');
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
     * We can define a master API KEY in the config.php by defining the constant
     * FS_API_KEY.
     *
     * @return bool
     */
    private function checkAuthToken(): bool
    {
        $this->apiKey = new ApiKey();
        $altToken = $this->request->headers->get('Token', '');
        $token = $this->request->headers->get('X-Auth-Token', $altToken);
        if (empty($token)) {
            return false;
        }

        if (defined('FS_API_KEY') && $token == \FS_API_KEY) {
            $this->apiKey->apikey = \FS_API_KEY;
            $this->apiKey->fullaccess = true;
            return true;
        }

        $where = [
            new DataBaseWhere('apikey', $token),
            new DataBaseWhere('enabled', true)
        ];
        return $this->apiKey->loadFromCode('', $where);
    }

    /**
     * 
     * @param int    $status
     * @param string $message
     */
    protected function die(int $status, string $message = '')
    {
        $content = $this->toolBox()->i18n()->trans($message);
        foreach ($this->toolBox()->log()->readAll() as $log) {
            $content .= empty($content) ? $log["message"] : "\n" . $log["message"];
        }

        $this->response->setContent(json_encode(['error' => $content]));
        $this->response->setStatusCode($status);
    }

    /**
     * Expose resource.
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
        foreach (scandir(\FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . 'Lib' . DIRECTORY_SEPARATOR . 'API', SCANDIR_SORT_NONE) as $resource) {
            if (substr($resource, -4) !== '.php') {
                continue;
            }

            // The name of the class will be the same as that of the file without the php extension.
            // Classes will be descendants of Base/APIResourceClass.
            $class = substr('\\FacturaScripts\\Dinamic\\Lib\\API\\' . $resource, 0, -4);
            $APIClass = new $class($this->response, $this->request, []);
            if (isset($APIClass) && method_exists($APIClass, 'getResources')) {
                // getResources obtains an associative array of arrays generated
                // with setResource ('name'). These arrays keep the name of the class
                // and the resource so that they can be invoked later.
                //
                // This allows using different API extensions, and not just the
                // usual Lib/API/APIModel.
                $resources[] = $APIClass->getResources();
            }
        }

        // Returns an ordered array with all available resources.
        $finalResources = array_merge(...$resources);
        ksort($finalResources);
        return $finalResources;
    }

    /**
     * Returns true if the token has the requested access to the resource.
     *
     * @return bool
     */
    private function isAllowed(): bool
    {
        $resource = $this->getUriParam(2);
        if ($resource === '' || $this->apiKey->fullaccess) {
            return true;
        }

        $apiAccess = new ApiAccess();
        $where = [
            new DataBaseWhere('idapikey', $this->apiKey->id),
            new DataBaseWhere('resource', $resource)
        ];
        if ($apiAccess->loadFromCode('', $where)) {
            switch ($this->request->getMethod()) {
                case 'DELETE':
                    return $apiAccess->allowdelete;

                case 'GET':
                    return $apiAccess->allowget;

                case 'PATCH':
                case 'PUT':
                    return $apiAccess->allowput;

                case 'POST':
                    return $apiAccess->allowpost;
            }
        }

        return false;
    }

    /**
     * Check if API is disabled. API can't be disabled if FS_API_KEY is defined
     * in the config.php file.
     *
     * @return bool
     */
    private function isDisabled(): bool
    {
        /// Is FS_API_KEY defined in the config?
        if (defined('FS_API_KEY')) {
            return false;
        }

        return $this->toolBox()->appSettings()->get('default', 'enable_api', false) == false;
    }

    /**
     * Selects the resource
     *
     * @return bool
     */
    private function selectResource(): bool
    {
        $map = $this->getResourcesMap();

        $resourceName = $this->getUriParam(2);
        if ($resourceName === '') {
            // If no command, expose resources and exit
            $this->exposeResources($map);
            return true;
        }

        if (!isset($map[$resourceName]['API'])) {
            $this->die(Response::HTTP_BAD_REQUEST, 'invalid-resource');
            return false;
        }

        /// get params
        $param = 3;
        $params = [];
        while (($item = $this->getUriParam($param)) !== '') {
            $params[] = $item;
            $param++;
        }

        try {
            $APIClass = new $map[$resourceName]['API']($this->response, $this->request, $params);
            return $APIClass->processResource($map[$resourceName]['Name']);
        } catch (Exception $exc) {
            $this->toolBox()->log()->critical('API-ERROR: ' . $exc->getMessage());
            $this->die(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return false;
    }

    /**
     * Selects the API version if it is supported
     *
     * @return bool
     */
    private function selectVersion(): bool
    {
        if ($this->getUriParam(1) == self::API_VERSION) {
            return $this->selectResource();
        }

        $this->die(Response::HTTP_NOT_FOUND, 'api-version-not-found');
        return true;
    }
}
