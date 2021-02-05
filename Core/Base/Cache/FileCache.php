<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Base\Cache;

use FacturaScripts\Core\Base\ToolBox;

/**
 * Simple file cache
 * This class is great for those who can't use apc or memcached in their projects.
 *
 * @author Emilio Cobos (emiliocobos.net)   <ecoal95@gmail.com> and github contributors
 * @author Carlos García Gómez              <carlos@facturascripts.com>
 *
 */
class FileCache implements AdaptorInterface
{

    /**
     * Cache configuration
     *
     * @var array
     */
    private static $config;

    /**
     * FileCache constructor.
     */
    public function __construct()
    {
        if (!isset(self::$config)) {
            self::$config = [
                'cache_path' => \FS_FOLDER . '/MyFiles/Cache/FileCache',
                'expires' => 3600,
            ];

            $dir = self::$config['cache_path'];
            if (!$this->toolBox()->files()->createFolder($dir, true)) {
                $this->toolBox()->i18nLog()->critical('cant-create-folder', ['%folderName%' => $dir]);
            }
        }
    }

    /**
     * Flush all cache.
     *
     * @return bool always true
     */
    public function clear()
    {
        foreach (scandir(self::$config['cache_path'], SCANDIR_SORT_ASCENDING) as $fileName) {
            if (substr($fileName, -4) === '.php') {
                unlink(self::$config['cache_path'] . '/' . $fileName);
            }
        }

        return true;
    }

    /**
     * Delete data from cache.
     *
     * @param string $key
     *
     * @return bool true if the data was removed successfully
     */
    public function delete($key)
    {
        $route = $this->getRoute($key);
        if (file_exists($route)) {
            return unlink($route);
        }

        return true;
    }

    /**
     * Get the data associated with a key.
     *
     * @param string $key
     *
     * @return mixed the content you put in, or null if expired or not found
     */
    public function get($key)
    {
        $file = $this->getRoute($key);
        if (!$this->fileExpired($file)) {
            $content = file_get_contents($file);

            /**
             * Perhaps it's possible to exploit the unserialize via: file_get_contents(...).
             * Documentation can be found here:
             * https://github.com/kalessil/phpinspectionsea/blob/master/docs/security.md#exploiting-unserialize
             */
            return unserialize($content);
        }

        return null;
    }

    /**
     * Put content into the cache.
     *
     * @param string $key
     * @param mixed  $content the the content you want to store
     * @param int    $expire  time to expire
     *
     * @return bool whether if the operation was successful or not
     */
    public function set($key, $content, $expire)
    {
        if ($expire < self::$config['expires']) {
            self::$config['expires'] = $expire;
        }

        $destFileName = $this->getRoute($key);

        /** Use a unique temporary filename to make writes atomic with rewrite */
        $tempFileName = str_replace('.php', uniqid('-', true) . '.php', $destFileName);
        $ret = @file_put_contents($tempFileName, serialize($content));
        if ($ret !== false) {
            return @rename($tempFileName, $destFileName);
        }

        @unlink($tempFileName);
        return false;
    }

    /**
     * Get a route to the file associated to that key.
     *
     * @param string $key
     *
     * @return string the filename of the php file
     */
    private function getRoute($key)
    {
        return self::$config['cache_path'] . '/' . md5($key) . '.php';
    }

    /**
     * Check if a file has expired or not.
     *
     * @param string $file the rout to the file
     *
     * @return bool if the file has expired or not
     */
    private function fileExpired($file)
    {
        if (file_exists($file)) {
            return time() > (filemtime($file) + self::$config['expires']);
        }

        return true;
    }

    /**
     * 
     * @return ToolBox
     */
    private function toolBox()
    {
        return new ToolBox();
    }
}
