<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Internal\Headers;
use FacturaScripts\Core\Internal\RequestFiles;
use FacturaScripts\Core\Internal\SubRequest;

/**
 * Encapsula la petición HTTP entrante (cookies, archivos, cabeceras, parámetros GET/POST y cuerpo).
 * Se construye normalmente desde las superglobales mediante createFromGlobals(),
 * pero también admite datos arbitrarios para facilitar los tests.
 */
final class Request
{
    const METHOD_GET = 'GET';
    const METHOD_PATCH = 'PATCH';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';

    /** Cookies de la petición ($_COOKIE). @var SubRequest */
    public $cookies;

    /** Archivos subidos ($_FILES). @var RequestFiles */
    public $files;

    /** Cabeceras HTTP derivadas de $_SERVER. @var Headers */
    public $headers;

    /** Parámetros de la query string ($_GET). @var SubRequest */
    public $query;

    /** Cuerpo crudo de la petición; si es null se lee de php://input bajo demanda. @var string|null */
    private $rawInput;

    /** Parámetros del cuerpo de la petición ($_POST y PUT/PATCH form-urlencoded). @var SubRequest */
    public $request;

    /**
     * @param array $data Claves opcionales: cookies, files, headers, query, request, input.
     */
    public function __construct(array $data = [])
    {
        $this->cookies = new SubRequest($data['cookies'] ?? []);
        $this->files = new RequestFiles($data['files'] ?? []);
        $this->headers = new Headers($data['headers'] ?? []);
        $this->query = new SubRequest($data['query'] ?? []);
        $this->rawInput = $data['input'] ?? null;
        $this->request = new SubRequest($data['request'] ?? []);
    }

    /**
     * @deprecated use request->all() or query->all() instead
     */
    public function all(string ...$key): array
    {
        if (empty($key)) {
            return array_merge($this->request->all(), $this->query->all());
        }

        $result = [];
        foreach ($key as $k) {
            $result[$k] = $this->get($k);
        }
        return $result;
    }

    /**
     * Detecta el navegador a partir del User-Agent.
     * @return string chrome, edge, firefox, safari, opera, ie o unknown.
     */
    public function browser(): string
    {
        $userAgent = $this->userAgent();
        if (stripos($userAgent, 'chrome') !== false) {
            return 'chrome';
        }
        if (stripos($userAgent, 'edg/') !== false || stripos($userAgent, 'edge') !== false) {
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

    /** Devuelve el valor de una cookie o $default si no existe. */
    public function cookie(string $key, $default = null): ?string
    {
        return $this->cookies->get($key, $default);
    }

    /**
     * Construye una Request a partir de las superglobales del entorno PHP
     * ($_COOKIE, $_FILES, $_SERVER, $_GET, $_POST y php://input para PUT/PATCH).
     */
    public static function createFromGlobals(): self
    {
        return new self([
            'cookies' => $_COOKIE,
            'files' => $_FILES,
            'headers' => $_SERVER,
            'query' => $_GET,
            'request' => self::parseRequestData(),
        ]);
    }

    /** Devuelve el archivo subido asociado a $key, o null si no existe. */
    public function file(string $key): ?UploadedFile
    {
        return $this->files->get($key);
    }

    /** URL absoluta completa: protocolo, host, path y query string. */
    public function fullUrl(): string
    {
        return $this->protocol() . '://' . $this->host() . $this->urlWithQuery();
    }

    /**
     * @deprecated use input(), inputOrQuery(), query() or queryOrInput() instead
     */
    public function get(string $key, $default = null): ?string
    {
        if ($this->query->has($key)) {
            return $this->query->get($key);
        }

        return $this->request->get($key, $default);
    }

    /**
     * @deprecated use request->getArray() or query->getArray() instead
     */
    public function getArray(string $key): array
    {
        if ($this->query->has($key)) {
            return $this->query->getArray($key);
        }

        return $this->request->getArray($key);
    }

    /**
     * @deprecated use request->getAlnum() or query->getAlnum() instead
     */
    public function getAlnum(string $key): string
    {
        if ($this->query->has($key)) {
            return $this->query->getAlnum($key);
        }

        return $this->request->getAlnum($key);
    }

    /** Devuelve el path de REQUEST_URI sin la query string (ej: "/admin/users"). */
    public function getBasePath(): string
    {
        $url = $_SERVER['REQUEST_URI'];
        $base = parse_url($url, PHP_URL_PATH);
        return is_string($base) ? $base : '';
    }

    /**
     * @deprecated use request->getBool() or query->getBool() instead
     */
    public function getBool(string $key, ?bool $default = null): ?bool
    {
        if ($this->query->has($key)) {
            return $this->query->getBool($key, $default);
        }

        return $this->request->getBool($key, $default);
    }

    /** Devuelve el cuerpo crudo de la petición (rawInput inyectado o php://input). */
    public function getContent(): string
    {
        return $this->rawInput ?? file_get_contents('php://input');
    }

    /**
     * @deprecated use request->getDate() or query->getDate() instead
     */
    public function getDate(string $key, ?string $default = null): ?string
    {
        if ($this->query->has($key)) {
            return $this->query->getDate($key, $default);
        }

        return $this->request->getDate($key, $default);
    }

    /**
     * @deprecated use request->getDateTime() or query->getDateTime() instead
     */
    public function getDateTime(string $key, ?string $default = null): ?string
    {
        if ($this->query->has($key)) {
            return $this->query->getDateTime($key, $default);
        }

        return $this->request->getDateTime($key, $default);
    }

    /**
     * @deprecated use request->getEmail() or query->getEmail() instead
     */
    public function getEmail(string $key, ?string $default = null): ?string
    {
        if ($this->query->has($key)) {
            return $this->query->getEmail($key, $default);
        }

        return $this->request->getEmail($key, $default);
    }

    /**
     * @deprecated use request->getFloat() or query->getFloat() instead
     */
    public function getFloat(string $key, ?float $default = null): ?float
    {
        if ($this->query->has($key)) {
            return $this->query->getFloat($key, $default);
        }

        return $this->request->getFloat($key, $default);
    }

    /**
     * @deprecated use request->getHour() or query->getHour() instead
     */
    public function getHour(string $key, ?string $default = null): ?string
    {
        if ($this->query->has($key)) {
            return $this->query->getHour($key, $default);
        }

        return $this->request->getHour($key, $default);
    }

    /**
     * @deprecated use request->getInt() or query->getInt() instead
     */
    public function getInt(string $key, ?int $default = null): ?int
    {
        if ($this->query->has($key)) {
            return $this->query->getInt($key, $default);
        }

        return $this->request->getInt($key, $default);
    }

    /**
     * @return string
     * @deprecated use method() instead
     */
    public function getMethod(): string
    {
        return $this->method();
    }

    /**
     * @deprecated use request->getOnly() or query->getOnly() instead
     */
    public function getOnly(string $key, array $values): ?string
    {
        if ($this->query->has($key)) {
            return $this->query->getOnly($key, $values);
        }

        return $this->request->getOnly($key, $values);
    }

    /**
     * @deprecated use request->getString() or query->getString() instead
     */
    public function getString(string $key, ?string $default = null): ?string
    {
        if ($this->query->has($key)) {
            return $this->query->getString($key, $default);
        }

        return $this->request->getString($key, $default);
    }

    /**
     * @deprecated use request->getUrl() or query->getUrl() instead
     */
    public function getUrl(string $key, ?string $default = null): ?string
    {
        if ($this->query->has($key)) {
            return $this->query->getUrl($key, $default);
        }

        return $this->request->getUrl($key, $default);
    }

    /**
     * Comprueba que TODAS las claves indicadas estén presentes en query o request.
     * Devuelve false si alguna falta o si no se pasa ninguna clave.
     */
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

    /** Devuelve la cabecera HTTP solicitada o $default. */
    public function header(string $key, $default = null): ?string
    {
        return $this->headers->get($key, $default);
    }

    /** Host de la petición (HTTP_HOST) o cadena vacía si no se conoce. */
    public function host(): string
    {
        return $_SERVER['HTTP_HOST'] ?? '';
    }

    /** Lee un valor solo del cuerpo de la petición (POST/PUT/PATCH). */
    public function input(string $key, $default = null): ?string
    {
        return $this->request->get($key, $default);
    }

    /** Lee del cuerpo y, si no existe, recurre a la query string. */
    public function inputOrQuery(string $key, $default = null): ?string
    {
        return $this->request->has($key) ?
            $this->request->get($key) :
            $this->query->get($key, $default);
    }

    /**
     * Devuelve la IP del cliente, dando prioridad a Cloudflare y X-Forwarded-For
     * antes que REMOTE_ADDR. Si no hay nada disponible devuelve "::1".
     */
    public function ip(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $field) {
            if (!empty($_SERVER[$field])) {
                return (string)$_SERVER[$field];
            }
        }

        return '::1';
    }

    /** Comprueba si el método HTTP coincide con el indicado (GET, POST, etc.). */
    public function isMethod(string $method): bool
    {
        return $this->method() === $method;
    }

    /**
     * Decodifica el cuerpo de la petición como JSON.
     * Si $key es null devuelve el array completo; en caso contrario el valor de esa clave o $default.
     */
    public function json(?string $key = null, $default = null)
    {
        $input = $this->getContent();
        $data = json_decode($input, true);

        if ($key === null) {
            return $data;
        }

        return $data[$key] ?? $default;
    }

    /** Devuelve el método HTTP (GET, POST, PUT, PATCH, DELETE...). */
    public function method(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Detecta el sistema operativo del cliente a partir del User-Agent.
     * @return string windows, mac, linux, unix, sun, bsd o unknown.
     */
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

    /**
     * Construye el array de datos de la petición.
     * Para PUT/PATCH con application/x-www-form-urlencoded, PHP no rellena $_POST,
     * así que parseamos manualmente el cuerpo desde php://input.
     */
    public static function parseRequestData(): array
    {
        $request = $_POST;

        if (!array_key_exists('REQUEST_METHOD', $_SERVER)) {
            return $request;
        }

        if ($_SERVER['REQUEST_METHOD'] === self::METHOD_PUT || $_SERVER['REQUEST_METHOD'] === self::METHOD_PATCH) {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

            if (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
                parse_str(file_get_contents('php://input'), $request);
            }
        }

        return $request;
    }

    /** Protocolo del servidor (p.ej. HTTP/1.1) o cadena vacía. */
    public function protocol(): string
    {
        return $_SERVER['SERVER_PROTOCOL'] ?? '';
    }

    /** Lee un valor solo de la query string. */
    public function query(string $key, $default = null): ?string
    {
        return $this->query->get($key, $default);
    }

    /** Lee de la query y, si no existe, recurre al cuerpo de la petición. */
    public function queryOrInput(string $key, $default = null): ?string
    {
        return $this->query->has($key) ?
            $this->query->get($key) :
            $this->request->get($key, $default);
    }

    /** Indica si la petición se ha realizado por HTTPS. */
    public function isSecure(): bool
    {
        return $this->protocol() === 'https';
    }

    /**
     * Devuelve la URL relativa de la petición, eliminada la query string y el prefijo FS_ROUTE.
     *
     * @param int|null $position Si es null devuelve la URL completa.
     *                           Si es un índice, devuelve el segmento del path en esa posición
     *                           (admite negativos para contar desde el final).
     */
    public function url(?int $position = null): string
    {
        // si contiene '?', lo quitamos y lo que venga después
        $url = explode('?', $_SERVER['REQUEST_URI'])[0];

        // si el principio coincide con FS_ROUTE, lo quitamos
        $route = Tools::config('route');
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

    /** URL relativa con la query string añadida (incluye el "?" aunque esté vacía). */
    public function urlWithQuery(): string
    {
        return $this->url() . '?' . $_SERVER['QUERY_STRING'];
    }

    /** User-Agent enviado por el cliente, o cadena vacía. */
    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
}
