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

final class Request
{
    /** @var string */
    private $cast = 'string';

    /** @var array */
    private $only = [];

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
        return $this->transform($_COOKIE[$key] ?? $default);
    }

    public function cookies(string ...$key): array
    {
        if (empty($key)) {
            return $_COOKIE;
        }

        $values = [];
        foreach ($key as $k) {
            $values[$k] = $this->transform($_COOKIE[$k] ?? null);
        }
        return $values;
    }

    public function file(string $key, $default = null)
    {
        return $_FILES[$key] ?? $default;
    }

    public function files(string ...$key): array
    {
        if (empty($key)) {
            return $_FILES;
        }

        $values = [];
        foreach ($key as $k) {
            $values[$k] = $_FILES[$k] ?? null;
        }
        return $values;
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
            if (!isset($_COOKIE[$k])) {
                return false;
            }
        }
        return true;
    }

    public function hasFile(string ...$key): bool
    {
        foreach ($key as $k) {
            if (!isset($_FILES[$k])) {
                return false;
            }
        }
        return true;
    }

    public function hasHeader(string ...$key): bool
    {
        foreach ($key as $k) {
            $k = 'HTTP_' . strtoupper(str_replace('-', '_', $k));
            if (!isset($_SERVER[$k])) {
                return false;
            }
        }
        return true;
    }

    public function hasInput(string ...$key): bool
    {
        foreach ($key as $k) {
            if (!isset($_POST[$k])) {
                return false;
            }
        }
        return true;
    }

    public function hasQuery(string ...$key): bool
    {
        foreach ($key as $k) {
            if (!isset($_GET[$k])) {
                return false;
            }
        }
        return true;
    }

    public function header(string $key, $default = null)
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $this->transform($_SERVER[$key] ?? $default);
    }

    public function headers(string ...$key): array
    {
        if (empty($key)) {
            return $_SERVER;
        }

        $values = [];
        foreach ($key as $k) {
            $k = 'HTTP_' . strtoupper(str_replace('-', '_', $k));
            $values[$k] = $this->transform($_SERVER[$k] ?? null);
        }
        return $values;
    }

    public function host(): string
    {
        return $_SERVER['HTTP_HOST'] ?? '';
    }

    public function input(string $key, $default = null)
    {
        return $this->transform($_POST[$key] ?? $default);
    }

    public function inputs(string ...$key): array
    {
        if (empty($key)) {
            return $_POST;
        }

        $values = [];
        foreach ($key as $k) {
            $values[$k] = $this->transform($_POST[$k] ?? null);
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
            if (isset($_COOKIE[$k])) {
                return false;
            }
        }
        return true;
    }

    public function isFileMissing(string ...$key): bool
    {
        foreach ($key as $k) {
            if (isset($_FILES[$k])) {
                return false;
            }
        }
        return true;
    }

    public function isHeaderMissing(string ...$key): bool
    {
        foreach ($key as $k) {
            $k = 'HTTP_' . strtoupper(str_replace('-', '_', $k));
            if (isset($_SERVER[$k])) {
                return false;
            }
        }
        return true;
    }

    public function isInputMissing(string ...$key): bool
    {
        foreach ($key as $k) {
            if (isset($_POST[$k])) {
                return false;
            }
        }
        return true;
    }

    public function isQueryMissing(string ...$key): bool
    {
        foreach ($key as $k) {
            if (isset($_GET[$k])) {
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
            return $_GET;
        }

        $values = [];
        foreach ($key as $k) {
            $values[$k] = $this->transform($_GET[$k] ?? null);
        }
        return $values;
    }

    public function query(string $key, $default = null)
    {
        return $this->transform($_GET[$key] ?? $default);
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
