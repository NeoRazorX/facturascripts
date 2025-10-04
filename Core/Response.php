<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
    public const HTTP_CONFLICT = 409;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_INTERNAL_SERVER_ERROR = 500;
    public const HTTP_METHOD_NOT_ALLOWED = 405;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_OK = 200;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_UNPROCESSABLE_ENTITY = 422;

    /** @var string */
    private $content;

    /** @var array */
    private $cookies;

    /** @var ResponseHeaders */
    public $headers;

    /** @var int */
    private $http_code;

    /** @var bool */
    private $send_disabled = false;

    /** @var bool */
    private $sent = false;

    public function __construct(int $http_code = 200)
    {
        $this->content = '';
        $this->cookies = [];
        $this->headers = new ResponseHeaders();
        $this->http_code = $http_code;
    }

    public function cookie(string $name, ?string $value, int $expire = 0, bool $httpOnly = true, ?bool $secure = null, string $sameSite = 'Lax'): self
    {
        if (empty($expire)) {
            $expire = time() + (int)Tools::config('cookies_expire');
        }

        // Si no se especifica secure, detectar si estamos en HTTPS
        if ($secure === null) {
            $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        }

        $this->cookies[$name] = [
            'name' => $name,
            'value' => $value ?? '',
            'expire' => $expire,
            'httponly' => $httpOnly,
            'secure' => $secure,
            'samesite' => $sameSite,
        ];

        return $this;
    }

    public function disableSend(bool $disable = true): self
    {
        $this->send_disabled = $disable;

        return $this;
    }

    public function download(string $file_path, string $file_name = ''): void
    {
        $this->file($file_path, $file_name, 'attachment');
    }

    public function file(string $file_path, string $file_name = '', string $disposition = 'inline'): void
    {
        // Validar que el archivo existe y es legible
        if (!file_exists($file_path) || !is_readable($file_path)) {
            $this->setHttpCode(self::HTTP_NOT_FOUND);
            $this->sendHeaders();
            return;
        }

        // Obtener la ruta real y validar que no salga del directorio permitido
        $real_path = realpath($file_path);
        if ($real_path === false) {
            $this->setHttpCode(self::HTTP_FORBIDDEN);
            $this->sendHeaders();
            return;
        }

        // Verificar que no es un directorio
        if (is_dir($real_path)) {
            $this->setHttpCode(self::HTTP_FORBIDDEN);
            $this->sendHeaders();
            return;
        }

        // Sanitizar el nombre del archivo si se proporciona
        if (false === empty($file_name)) {
            $safe_name = $this->sanitizeFileName($file_name);
            $disposition .= '; filename="' . $safe_name . '"';
        }

        // Detectar el tipo MIME del archivo
        $mime_type = mime_content_type($real_path) ?: 'application/octet-stream';

        $this->headers->set('Content-Type', $mime_type);
        $this->headers->set('Content-Disposition', $disposition);
        $this->headers->set('Content-Length', (int)filesize($real_path));

        if ($this->send_disabled) {
            return;
        }

        $this->sendHeaders();

        readfile($real_path);
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
        $safe_name = $this->sanitizeFileName($file_name, 'doc_', '.pdf');

        $this->headers->set('Content-Type', 'application/pdf');
        $this->headers->set('Content-Disposition', 'inline; filename="' . $safe_name . '"');
        $this->headers->set('Content-Length', strlen($content));

        $this->content = $content;

        $this->send();
    }

    public function redirect(string $url, int $delay = 0): self
    {
        if ($delay > 0) {
            $this->headers->set('Refresh', $delay . '; url=' . $url);
        } else {
            $this->headers->set('Location', $url);
        }

        return $this;
    }

    public function send(): void
    {
        if ($this->send_disabled || $this->sent) {
            return;
        }

        $this->sendHeaders();

        echo $this->content;

        $this->sent = true;
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
        if ($this->send_disabled) {
            return;
        }

        http_response_code($this->http_code);

        foreach ($this->headers->all() as $name => $value) {
            header($name . ': ' . $value);
        }

        foreach ($this->cookies as $cookie) {
            // Preparar opciones de cookie con flags de seguridad
            $options = [
                'expires' => $cookie['expire'],
                'path' => Tools::config('route', '/'),
                'domain' => '',
                'secure' => $cookie['secure'] ?? false,
                'httponly' => $cookie['httponly'] ?? true,
                'samesite' => $cookie['samesite'] ?? 'Lax'
            ];

            setcookie($cookie['name'], $cookie['value'], $options);
        }
    }

    private function sanitizeFileName(string $fileName, string $prefix = 'file_', string $suffix = ''): string
    {
        if (empty($fileName)) {
            return $prefix . uniqid() . $suffix;
        }

        $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);
        if (empty($sanitizedName)) {
            return $prefix . uniqid() . $suffix;
        }

        return $sanitizedName;
    }
}
