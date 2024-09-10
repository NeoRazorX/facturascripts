<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Un sencillo cliente HTTP basado en cURL.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Http
{
    /** @var string */
    protected $body;

    /** @var array */
    private $curlOptions;

    /** @var mixed */
    protected $data;

    /** @var string */
    public $error;

    /** @var bool */
    private $executed = false;

    /** @var array */
    private $headers = [];

    /** @var string */
    protected $method;

    /** @var array */
    private $responseHeaders = [];

    /** @var int */
    private $statusCode = 0;

    /** @var string */
    protected $url;

    public function __construct(string $method, string $url, $data = [])
    {
        $this->method = $method;
        $this->url = $url;
        $this->data = $data;

        $this->curlOptions = [
            CURLOPT_AUTOREFERER => 1,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36'
        ];
    }

    public function body(): string
    {
        if (!$this->executed) {
            $this->exec();
        }

        return $this->body;
    }

    public static function delete(string $url, $data = []): self
    {
        return new self('DELETE', $url, $data);
    }

    public function errorMessage(): string
    {
        if (!$this->executed) {
            $this->exec();
        }

        return $this->error;
    }

    public function failed(): bool
    {
        return !$this->ok();
    }

    public static function get(string $url, $data = []): self
    {
        return new self('GET', $url, $data);
    }

    public function header(string $key): string
    {
        if (!$this->executed) {
            $this->exec();
        }

        return array_key_exists(strtolower($key), $this->responseHeaders) ?
            $this->responseHeaders[strtolower($key)][0] :
            '';
    }

    public function headers(): array
    {
        if (!$this->executed) {
            $this->exec();
        }

        return $this->responseHeaders;
    }

    public function json(bool $associative = true)
    {
        if (!$this->executed) {
            $this->exec();
        }

        return json_decode($this->body, $associative);
    }

    public function notFound(): bool
    {
        if (!$this->executed) {
            $this->exec();
        }

        return $this->statusCode === 404;
    }

    public function ok(): bool
    {
        if (!$this->executed) {
            $this->exec();
        }

        return in_array($this->statusCode, [200, 201, 202]);
    }

    public static function post(string $url, $data = []): self
    {
        return new self('POST', $url, $data);
    }

    public static function postJson(string $url, array $data = []): self
    {
        return self::post($url, json_encode($data))
            ->setHeader('Content-Type', 'application/json');
    }

    public static function put(string $url, $data = []): self
    {
        return new self('PUT', $url, $data);
    }

    public function saveAs(string $filename): bool
    {
        if (!$this->executed) {
            $this->exec();
        }

        if ($this->statusCode !== 200) {
            return false;
        }

        return file_put_contents($filename, $this->body) !== false;
    }

    public function setBearerToken(string $token): self
    {
        return $this->setHeader('Authorization', 'Bearer ' . $token);
    }

    public function setCurlOption(int $option, $value): self
    {
        $this->curlOptions[$option] = $value;
        return $this;
    }

    public function setHeader(string $key, string $value): self
    {
        $this->headers[$key] = $key . ': ' . $value;
        return $this;
    }

    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    public function setTimeout(int $timeout): self
    {
        return $this->setCurlOption(CURLOPT_TIMEOUT, $timeout);
    }

    public function setToken(string $token): self
    {
        return $this->setHeader('Token', $token);
    }

    public function setUser(string $user, string $password): self
    {
        return $this->setCurlOption(CURLOPT_USERPWD, $user . ':' . $password);
    }

    public function setUserAgent(string $userAgent): self
    {
        return $this->setCurlOption(CURLOPT_USERAGENT, $userAgent);
    }

    public function status(): int
    {
        if (!$this->executed) {
            $this->exec();
        }

        return $this->statusCode;
    }

    protected function exec(): void
    {
        $ch = curl_init();
        curl_setopt_array($ch, $this->curlOptions);

        // añadimos las cabeceras
        if (!empty($this->headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        }

        switch ($this->method) {
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (is_array($this->data)) {
                    curl_setopt($ch, CURLOPT_URL, $this->url . '?' . http_build_query($this->data));
                    break;
                }
                curl_setopt($ch, CURLOPT_URL, $this->url);
                break;

            case 'POST':
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_URL, $this->url);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->getPostFields());
                break;

            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_URL, $this->url);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->getPostFields());
                break;

            default:
                // GET
                if (is_array($this->data) && !empty($this->data)) {
                    curl_setopt($ch, CURLOPT_URL, $this->url . '?' . http_build_query($this->data));
                    break;
                }
                curl_setopt($ch, CURLOPT_URL, $this->url);
                break;
        }

        // guardamos las cabeceras de respuesta
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) {
            $len = strlen($header);
            $header = explode(':', $header, 2);
            if (count($header) < 2) {
                return $len;
            }
            $this->responseHeaders[strtolower(trim($header[0]))][] = trim($header[1]);
            return $len;
        });

        $this->body = curl_exec($ch);
        $this->statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->error = curl_error($ch);
        curl_close($ch);

        $this->executed = true;
    }

    protected function getPostFields()
    {
        // si el Content-Type es multipart/form-data, devolvemos los datos sin codificar
        if (stripos($this->headers['Content-Type'] ?? '', 'multipart/form-data') !== false) {
            return $this->data;
        }

        // si data es un array, lo codificamos
        return is_array($this->data) ? http_build_query($this->data) : $this->data;
    }
}
