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

namespace FacturaScripts\Core\Lib\API\Base;

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Base\Translator;
use Symfony\Component\HttpFoundation\Response;

/**
 * APIResource is an abstract class for any API Resource.
 *
 * @author Rafael San José Tovar (http://www.x-netdigital.com) <rsanjoseo@gmail.com>
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class APIResourceClass
{
    /**
     * Translation engine.
     *
     * @var \FacturaScripts\Core\Base\Translator
     */
    protected $i18n;

    /**
     * Contains the HTTP method (GET, PUT, PATCH, POST, DELETE).
     * PUT, PATCH and POST used in the same way.
     *
     * @var string $method
     */
    protected $method;

    /**
     * App log manager.
     *
     * @var \FacturaScripts\Core\Base\MiniLog
     */
    protected $miniLog;

    /**
     * HTTP response object.
     *
     * @var \Symfony\Component\HttpFoundation\Response
     */
    protected $response;

    /**
     * Gives us access to the HTTP request parameters.
     *
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * @var array params passed in the URI
     */
    protected $params;

    /**
     * APIResourceClass constructor.
     *
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param MiniLog $miniLog
     * @param Translator $i18n
     * @param array $params is an array with URI parameters
     */
    public function __construct($response, $request, MiniLog $miniLog, Translator $i18n, array $params)
    {
        $this->response = $response;
        $this->request = $request;
        $this->miniLog = $miniLog;
        $this->i18n = $i18n;
        $this->params = $params;
    }

    /**
     * Process the DELETE request. Overwrite this function to implement is functionality.
     * It is not defined as abstract because descendants may not need this method if
     * they overwrite processResource.
     *
     * @return bool
     */
    public function doDELETE(): bool
    {
        return true;
    }

    /**
     * Process the GET request. Overwrite this function to implement is functionality.
     * It is not defined as abstract because descendants may not need this method if
     * they overwrite processResource.
     *
     * @return bool
     */
    public function doGET(): bool
    {
        return true;
    }

    /**
     * Process the PUT request. Overwrite this function to implement is functionality.
     * It is not defined as abstract because descendants may not need this method if
     * they overwrite processResource.
     *
     * @return bool
     */
    public function doPUT(): bool
    {
        return true;
    }

    /**
     * Process the POST request. Overwrite this function to implement is functionality.
     * It is not defined as abstract because descendants may not need this method if
     * they overwrite processResource.
     *
     * @return bool
     */
    public function doPOST(): bool
    {
        return true;
    }

    /**
     * Returns an associative array with the resources, where the index is
     * the public name of the resource.
     *
     * @return array
     */
    abstract public function getResources(): array;

    /**
     * Process the resource, allowing POST/PUT/DELETE/GET ALL actions
     *
     * @param string $name of resource, used only if are several.
     * @param array $params . Params are URI segments. Can be an empty array, not null.
     *
     * @return bool
     */
    public function processResource(string $name): bool
    {
        try {
            $this->method = $this->request->getMethod();

            // http://www.restapitutorial.com/lessons/httpmethods.html
            switch ($this->method) {
                case 'POST':
                    return $this->doPOST();
                case 'GET':
                    return $this->doGET();
                case 'PUT':
                    return $this->doPUT();
                case 'DELETE':
                    return $this->doDELETE();
            }

            $this->miniLog->error("Unknown method {$this->method} in {$name}");
            $this->setError("Unknown method {$this->method} in {$name}");
            return false;
        } catch (\Exception $ex) {
            $this->miniLog->error('API-ERROR' . Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->setError('API-ERROR', null, Response::HTTP_INTERNAL_SERVER_ERROR);
            return false;
        }
    }

    /**
     * Register a resource
     *
     * @param string $name
     *
     * @return array
     */
    public function setResource(string $name): array
    {
        return ['API' => \get_class($this), 'Name' => $name];
    }

    /**
     * Return the array with the result, and HTTP_OK status code.
     *
     * @param array $data
     * @return void
     */
    protected function returnResult(array $data)
    {
        $this->response->setStatusCode(Response::HTTP_OK);
        $this->response->setContent(json_encode($data));
    }

    /**
     * Return a order confirmation. For example for a DELETE order.
     * Can return an array with additional information.
     *
     * @param string $string is an informative text of the confirmation message
     * @param array $data with additional information.
     * @return void
     */
    protected function setOk(string $string, array $data = null)
    {
        $this->response->setStatusCode(Response::HTTP_OK);
        $res = array();
        $res['ok'] = $string;
        if ($data !== null) {
            $res['data'] = $data;
        }
        $this->response->setContent(json_encode($res));
    }

    /**
     * Return an error message and the corresponding status.
     * Can also return an array with additional information.
     *
     * @param string $message
     * @param array $data
     * @param int $status
     * @return void
     */
    protected function setError(string $message, array $data = null, int $status = Response::HTTP_BAD_REQUEST)
    {
        $this->response->setStatusCode($status);

        foreach ($this->miniLog->read(["error"]) as $error) {
            $message .= ' ' . $error['message'];
        }

        $res = array();
        $res['error'] = $message;
        if ($data !== null) {
            $res['data'] = $data;
        }
        $this->response->setContent(json_encode($res));
    }
}
