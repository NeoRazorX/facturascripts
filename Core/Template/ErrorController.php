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

use Exception;
use FacturaScripts\Core\Contract\ErrorControllerInterface;

abstract class ErrorController implements ErrorControllerInterface
{
    /** @var Exception */
    protected $exception;

    /** @var string */
    protected $url;

    public function __construct(Exception $exception, string $url = '')
    {
        $this->exception = $exception;
        $this->url = $url;
    }

    protected function html(string $title, string $bodyHtml, string $bodyCss): string
    {
        return '<!doctype html>' . PHP_EOL
            . '<html lang="en">' . PHP_EOL
            . '<head>' . PHP_EOL
            . '<meta charset="utf-8">' . PHP_EOL
            . '<meta name="viewport" content="width=device-width, initial-scale=1">' . PHP_EOL
            . '<title>' . $title . '</title>' . PHP_EOL
            . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">' . PHP_EOL
            . '</head>' . PHP_EOL
            . '<body class="' . $bodyCss . '">' . PHP_EOL
            . $bodyHtml
            . '</body>' . PHP_EOL
            . '</html>';
    }
}
