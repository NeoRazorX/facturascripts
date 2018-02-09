<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Base\Cache;

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Base\Translator;

/**
 * Simple file cache
 * This class is great for those who can't use apc or memcached in their projects.
 *
 * @author Emilio Cobos (emiliocobos.net) <ecoal95@gmail.com> and github contributors
 * @author Carlos García Gómez <carlos@facturascripts.com>
 *
 * @version 1.0.1
 *
 * @link http://emiliocobos.net/php-cache/
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
     * Translator object
     *
     * @var Translator
     */
    private $i18n;

    /**
     * MiniLog object
     *
     * @var MiniLog
     */
    private $minilog;

    /**
     * FileCache constructor.
     */
    public function __construct()
    {
        self::$config = [
            'cache_path' => FS_FOLDER . '/MyFiles/Cache/FileCache',
            'expires' => 180,
        ];

        $this->i18n = new Translator();
        $this->minilog = new MiniLog();

        $dir = self::$config['cache_path'];
        if (!file_exists($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            $this->minilog->critical($this->i18n->trans('cant-create-folder', ['%folderName%' => $dir]));
        }
        $this->minilog->debug('using-filecache');
        $this->minilog->debug('cache-dir', ['%folderName%' => $dir]);
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
     * Get the data associated with a key.
     *
     * @param string   $key
     * @param bool     $raw
     * @param int|null $custom_time
     *
     * @return mixed the content you put in, or null if expired or not found
     */
    public function get($key, $raw = false, $custom_time = null)
    {
        $this->minilog->debug($this->i18n->trans('filecache-get-key-item', ['%item%' => $key]));
        if (!$this->fileExpired($file = $this->getRoute($key), $custom_time)) {
            $content = file_get_contents($file);
            /**
             * Perhaps it's possible to exploit the unserialize via: file_get_contents(...).
             * Documentation can be found here:
             * https://github.com/kalessil/phpinspectionsea/blob/master/docs/security.md#exploiting-unserialize
             */
            return $raw ? $content : unserialize($content);
        }

        return null;
    }

    /**
     * Put content into the cache.
     *
     * @param string $key
     * @param mixed  $content the the content you want to store
     * @param bool   $raw     whether if you want to store raw data or not. If it is true, $content *must* be a string
     *
     * @return bool whether if the operation was successful or not
     */
    public function set($key, $content, $raw = false)
    {
        $this->minilog->debug($this->i18n->trans('filecache-set-key-item', ['%item%' => $key]));
        $dest_file_name = $this->getRoute($key);
        /** Use a unique temporary filename to make writes atomic with rewrite */
        $temp_file_name = str_replace('.php', uniqid('-', true) . '.php', $dest_file_name);
        $ret = @file_put_contents($temp_file_name, $raw ? $content : serialize($content));
        if ($ret !== false) {
            return @rename($temp_file_name, $dest_file_name);
        }
        @unlink($temp_file_name);

        return false;
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
        $this->minilog->debug($this->i18n->trans('filecache-delete-key-item', ['%item%' => $key]));
        $ruta = $this->getRoute($key);
        if (file_exists($ruta)) {
            return unlink($ruta);
        }

        return true;
    }

    /**
     * Flush all cache.
     *
     * @return bool always true
     */
    public function clear()
    {
        $this->minilog->debug($this->i18n->trans('filecache-clear'));
        foreach (scandir(self::$config['cache_path'], SCANDIR_SORT_ASCENDING) as $fileName) {
            if (substr($fileName, -4) === '.php') {
                unlink(self::$config['cache_path'] . '/' . $fileName);
            }
        }

        return true;
    }

    /**
     * Check if a file has expired or not.
     *
     * @param string $file the rout to the file
     * @param int    $time the number of minutes it was set to expire
     *
     * @return bool if the file has expired or not
     */
    private function fileExpired($file, $time = null)
    {
        if (file_exists($file)) {
            return time() > (filemtime($file) + 60 * ($time ?: self::$config['expires']));
        }

        return true;
    }
}
