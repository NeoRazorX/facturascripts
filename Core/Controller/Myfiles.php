<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Contract\ControllerInterface;
use FacturaScripts\Core\KernelException;
use FacturaScripts\Core\Lib\MyFilesToken;
use FacturaScripts\Core\Tools;

class Myfiles implements ControllerInterface
{
    /** @var string */
    private $filePath = '';

    public function __construct(string $className, string $url = '')
    {
        if (empty($url)) {
            return;
        }

        // url starts with /MyFiles/ ?
        if (strpos($url, '/MyFiles/') !== 0) {
            return;
        }

        $this->filePath = Tools::folder() . urldecode($url);

        if (false === is_file($this->filePath)) {
            throw new KernelException(
                'FileNotFound',
                Tools::trans('file-not-found', ['%fileName%' => $url])
            );
        }

        if (false === $this->isFileSafe($this->filePath)) {
            throw new KernelException('UnsafeFile', $url);
        }

        // if the folder is MyFiles/Public, then we don't need to check the token
        if (strpos($url, '/MyFiles/Public/') === 0) {
            return;
        }

        // get the myft parameter
        $fixedFilePath = substr(urldecode($url), 1);
        $token = filter_input(INPUT_GET, 'myft');
        if (empty($token) || false === MyFilesToken::validate($fixedFilePath, $token)) {
            throw new KernelException('MyfilesTokenError', $fixedFilePath);
        }
    }

    public function getPageData(): array
    {
        return [];
    }

    public static function isFileSafe(string $filePath): bool
    {
        $parts = explode('.', $filePath);
        $safe = [
            '7z', 'accdb', 'ai', 'aac', 'avi', 'cdr', 'css', 'csv', 'doc', 'docx', 'dxf', 'dwg', 'eot', 'flac', 'gif', 'gz', 'html',
            'ico', 'ics', 'jfif', 'jpeg', 'jpg', 'js', 'json', 'm4a', 'map', 'md', 'mdb', 'mkv', 'mov', 'mp3', 'mp4', 'ndg',
            'ods', 'odt', 'ogg', 'pdf', 'png', 'pptx', 'rar', 'sql', 'step', 'svg', 'ttf', 'txt', 'wav', 'webm', 'webp',
            'woff', 'woff2', 'xls', 'xlsm', 'xlsx', 'xml', 'xsig', 'zip'
        ];
        $extension = strtolower(end($parts));
        return empty($parts) || count($parts) === 1 || in_array($extension, $safe, true);
    }

    public function run(): void
    {
        if (empty($this->filePath)) {
            return;
        }

        $mimeType = $this->getMime($this->filePath);
        $fileSize = filesize($this->filePath);

        // Permite acceso CORS para que Safari pueda cargar recursos
        header('Access-Control-Allow-Origin: *');

        // Manejo de Range Requests para Safari (crucial para audio)
        $start = 0;
        $end = $fileSize - 1;

        if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches)) {
            $start = intval($matches[1]);
            $end = $matches[2] !== '' ? intval($matches[2]) : $end;

            header('HTTP/1.1 206 Partial Content');
            header('Content-Range: bytes ' . $start . '-' . $end . '/' . $fileSize);
            header('Content-Length: ' . ($end - $start + 1));
        } else {
            header('HTTP/1.1 200 OK');
            header('Content-Length: ' . $fileSize);
        }

        header('Content-Type: ' . $mimeType);
        header('Accept-Ranges: bytes');
        header('Cache-Control: public, max-age=31536000, immutable');

        // Para archivos de audio, nunca descargar - siempre reproducir inline
        if (strpos($mimeType, 'audio') === 0) {
            header('Content-Disposition: inline; filename="' . basename($this->filePath) . '"');
        } else {
            // force to download svg, xml and html files to prevent XSS attacks
            if ($this->shouldForceDownload($this->filePath)) {
                header('Content-Disposition: attachment; filename="' . basename($this->filePath) . '"');
            }
        }

        // disable the buffer if enabled
        if (ob_get_contents()) {
            ob_end_flush();
        }

        // Enviar el archivo
        if (isset($_SERVER['HTTP_RANGE'])) {
            // Enviar solo el rango solicitado
            $file = fopen($this->filePath, 'rb');
            fseek($file, $start);
            echo fread($file, $end - $start + 1);
            fclose($file);
        } else {
            // Enviar el archivo completo
            readfile($this->filePath);
        }
    }

    private function getMime(string $filePath): string
    {
        $info = pathinfo($filePath);
        $extension = strtolower($info['extension']);
        switch ($extension) {
            case 'css':
                return 'text/css';

            case 'js':
                return 'application/javascript';

            case 'xml':
            case 'xsig':
                return 'text/xml';

            // Formatos de audio - asegurar tipos MIME correctos
            case 'm4a':
                return 'audio/mp4';

            case 'mp3':
                return 'audio/mpeg';

            case 'wav':
                return 'audio/wav';

            case 'ogg':
                return 'audio/ogg';

            case 'webm':
                return 'audio/webm';

            case 'aac':
                return 'audio/aac';

            case 'flac':
                return 'audio/flac';
        }

        return mime_content_type($filePath);
    }

    private function shouldForceDownload(string $filePath): bool
    {
        // verificar extensión
        $info = pathinfo($filePath);
        if (isset($info['extension'])) {
            $extension = strtolower($info['extension']);
            $dangerousExtensions = ['svg', 'xml', 'xsig', 'html', 'htm', 'xhtml'];
            if (in_array($extension, $dangerousExtensions, true)) {
                return true;
            }
        }

        // verificar MIME type detectado (por si el archivo está renombrado)
        $mime = $this->getMime($filePath);
        $dangerousMimes = ['text/html', 'text/xml', 'application/xml', 'image/svg+xml', 'application/xhtml+xml'];
        foreach ($dangerousMimes as $dangerousMime) {
            if (strpos($mime, $dangerousMime) !== false) {
                return true;
            }
        }

        return false;
    }
}
