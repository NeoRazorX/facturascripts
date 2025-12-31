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
            '7z', 'accdb', 'ai', 'avi', 'cdr', 'css', 'csv', 'doc', 'docx', 'dxf', 'dwg', 'eot', 'gif', 'gz', 'html',
            'ico', 'ics', 'jfif', 'jpeg', 'jpg', 'js', 'json', 'lbx', 'map', 'md', 'mdb', 'mkv', 'mov', 'mp3', 'mp4', 'ndg',
            'ods', 'odt', 'ogg', 'pdf', 'png', 'pptx', 'rar', 'sql', 'step', 'svg', 'ttf', 'txt', 'webm', 'webp',
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

        header('Content-Type: ' . $this->getMime($this->filePath));
        header('Cache-Control: public, max-age=31536000, immutable');

        // disable the buffer if enabled
        if (ob_get_contents()) {
            ob_end_flush();
        }

        // force to download svg, xml and html files to prevent XSS attacks
        if ($this->shouldForceDownload($this->filePath)) {
            header('Content-Disposition: attachment; filename="' . basename($this->filePath) . '"');
        }

        readfile($this->filePath);
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
