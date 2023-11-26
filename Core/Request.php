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

use FacturaScripts\Core\Internal\RequestFiles;
use FacturaScripts\Core\Internal\RequestString;
use FacturaScripts\Core\Internal\SubRequest;
use FacturaScripts\Core\Internal\UploadedFile;

final class Request
{
    /** @var SubRequest */
    public $cookies;

    /** @var RequestFiles */
    public $files;

    /** @var SubRequest */
    public $headers;

    /** @var SubRequest */
    public $query;

    /** @var SubRequest */
    public $request;

    public function __construct(array $data = [])
    {
        $this->cookies = new SubRequest($data['cookies'] ?? []);
        $this->files = new RequestFiles($data['files'] ?? []);
        $this->headers = new SubRequest($data['headers'] ?? []);
        $this->query = new SubRequest($data['query'] ?? []);
        $this->request = new SubRequest($data['request'] ?? []);
    }

    /**
     * @param string ...$key
     * @return RequestString[]
     */
    public function all(string ...$key): array
    {
        if (empty($key)) {
            return array_merge($this->query->all(), $this->request->all());
        }

        $result = [];
        foreach ($key as $k) {
            $result[$k] = $this->get($k);
        }
        return $result;
    }

    public function browser(): string
    {
        $userAgent = $this->userAgent();
        if (stripos($userAgent, 'chrome') !== false) {
            return 'chrome';
        }
        if (stripos($userAgent, 'edge') !== false) {
            return 'edge';
        }
        if (stripos($userAgent, 'firefox') !== false) {
            return 'firefox';
        }
        if (stripos($userAgent, 'safari') !== false) {
            return 'safari';
        }
        if (stripos($userAgent, 'opera') !== false) {
            return 'opera';
        }
        if (stripos($userAgent, 'msie') !== false) {
            return 'ie';
        }
        return 'unknown';
    }

    public function cookie(string $key, $default = null): RequestString
    {
        return $this->cookies->get($key, $default);
    }

    public static function createFromGlobals(): self
    {
        return new self([
            'cookies' => $_COOKIE,
            'files' => $_FILES,
            'headers' => getallheaders(),
            'query' => $_GET,
            'request' => $_POST,
        ]);
    }

    public function file(string $key): ?UploadedFile
    {
        return $this->files->get($key);
    }

    public function fullUrl(): string
    {
        return $this->protocol() . '://' . $this->host() . $this->urlWithQuery();
    }

    public function get(string $key, $default = null): RequestString
    {
        if ($this->request->has($key)) {
            return $this->request->get($key);
        }

        return $this->query->get($key, $default);
    }

    public function has(string ...$key): bool
    {
        $found = false;
        foreach ($key as $k) {
            if ($this->request->has($k) || $this->query->has($k)) {
                $found = true;
                continue;
            }

            return false;
        }

        return $found;
    }

    public function header(string $key, $default = null): RequestString
    {
        return $this->headers->get($key, $default);
    }

    public function host(): string
    {
        return $_SERVER['HTTP_HOST'] ?? '';
    }

    public function input(string $key, $default = null): RequestString
    {
        return $this->request->get($key, $default);
    }

    public function ip(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $field) {
            if (isset($_SERVER[$field])) {
                return (string)$_SERVER[$field];
            }
        }

        return '::1';
    }

    public function isMethod(string $method): bool
    {
        return $this->method() === $method;
    }

    public function method(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function os(): string
    {
        $userAgent = $this->userAgent();
        if (stripos($userAgent, 'windows') !== false) {
            return 'windows';
        }
        if (stripos($userAgent, 'macintosh') !== false) {
            return 'mac';
        }
        if (stripos($userAgent, 'linux') !== false) {
            return 'linux';
        }
        if (stripos($userAgent, 'unix') !== false) {
            return 'unix';
        }
        if (stripos($userAgent, 'sunos') !== false) {
            return 'sun';
        }
        if (stripos($userAgent, 'bsd') !== false) {
            return 'bsd';
        }
        return 'unknown';
    }

    public function protocol(): string
    {
        return $_SERVER['SERVER_PROTOCOL'] ?? '';
    }

    public function query(string $key, $default = null): RequestString
    {
        return $this->query->get($key, $default);
    }

    public function url(?int $position = null): string
    {
        // si contiene '?', lo quitamos y lo que venga después
        $url = explode('?', $_SERVER['REQUEST_URI'])[0];

        // si el principio coincide con FS_ROUTE, lo quitamos
        $route = FS_ROUTE;
        if (substr($url, 0, strlen($route)) === $route) {
            $url = substr($url, strlen($route));
        }

        // si posición es null, devolvemos la url completa
        if (null === $position) {
            return $url;
        }

        $path = explode('/', $url);

        // si la posición es negativa, la contamos desde el final
        if ($position < 0) {
            $position = count($path) + $position;
        }

        return $path[$position] ?? '';
    }

    public function urlWithQuery(): string
    {
        return $this->url() . '?' . $_SERVER['QUERY_STRING'];
    }

    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
}
