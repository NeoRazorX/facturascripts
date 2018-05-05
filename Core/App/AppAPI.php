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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\App;

use FacturaScripts\Core\Model\ApiKey;
use Symfony\Component\HttpFoundation\Response;

/**
 * AppAPI is the class used for API.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Rafael San José Tovar (http://www.x-netdigital.com) <info@rsanjoseo.com>
 */
class AppAPI extends App
{
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

        return $this->selectVersion();
    }

    /**
     * Returns true if the client is authenticated with the header token.
     *
     * @author Ángel Guzmán Maeso <angel@guzmanmaeso.com>
     *
     * @return boolean
     */
    private function checkAuthToken(): bool
    {
        $token = $this->request->headers->get('Token', '');
        if (empty($token)) {
            return false;
        }

        return (new ApiKey())->checkAuthToken($token);
    }

    /**
     * Expose resource.
     *
     * @param array $map
     * @throws \UnexpectedValueException
     * @return void
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
     * Load resource map
     *
     * @return array
     */
    private function getResourcesMap(): array
    {
        $resources = [[]];
        foreach (scandir(FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . 'Lib' . DIRECTORY_SEPARATOR . 'API', SCANDIR_SORT_NONE) as $resource) {
            if (substr($resource, -4) === '.php') {
                $class = substr('FacturaScripts\\Dinamic\\Lib\\API\\' . $resource, 0, -4);
                $APIClass = new $class($this->response, $this->request, $this->miniLog, $this->i18n, []);
                $resources[] = $APIClass->getResources();
                unset($APIClass);
            }
        }
        $resources = array_merge(...$resources);
        ksort($resources);

        return $resources;
    }

    /**
     * Check if API is disabled
     *
     * @return mixed
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

        $APIClass = new $map[$resourceName]['API']($this->response, $this->request, $this->miniLog, $this->i18n, $params);
        if (isset($APIClass)) {
            return $APIClass->processResource($map[$resourceName]['Name'], $params);
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
     * @param int $status
     * @return void
     */
    protected function fatalError(string $text, int $status)
    {
        $this->response->setStatusCode($status);
        $this->response->setContent(json_encode(['error' => $text]));
    }
}
