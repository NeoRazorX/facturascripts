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

namespace FacturaScripts\Core\Lib\API\Base;

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Base\Translator;
use Symfony\Component\HttpFoundation\Response;

/**
 * APIResource is an abstract class for any API Resource.
 *
 * @author Rafael San José Tovar (http://www.x-netdigital.com) <rsanjoseo@gmail.com>
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
     * App log manager.
     *
     * @var \FacturaScripts\Core\Base\MiniLog
     */
    protected $miniLog;

    /**
     * Translation engine.
     *
     * @var \FacturaScripts\Core\Base\Translator
     */
    protected $i18n;

    /**
     * APIResourceClass constructor.
     *
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param MiniLog $miniLog
     * @param Translator $i18n
     */
    public function __construct($response, $request, MiniLog $miniLog, Translator $i18n)
    {
        $this->response = $response;
        $this->request = $request;
        $this->miniLog = $miniLog;
        $this->i18n = $i18n;
    }

    /**
     * Process the GET request. Overwrite this function to implement is functionality.
     * It is not defined as abstract because descendants may not need this method if
     * they overwrite processResource.
     *
     * @param array $params
     *
     * @return bool
     */
    public function doGET(array $params): bool
    {
        return true;
    }

    /**
     * Process the POST/PUT/PATCH request. Overwrite this function to implement is functionality.
     * It is not defined as abstract because descendants may not need this method if
     * they overwrite processResource.
     *
     * @param array $params
     *
     * @return bool
     */
    public function doPOST(array $params): bool
    {
        return true;
    }

    /**
     * Process the DELETE request. Overwrite this function to implement is functionality.
     * It is not defined as abstract because descendants may not need this method if
     * they overwrite processResource.
     *
     * @param array $params
     *
     * @return bool
     */
    public function doDELETE(array $params): bool
    {
        return true;
    }

    /**
     * Process the resource, allowing POST/PUT/DELETE/GET ALL actions
     *
     * @param string $name of resource, used only if are several.
     * @param array $params . Params are URI segments. Can be an empty array, not null.
     *
     * @return bool
     */
    public function processResource(string $name, array $params): bool
    {
        try {
            $this->method = $this->request->getMethod();

            // http://www.restapitutorial.com/lessons/httpmethods.html
            switch ($this->method) {
                /*
                 * The HTTP GET method is used to **read** (or retrieve) a representation of a resource. In the “happy”
                 * (or non-error) path, GET returns a representation in XML or JSON and an HTTP response code of 200 (OK).
                 * In an error case, it most often returns a 404 (NOT FOUND) or 400 (BAD REQUEST).
                 *
                 * According to the design of the HTTP specification, GET (along with HEAD) requests are used only to read
                 * data and not change it. Therefore, when used this way, they are considered safe. That is, they can be
                 * called without risk of data modification or corruption—calling it once has the same effect as calling
                 * it 10 times, or none at all. Additionally, GET (and HEAD) is idempotent, which means that making multiple
                 * identical requests ends up having the same result as a single request.
                 *
                 * Do not expose unsafe operations via GET—it should never modify any resources on the server.
                 *
                 * Examples:
                 *
                 *   GET http://www.example.com/customers/12345
                 *   GET http://www.example.com/customers/12345/orders
                 *   GET http://www.example.com/buckets/sample
                 */
                case 'GET':
                    return $this->doGET($params);
                /*
                 * PUT is most-often utilized for **update** capabilities, PUT-ing to a known resource URI with the request
                 * body containing the newly-updated representation of the original resource.
                 *
                 * However, PUT can also be used to create a resource in the case where the resource ID is chosen by the
                 * client instead of by the server. In other words, if the PUT is to a URI that contains the value of a
                 * non-existent resource ID. Again, the request body contains a resource representation. Many feel this is
                 * convoluted and confusing. Consequently, this method of creation should be used sparingly, if at all.
                 *
                 * Alternatively, use POST to create new resources and provide the client-defined ID in the body
                 * representation—presumably to a URI that doesn't include the ID of the resource (see POST below).
                 *
                 * On successful update, return 200 (or 204 if not returning any content in the body) from a PUT. If using
                 * PUT for create, return HTTP status 201 on successful creation. A body in the response is
                 * optional—providing one consumes more bandwidth. It is not necessary to return a link via a Location
                 * header in the creation case since the client already set the resource ID.
                 *
                 * PUT is not a safe operation, in that it modifies (or creates) state on the server, but it is idempotent.
                 * In other words, if you create or update a resource using PUT and then make that same call again, the
                 * resource is still there and still has the same state as it did with the first call.
                 *
                 * If, for instance, calling PUT on a resource increments a counter within the resource, the call is no
                 * longer idempotent. Sometimes that happens and it may be enough to document that the call is not
                 * idempotent. However, it's recommended to keep PUT requests idempotent. It is strongly recommended to use
                 * POST for non-idempotent requests.
                 *
                 * Examples:
                 *
                 *   PUT http://www.example.com/customers/12345
                 *   PUT http://www.example.com/customers/12345/orders/98765
                 *   PUT http://www.example.com/buckets/secret_stuff
                 */
                case 'PUT':
                    /*
                     * PUT is most-often utilized for **update** capabilities, PUT-ing to a known resource URI with the request
                     * body containing the newly-updated representation of the original resource.
                     *
                     * However, PUT can also be used to create a resource in the case where the resource ID is chosen by the
                     * client instead of by the server. In other words, if the PUT is to a URI that contains the value of a
                     * non-existent resource ID. Again, the request body contains a resource representation. Many feel this is
                     * convoluted and confusing. Consequently, this method of creation should be used sparingly, if at all.
                     *
                     * Alternatively, use POST to create new resources and provide the client-defined ID in the body
                     * representation—presumably to a URI that doesn't include the ID of the resource (see POST below).
                     *
                     * On successful update, return 200 (or 204 if not returning any content in the body) from a PUT. If using
                     * PUT for create, return HTTP status 201 on successful creation. A body in the response is
                     * optional—providing one consumes more bandwidth. It is not necessary to return a link via a Location
                     * header in the creation case since the client already set the resource ID.
                     *
                     * PUT is not a safe operation, in that it modifies (or creates) state on the server, but it is idempotent.
                     * In other words, if you create or update a resource using PUT and then make that same call again, the
                     * resource is still there and still has the same state as it did with the first call.
                     *
                     * If, for instance, calling PUT on a resource increments a counter within the resource, the call is no
                     * longer idempotent. Sometimes that happens and it may be enough to document that the call is not
                     * idempotent. However, it's recommended to keep PUT requests idempotent. It is strongly recommended to use
                     * POST for non-idempotent requests.
                     *
                     * Examples:
                     *
                     *   PUT http://www.example.com/customers/12345
                     *   PUT http://www.example.com/customers/12345/orders/98765
                     *   PUT http://www.example.com/buckets/secret_stuff
                     */
                case 'PATCH':
                    /*
                     * The POST verb is most-often utilized to **create** new resources. In particular, it's used to create
                     * subordinate resources. That is, subordinate to some other (e.g. parent) resource. In other words, when
                     * creating a new resource, POST to the parent and the service takes care of associating the new resource
                     * with the parent, assigning an ID (new resource URI), etc.
                     *
                     * On successful creation, return HTTP status 201, returning a Location header with a link to the
                     * newly-created resource with the 201 HTTP status.
                     *
                     * POST is neither safe nor idempotent. It is therefore recommended for non-idempotent resource requests.
                     * Making two identical POST requests will most-likely result in two resources containing the same
                     * information.
                     *
                     * Examples:
                     *
                     *   POST http://www.example.com/customers
                     *   POST http://www.example.com/customers/12345/orders
                     */
                case 'POST':
                    return $this->doPOST($params);
                /*
                 * DELETE is pretty easy to understand. It is used to **delete** a resource identified by a URI.
                 *
                 * On successful deletion, return HTTP status 200 (OK) along with a response body, perhaps the
                 * representation of the deleted item (often demands too much bandwidth), or a wrapped response (see Return
                 * Values below). Either that or return HTTP status 204 (NO CONTENT) with no response body. In other words,
                 * a 204 status with no body, or the JSEND-style response and HTTP status 200 are the recommended responses.
                 *
                 * HTTP-spec-wise, DELETE operations are idempotent. If you DELETE a resource, it's removed. Repeatedly
                 * calling DELETE on that resource ends up the same: the resource is gone. If calling DELETE say, decrements
                 * a counter (within the resource), the DELETE call is no longer idempotent. As mentioned previously, usage
                 * statistics and measurements may be updated while still considering the service idempotent as long as no
                 * resource data is changed. Using POST for non-idempotent resource requests is recommended.
                 *
                 * There is a caveat about DELETE idempotence, however. Calling DELETE on a resource a second time will
                 * often return a 404 (NOT FOUND) since it was already removed and therefore is no longer findable. This,
                 * by some opinions, makes DELETE operations no longer idempotent, however, the end-state of the resource
                 * is the same. Returning a 404 is acceptable and communicates accurately the status of the call.
                 *
                 * Examples:
                 *
                 *   DELETE http://www.example.com/customers/12345
                 *   DELETE http://www.example.com/customers/12345/orders
                 *   DELETE http://www.example.com/bucket/sample
                 */
                case 'DELETE':
                    return $this->doDELETE($params);
                default:
                    $this->setError("Unknown method {$this->method} in {$name}");
                    return false;
            }
        } catch (\Exception $ex) {
            $this->setError(API_ERROR, null, Response::HTTP_INTERNAL_SERVER_ERROR);
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
     * Returns an associative array with the resources, where the index is
     * the public name of the resource.
     *
     * @return array
     */
    abstract public function getResources(): array;

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
        $res['error'] = $message;
        if ($data !== null) {
            $res['data'] = $data;
        }
        $this->response->setContent(json_encode($res));
    }
}
