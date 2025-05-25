<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_INTERNAL_SERVER_ERROR = 500;
    public const HTTP_METHOD_NOT_ALLOWED = 405;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_OK = 200;
    public const HTTP_UNAUTHORIZED = 401;

    /** @var string */
    private $content;

    /** @var array */
    private $cookies;

    /** @var ResponseHeaders */
    public $headers;

    /** @var int */
    private $http_code;

    public function __construct(int $http_code = 200)
    {
        $this->content = '';
        $this->cookies = [];
        $this->headers = new ResponseHeaders();
        $this->http_code = $http_code;
    }

    public function cookie(string $name, string $value, int $expire = 0): self
    {
        if (empty($expire)) {
            $expire = time() + (int)Tools::config('cookies_expire');
        }

        $this->cookies[$name] = [
            'name' => $name,
            'value' => $value,
            'expire' => $expire,
        ];

        return $this;
    }

    public function download(string $file_path, string $file_name = ''): void
    {
        $this->file($file_path, $file_name, 'attachment');
    }

    public function file(string $file_path, string $file_name = '', string $disposition = 'inline'): void
    {
        if ($file_name) {
            $disposition .= '; filename="' . $file_name . '"';
        }

        $this->headers->set('Content-Type', 'application/octet-stream');
        $this->headers->set('Content-Disposition', $disposition);
        $this->headers->set('Content-Length', (int)filesize($file_path));

        $this->sendHeaders();

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
        $this->headers->set('Content-Type', 'application/json');
        $this->content = json_encode($data);

        $this->send();
    }

    public function pdf(string $content, string $file_name = ''): void
    {
        // si no tenemos nombre, generamos uno
        if (empty($file_name)) {
            $file_name = 'doc_' . uniqid() . '.pdf';
        }

        $this->headers->set('Content-Type', 'application/pdf');
        $this->headers->set('Content-Disposition', 'inline; filename="' . $file_name . '"');
        $this->headers->set('Content-Length', strlen($content));
        $this->content = $content;

        $this->send();
    }

    public function redirect(string $url, int $delay = 0): self
    {
        if ($delay > 0) {
            $this->headers->set('Refresh', $delay . '; url=' . $url);
            return $this;
        }

        $this->headers->set('Location', $url);
        return $this;
    }

    public function send(): void
    {
        $this->sendHeaders();

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

    /**
     * @deprecated replaced by setHttpCode
     */
    public function setStatusCode(int $http_code): self
    {
        return $this->setHttpCode($http_code);
    }

    public function view(string $view, array $data = []): void
    {
        $this->headers->set('Content-Type', 'text/html');

        $this->content = Html::render($view, $data);

        $this->send();
    }

    public function withoutCookie(string $name): self
    {
        $this->cookie($name, '', time() - 3600);

        return $this;
    }

    private function sendHeaders(): void
    {
        http_response_code($this->http_code);

        foreach ($this->headers->all() as $name => $value) {
            header($name . ': ' . $value);
        }

        foreach ($this->cookies as $cookie) {
            setcookie($cookie['name'], $cookie['value'], $cookie['expire'], Tools::config('route', '/'));
        }
    }
}