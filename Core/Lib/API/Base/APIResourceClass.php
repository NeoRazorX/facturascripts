<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Lib\API\Base;

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
     * HTTP response object.
     *
     * @var Response
     */
    protected $response;

    abstract public function processResource();

    abstract public function processResourceParam(array $params);

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    protected function returnResult(array $data)
    {
        $this->response->setContent(json_encode($data));
    }

    protected function setError(string $string)
    {
        $this->response->setContent(json_encode(['error' => $string]));
    }
}
