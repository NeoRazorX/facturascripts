<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Tools;

class Files implements ControllerInterface
{
    /** @var string */
    private $filePath = '';

    public function __construct(string $className, string $url = '')
    {
        if (empty($url)) {
            return;
        }

        // favicon.ico
        if ('/favicon.ico' == $url) {
            $this->filePath = Tools::folder('Core', 'Assets', 'Images', 'favicon.ico');
            return;
        }

        $this->filePath = Tools::folder() . $url;

        if (false === is_file($this->filePath)) {
            throw new KernelException(
                'FileNotFound',
                Tools::lang()->trans('file-not-found', ['%fileName%' => $url])
            );
        }

        if (false === $this->isFolderSafe($url)) {
            throw new KernelException('UnsafeFolder', 'Folder not safe: ' . $url);
        }

        if (false === $this->isFileSafe($this->filePath)) {
            throw new KernelException('UnsafeFile', 'File not safe: ' . $url);
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
            'accdb', 'avi', 'cdr', 'css', 'csv', 'doc', 'docx', 'eot', 'gif', 'gz', 'html', 'ico', 'jpeg', 'jpg', 'js',
            'json', 'map', 'mdb', 'mkv', 'mp3', 'mp4', 'ndg', 'ods', 'odt', 'ogg', 'pdf', 'png', 'pptx', 'sql', 'svg',
            'ttf', 'txt', 'webm', 'woff', 'woff2', 'xls', 'xlsx', 'xml', 'xsig', 'zip'
        ];
        return empty($parts) || count($parts) === 1 || in_array(end($parts), $safe, true);
    }

    public static function isFolderSafe(string $filePath): bool
    {
        $safeFolders = ['node_modules', 'vendor', 'Dinamic', 'Core', 'Plugins', 'MyFiles/Public'];
        foreach ($safeFolders as $folder) {
            if ('/' . $folder === substr($filePath, 0, 1 + strlen($folder))) {
                return true;
            }
        }

        return false;
    }

    public function run(): void
    {
        if (empty($this->filePath)) {
            return;
        }

        header('Content-Type: ' . $this->getMime($this->filePath));

        // disable the buffer if enabled
        if (ob_get_contents()) {
            ob_end_flush();
        }

        // force to download svg files to prevent XSS attacks
        if (strpos($this->filePath, '.svg') !== false) {
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
}
