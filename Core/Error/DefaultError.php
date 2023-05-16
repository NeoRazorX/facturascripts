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

namespace FacturaScripts\Core\Error;

use FacturaScripts\Core\Template\ErrorController;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class DefaultError extends ErrorController
{
    public function run(): void
    {
        http_response_code(500);

        if ($this->exception instanceof SyntaxError) {
            echo '<h1>Twig syntax error</h1>';
            echo '<p>' . $this->exception->getRawMessage() . '</p>';
            echo '<p>File: ' . $this->exception->getFile() . ':' . $this->exception->getLine() . '</p>';
            return;
        }

        if ($this->exception instanceof RuntimeError) {
            echo '<h1>Twig runtime error</h1>';
            echo '<p>' . $this->exception->getRawMessage() . '</p>';
            echo '<p>File: ' . $this->exception->getFile() . ':' . $this->exception->getLine() . '</p>';
            return;
        }

        if ($this->exception instanceof LoaderError) {
            echo '<h1>Twig loader error</h1>';
            echo '<p>' . $this->exception->getRawMessage() . '</p>';
            echo '<p>File: ' . $this->exception->getFile() . ':' . $this->exception->getLine() . '</p>';
            return;
        }

        echo '<h1>Internal error ' . $this->exception->getCode() . '</h1>';
        echo '<p>' . $this->exception->getMessage() . '</p>';
        echo '<p>File: ' . $this->exception->getFile() . ':' . $this->exception->getLine() . '</p>';
    }
}