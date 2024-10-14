<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Template;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Contract\ControllerInterface;
use FacturaScripts\Core\KernelException;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\ApiAccess;
use FacturaScripts\Dinamic\Model\ApiKey;

abstract class ApiController implements ControllerInterface
{
    const API_VERSION = 3;
    const INCIDENT_EXPIRATION_TIME = 600;
    const IP_LIST = 'api-ip-list';
    const MAX_INCIDENT_COUNT = 5;

    /** @var ApiKey */
    protected $apiKey;

    /** @var Request */
    protected $request;

    /** @var Response */
    protected $response;

    /** @var string */
    protected $url;

    abstract protected function runResource(): void;

    public function __construct(string $className, string $url = '')
    {
        $this->request = Request::createFromGlobals();
        $this->response = new Response();
        $this->url = $url;

        Session::set('uri', $url);
    }

    public function getPageData(): array
    {
        return [];
    }

    public function run(): void
    {
        // si no hay constante api_key y la api está desactivada, no se puede acceder
        if (null === Tools::config('api_key') && false == Tools::settings('default', 'enable_api', false)) {
            throw new KernelException('DisabledApi', Tools::lang()->trans('api-disabled'));
        }

        if ($this->request->server->get('REQUEST_METHOD') == 'OPTIONS') {
            $this->response->headers->set('Access-Control-Allow-Origin', '*');
            $this->response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
            $allowHeaders = $this->request->server->get('HTTP_ACCESS_CONTROL_REQUEST_HEADERS');
            $this->response->headers->set('Access-Control-Allow-Headers', $allowHeaders);
            $this->response->headers->set('Content-Type', 'application/json');
            $this->response->send();
            return;
        }

        // comprobamos si la IP está bloqueada
        if ($this->clientHasManyIncidents()) {
            throw new KernelException('IpBannedOnApi', Tools::lang()->trans('ip-banned'));
        }

        // comprobamos el token
        $altToken = $this->request->headers->get('Token', '');
        $token = $this->request->headers->get('X-Auth-Token', $altToken);
        if (false === $this->validateApiToken($token)) {
            $this->saveIncident();
            throw new KernelException('InvalidApiToken', Tools::lang()->trans('auth-token-invalid'));
        }

        // comprobamos los permisos
        $resource = $this->getUriParam(2);
        if (false === $this->isAllowed($resource)) {
            $this->saveIncident();
            throw new KernelException('ForbiddenApiEndpoint', Tools::lang()->trans('forbidden'));
        }

        // comprobamos la versión de la api
        $version = $this->getUriParam(1);
        if (empty($version)) {
            throw new KernelException('MissingApiVersion', Tools::lang()->trans('api-version-not-found'));
        }
        if ($version != self::API_VERSION) {
            throw new KernelException('InvalidApiVersion', Tools::lang()->trans('api-version-invalid'));
        }

        $this->response->headers->set('Access-Control-Allow-Origin', '*');
        $this->response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
        $this->response->headers->set('Content-Type', 'application/json');

        $this->runResource();

        $this->response->send();
    }

    private function clientHasManyIncidents(): bool
    {
        // get ip count on the list
        $currentIp = Session::getClientIp();
        $ipCount = 0;
        foreach ($this->getIpList() as $item) {
            if ($item['ip'] === $currentIp) {
                $ipCount++;
            }
        }
        return $ipCount > self::MAX_INCIDENT_COUNT;
    }

    private function getIpList(): array
    {
        $ipList = Cache::get(self::IP_LIST);
        if (false === is_array($ipList)) {
            return [];
        }

        // remove expired items
        $newList = [];
        foreach ($ipList as $item) {
            if (time() - $item['time'] < self::INCIDENT_EXPIRATION_TIME) {
                $newList[] = $item;
            }
        }
        return $newList;
    }

    protected function getUriParam(string $num): string
    {
        $params = explode('/', substr($this->url, 1));
        return $params[$num] ?? '';
    }

    private function isAllowed(string $resource): bool
    {
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

    private function saveIncident(): void
    {
        // add the current IP to the list
        $ipList = $this->getIpList();
        $ipList[] = [
            'ip' => Session::getClientIp(),
            'time' => time()
        ];

        // save the list in cache
        Cache::set(self::IP_LIST, $ipList);
    }

    private function validateApiToken(string $token): bool
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
