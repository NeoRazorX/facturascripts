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

namespace FacturaScripts\Core;

use Exception;
use FacturaScripts\Core\Contract\ErrorControllerInterface;
use Throwable;

class KernelException extends Exception
{
    /** @var string */
    public $handler = '';

    /** @var string */
    public $url = '';

    public function __construct(string $handler, string $message, int $code = 0, ?Throwable $previous = null)
    {
        $this->handler = $handler;
        parent::__construct($message, $code, $previous);
    }

    public function getHandler(string $url): ErrorControllerInterface
    {
        $this->url = $url;

        $dynClass = '\\FacturaScripts\\Dinamic\\Error\\' . $this->handler;
        if (class_exists($dynClass)) {
            return new $dynClass($this);
        }

        $mainClass = '\\FacturaScripts\\Core\\Error\\' . $this->handler;
        return new $mainClass($this);
    }
}
