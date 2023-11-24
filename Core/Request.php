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
use FacturaScripts\Core\Internal\SubRequest;

final class Request
{
    /** @var string */
    private $cast = 'string';

    /** @var SubRequest */
    public $cookies;

    /** @var RequestFiles */
    public $files;

    /** @var SubRequest */
    public $headers;

    /** @var array */
    private $only = [];

    /** @var SubRequest */
    public $query;

    /** @var SubRequest */
    public $request;

    public function __construct(array $cookies = [], array $headers = [], array $query = [], array $request = [])
    {
        $this->cookies = new SubRequest($cookies);
        $this->files = new RequestFiles();
        $this->headers = new SubRequest($headers);
        $this->query = new SubRequest($query);
        $this->request = new SubRequest($request);
    }

    public function all(string ...$key): array
    {
        if (empty($key)) {
            return $_REQUEST;
        }

        $values = [];
        foreach ($key as $k) {
            $values[$k] = $this->transform($_REQUEST[$k] ?? null);
        }
        return $values;
    }

    public function asArray(): self
    {
        $this->cast = 'array';

        return $this;
    }

    public function asBool(): self
    {
        $this->cast = 'bool';

        return $this;
    }

    public function asDate(): self
    {
        $this->cast = 'date';

        return $this;
    }

    public function asDateTime(): self
    {
        $this->cast = 'datetime';

        return $this;
    }

    public function asEmail(): self
    {
        $this->cast = 'email';

        return $this;
    }

    public function asFloat(): self
    {
        $this->cast = 'float';

        return $this;
    }

    public function asHour(): self
    {
        $this->cast = 'hour';

        return $this;
    }

    public function asInt(): self
    {
        $this->cast = 'int';

        return $this;
    }

    public function asOnly(array $values): self
    {
        $this->cast = 'only';
        $this->only = $values;

        return $this;
    }

    public function asString(): self
    {
        $this->cast = 'string';

        return $this;
    }

    public function asUrl(): self
    {
        $this->cast = 'url';

        return $this;
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

    public function cookie(string $key, $default = null)
    {
        return $this->transform($this->cookies->get($key, $default));
    }

    public function cookies(string ...$key): array
    {
        if (empty($key)) {
            return $this->cookies->all();
        }

        $values = [];
        foreach ($key as $k) {
            $values[$k] = $this->cookie($k);
        }
        return $values;
    }

    public static function createFromGlobals(): self
    {
        return new self($_COOKIE, $_SERVER, $_GET, $_POST);
    }

    public function file(string $key, $default = null)
    {
        return $this->files->get($key, $default);
    }

    public function files(string ...$key): array
    {
        if (empty($key)) {
            return $this->files->all();
        }

        $values = [];
        foreach ($key as $k) {
            $values[$k] = $this->file($k);
        }
        return $values;
    }

    public function fullUrl(): string
    {
        return $this->protocol() . '://' . $this->host() . $this->urlWithQuery();
    }

    public function get(string $key, $default = null)
    {
        return $this->transform($_REQUEST[$key] ?? $default);
    }

    public function has(string ...$key): bool
    {
        foreach ($key as $k) {
            if (!isset($_REQUEST[$k])) {
                return false;
            }
        }
        return true;
    }

    public function hasCookie(string ...$key): bool
    {
        foreach ($key as $k) {
            if (!$this->cookies->has($k)) {
                return false;
            }
        }
        return true;
    }

    public function hasFile(string ...$key): bool
    {
        foreach ($key as $k) {
            if (!$this->files->has($k)) {
                return false;
            }
        }
        return true;
    }

    public function hasHeader(string ...$key): bool
    {
        foreach ($key as $k) {
            if (!$this->headers->has($k)) {
                return false;
            }
        }
        return true;
    }

    public function hasInput(string ...$key): bool
    {
        foreach ($key as $k) {
            if (!$this->request->has($k)) {
                return false;
            }
        }
        return true;
    }

    public function hasQuery(string ...$key): bool
    {
        foreach ($key as $k) {
            if (!$this->query->has($k)) {
                return false;
            }
        }
        return true;
    }

    public function header(string $key, $default = null)
    {
        return $this->transform($this->headers->get($key, $default));
    }

    public function headers(string ...$key): array
    {
        if (empty($key)) {
            return $this->headers->all();
        }

        $values = [];
        foreach ($key as $k) {
            $values[$k] = $this->header($k);
        }
        return $values;
    }

    public function host(): string
    {
        return $_SERVER['HTTP_HOST'] ?? '';
    }

    public function input(string $key, $default = null)
    {
        return $this->transform($this->request->get($key, $default));
    }

    public function inputs(string ...$key): array
    {
        if (empty($key)) {
            return $this->request->all();
        }

        $values = [];
        foreach ($key as $k) {
            $values[$k] = $this->input($k);
        }
        return $values;
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

    public function isCookieMissing(string ...$key): bool
    {
        foreach ($key as $k) {
            if ($this->cookies->has($k)) {
                return false;
            }
        }
        return true;
    }

    public function isFileMissing(string ...$key): bool
    {
        foreach ($key as $k) {
            if ($this->files->has($k)) {
                return false;
            }
        }
        return true;
    }

    public function isHeaderMissing(string ...$key): bool
    {
        foreach ($key as $k) {
            if ($this->headers->has($k)) {
                return false;
            }
        }
        return true;
    }

    public function isInputMissing(string ...$key): bool
    {
        foreach ($key as $k) {
            if ($this->request->has($k)) {
                return false;
            }
        }
        return true;
    }

    public function isQueryMissing(string ...$key): bool
    {
        foreach ($key as $k) {
            if ($this->query->has($k)) {
                return false;
            }
        }
        return true;
    }

    public function isMethod(string $method): bool
    {
        return $this->method() === $method;
    }

    public function method(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function missing(string ...$key): bool
    {
        foreach ($key as $k) {
            if (isset($_REQUEST[$k])) {
                return false;
            }
        }
        return true;
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

    public function queries(string ...$key): array
    {
        if (empty($key)) {
            return $this->query->all();
        }

        $values = [];
        foreach ($key as $k) {
            $values[$k] = $this->query($k);
        }
        return $values;
    }

    public function query(string $key, $default = null)
    {
        return $this->transform($this->query->get($key, $default));
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

    private function transform($value)
    {
        $cast = $this->cast;
        $only = $this->only;

        // ponemos el cast por defecto
        $this->cast = 'string';
        $this->only = [];

        if (is_null($value)) {
            return null;
        }

        switch ($cast) {
            case 'array':
                return (array)$value;

            case 'bool':
                return (bool)$value;

            case 'date':
                return Tools::date($value);

            case 'datetime':
                return Tools::dateTime($value);

            case 'email':
                return Validator::email($value) ? $value : null;

            case 'float':
                return (float)$value;

            case 'hour':
                return Tools::hour($value);

            case 'int':
                return (int)$value;

            case 'only':
                return in_array($value, $only) ? $value : null;

            case 'string':
                return (string)$value;

            case 'url':
                return Validator::url($value) ? $value : null;

            default:
                return $value;
        }
    }
}
