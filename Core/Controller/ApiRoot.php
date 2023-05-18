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
use FacturaScripts\Core\Contract\ControllerInterface;
use FacturaScripts\Core\KernelException;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\ApiAccess;
use FacturaScripts\Dinamic\Model\ApiKey;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiRoot implements ControllerInterface
{
    const API_VERSION = 3;

    /** @var ApiKey */
    protected $apiKey;

    /** @var Request */
    protected $request;

    /** @var Response */
    protected $response;

    /** @var string */
    protected $url;

    public function __construct(string $className, string $url = '')
    {
        $this->request = Request::createFromGlobals();
        $this->response = new Response();
        $this->url = $url;

        // si no hay constante api_key y la api está desactivada, no se puede acceder
        if (!defined('API_KEY') && false == Tools::settings('default', 'enable_api', false)) {
            throw new KernelException('DisabledApi', Tools::lang()->trans('api-disabled'));
        }

        // comprobamos el token
        $altToken = $this->request->headers->get('Token', '');
        $token = $this->request->headers->get('X-Auth-Token', $altToken);
        if (false === $this->validateApiToken($token)) {
            throw new KernelException('InvalidApiToken', Tools::lang()->trans('auth-token-invalid'));
        }

        // comprobamos los permisos
        if (false === $this->isAllowed()) {
            throw new KernelException('ForbiddenApiEndpoint', Tools::lang()->trans('forbidden'));
        }
    }

    public function getPageData(): array
    {
        return [];
    }

    public function run(): void
    {
        if ($this->request->server->get('REQUEST_METHOD') == 'OPTIONS') {
            $allowHeaders = $this->request->server->get('HTTP_ACCESS_CONTROL_REQUEST_HEADERS');
            $this->response->headers->set('Access-Control-Allow-Headers', $allowHeaders);
            return;
        }

        $this->response->headers->set('Access-Control-Allow-Origin', '*');
        $this->response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
        $this->response->headers->set('Content-Type', 'application/json');

        $version = $this->getUriParam(1);
        if (empty($version)) {
            throw new KernelException('MissingApiVersion', Tools::lang()->trans('api-version-not-found'));
        }
        if ($version != self::API_VERSION) {
            throw new KernelException('InvalidApiVersion', Tools::lang()->trans('api-version-invalid'));
        }

        $this->selectResource();

        $this->response->send();
    }

    protected function exposeResources(array &$map): void
    {
        $json = ['resources' => []];
        foreach (array_keys($map) as $key) {
            $json['resources'][] = $key;
        }

        $this->response->setContent(json_encode($json));
    }

    protected function getResourcesMap(): array
    {
        $resources = [[]];
        // Loop all controllers in /Dinamic/Lib/API
        $folder = Tools::folder('Dinamic', 'Lib', 'API');
        foreach (Tools::folderScan($folder, false) as $resource) {
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

    protected function getUriParam(string $num): string
    {
        $params = explode('/', substr($this->url, 1));
        return $params[$num] ?? '';
    }

    protected function isAllowed(): bool
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

    protected function selectResource(): void
    {
        $map = $this->getResourcesMap();

        $resourceName = $this->getUriParam(2);
        if ($resourceName === '') {
            // If no command, expose resources and exit
            $this->exposeResources($map);
            return;
        }

        if (!isset($map[$resourceName]['API'])) {
            throw new KernelException('InvalidApiResource', Tools::lang()->trans('api-resource-invalid'));
        }

        // get params
        $param = 3;
        $params = [];
        while (($item = $this->getUriParam($param)) !== '') {
            $params[] = $item;
            $param++;
        }

        $ApiClass = new $map[$resourceName]['API']($this->response, $this->request, $params);
        $ApiClass->processResource($map[$resourceName]['Name']);
    }

    protected function validateApiToken(string $token): bool
    {
        $this->apiKey = new ApiKey();
        if (empty($token)) {
            return false;
        }

        if ($token === Tools::config('api_key')) {
            $this->apiKey->apikey = Tools::config('api_key');
            $this->apiKey->fullaccess = true;
            return true;
        }

        $where = [
            new DataBaseWhere('apikey', $token),
            new DataBaseWhere('enabled', true)
        ];
        return $this->apiKey->loadFromCode('', $where);
    }
}