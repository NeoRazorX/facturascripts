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

namespace FacturaScripts\Core\Template;

use FacturaScripts\Core\Contract\ControllerInterface;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;

abstract class Controller implements ControllerInterface
{
    /** @var Request */
    public $request;

    /** @var Response */
    private $response;

    public function __construct(string $className, string $url = '')
    {
        $this->request = Request::createFromGlobals();
    }

    protected function response(): Response
    {
        if (null === $this->response) {
            $this->response = new Response();
        }

        return $this->response;
    }
}
