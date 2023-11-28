<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Internal\ResponseHeaders;

final class Response
{
    /** @var string */
    private $content;

    /** @var ResponseHeaders */
    public $headers;

    /** @var int */
    private $http_code;

    public function __construct(int $http_code = 200)
    {
        $this->content = '';
        $this->headers = new ResponseHeaders();
        $this->http_code = $http_code;
    }

    public function cookie(string $name, string $value, int $expire = 0): self
    {
        if (empty($expire)) {
            $expire = time() + (int)Tools::config('cookies_expire');
        }

        setcookie($name, $value, $expire, Tools::config('route', '/'));

        return $this;
    }

    public function file(string $file_path, string $file_name): void
    {
        http_response_code($this->http_code);

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file_name . '"');
        header('Content-Length: ' . filesize($file_path));

        readfile($file_path);
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getHttpCode(): int
    {
        return $this->http_code;
    }

    public function header(string $name, string $value): self
    {
        $this->headers->set($name, $value);

        return $this;
    }

    public function json(array $data): void
    {
        $this->content = json_encode($data);

        $this->send();
    }

    public function pdf(string $content, string $file_name = ''): void
    {
        // si no tenemos nombre, generamos uno
        if (empty($file_name)) {
            $file_name = 'doc_' . uniqid() . '.pdf';
        }

        http_response_code($this->http_code);

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $file_name . '"');
        header('Content-Length: ' . strlen($content));

        echo $content;
    }

    public function send(): void
    {
        http_response_code($this->http_code);

        foreach ($this->headers->all() as $name => $value) {
            header($name . ': ' . $value);
        }

        echo $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function setHttpCode(int $http_code): self
    {
        $this->http_code = $http_code;

        return $this;
    }
}
