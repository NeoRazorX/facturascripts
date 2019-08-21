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
namespace FacturaScripts\Core\Lib\API\Base;

use Exception;
use FacturaScripts\Core\Base\ToolBox;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * APIResource is an abstract class for any API Resource.
 *
 * @author Rafael San José Tovar (http://www.x-netdigital.com)  <rsanjoseo@gmail.com>
 * @author Carlos García Gómez                                  <carlos@facturascripts.com>
 */
abstract class APIResourceClass
{

    /**
     * Contains the HTTP method (GET, PUT, PATCH, POST, DELETE).
     * PUT, PATCH and POST used in the same way.
     *
     * @var string $method
     */
    protected $method;

    /**
     *
     * @var array
     */
    protected $params;

    /**
     * Gives us access to the HTTP request parameters.
     *
     * @var Request
     */
    protected $request;

    /**
     * HTTP response object.
     *
     * @var Response
     */
    protected $response;

    /**
     * Returns an associative array with the resources, where the index is
     * the public name of the resource.
     *
     * @return array
     */
    abstract public function getResources(): array;

    /**
     * APIResourceClass constructor.
     *
     * @param Response   $response
     * @param Request    $request
     * @param array      $params
     */
    public function __construct($response, $request, array $params)
    {
        $this->params = $params;
        $this->request = $request;
        $this->response = $response;
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
     * Process the resource, allowing POST/PUT/DELETE/GET ALL actions
     *
     * @param string $name of resource, used only if are several.
     * @param array  $params are URI segments. Can be an empty array, not null.
     *
     * @return bool
     */
    public function processResource(string $name): bool
    {
        $this->method = $this->request->getMethod();

        try {
            // http://www.restapitutorial.com/lessons/httpmethods.html
            switch ($this->method) {
                case 'DELETE':
                    return $this->doDELETE();

                case 'GET':
                    return $this->doGET();

                case 'PATCH':
                case 'PUT':
                    return $this->doPUT();

                case 'POST':
                    return $this->doPOST();
            }
        } catch (Exception $exc) {
            $this->setError('API-ERROR: ' . $exc->getMessage(), null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return false;
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
     */
    protected function returnResult(array $data)
    {
        $this->response->setContent(json_encode($data));
        $this->response->setStatusCode(Response::HTTP_OK);
    }

    /**
     * Return a order confirmation. For example for a DELETE order.
     * Can return an array with additional information.
     *
     * @param string $message is an informative text of the confirmation message
     * @param array  $data with additional information.
     */
    protected function setOk(string $message, $data = null)
    {
        $this->toolBox()->log('api')->notice($message);

        $res = ['ok' => $message];
        if ($data !== null) {
            $res['data'] = $data;
        }

        $this->response->setContent(json_encode($res));
        $this->response->setStatusCode(Response::HTTP_OK);
    }

    /**
     * Return an error message and the corresponding status.
     * Can also return an array with additional information.
     *
     * @param string $message
     * @param array  $data
     * @param int    $status
     */
    protected function setError(string $message, $data = null, int $status = Response::HTTP_BAD_REQUEST)
    {
        $this->toolBox()->log('api')->error($message);

        $res = ['error' => $message];
        if ($data !== null) {
            $res['data'] = $data;
        }

        $this->response->setContent(json_encode($res));
        $this->response->setStatusCode($status);
    }

    /**
     * 
     * @return ToolBox
     */
    protected function toolBox()
    {
        return new ToolBox();
    }
}
