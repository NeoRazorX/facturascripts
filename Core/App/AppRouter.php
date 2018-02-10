<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\App;

/**
 * Description of AppRouter
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AppRouter
{

    public function __construct()
    {
        if (!defined('FS_ROUTE')) {
            define('FS_ROUTE', '');
        }
    }

    public function getApp()
    {
        $uri = $this->getUri();
        if ('/api' === $uri || '/api/' === substr($uri, 0, 5)) {
            return new AppAPI($uri);
        }

        if ('/cron' === $uri) {
            return new AppCron($uri);
        }

        return new AppController($uri);
    }

    public function getFile()
    {
        $uri = $this->getUri();
        $filePath = FS_FOLDER . $uri;

        /// Not a file?
        if (!is_file($filePath)) {
            return false;
        }

        /// Forbidden folder?
        $allowedFolders = ['node_modules', 'vendor', 'Dinamic', 'Core'];
        foreach ($allowedFolders as $folder) {
            if ('/' . $folder === substr($uri, 0, strlen($folder) + 1)) {
                header('Content-Type: ' . $this->getMime($filePath));
                readfile($filePath);
                return true;
            }
        }

        return false;
    }

    private function getMime($filePath)
    {
        if (substr($filePath, -4) === '.css') {
            return 'text/css';
        }

        if (substr($filePath, -3) === '.js') {
            return 'application/javascript';
        }

        return mime_content_type($filePath);
    }

    private function getUri()
    {
        $uri = filter_input(INPUT_SERVER, 'REQUEST_URI');
        $uriArray = explode('?', $uri);

        return substr($uriArray[0], strlen(FS_ROUTE));
    }
}
