<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
 * Copyright (C) 2017  Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
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
 * Clase para concectar e interactuar con memcache.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class MemcacheAdapter implements AdaptorInterface
{
    /**
     * Objeto Memcache
     *
     * @var \Memcache
     */
    private static $memcache;

    /**
     * Objeto FileCache
     *
     * @var FileCache
     */
    private static $PhpFileCache;

    /**
     * Si contiene errores True, sino False
     *
     * @var bool
     */
    private static $error;

    /**
     * Si está conectado True, sino False
     *
     * @var bool
     */
    private static $connected;

    /**
     * Objeto traductor
     *
     * @var Translator
     */
    private $i18n;

    /**
     * Objeto del minilog
     *
     * @var MiniLog
     */
    private $minilog;

    /**
     * MemcacheAdaptor constructor.
     * If Memcache can't be used, default option is FileCache
     *
     * @param string $folder
     */
    public function __construct($folder = '')
    {
        $this->minilog = new MiniLog();
        $this->i18n = new Translator($folder);

        self::$connected = false;
        self::$error = false;

        if (self::$memcache === null) {
            if (\class_exists('Memcache')) {
                self::$memcache = new \Memcache();
                if (@self::$memcache->connect(FS_CACHE_HOST, FS_CACHE_PORT)) {
                    self::$connected = true;
                    $this->minilog->debug($this->i18n->trans('using-memcache'));
                } else {
                    self::$error = true;
                    $this->minilog->error($this->i18n->trans('error-connecting-memcache'));
                }
            } else {
                self::$memcache = null;
                self::$error = true;
                $this->minilog->error($this->i18n->trans('memcache-not-found'));
            }
        }

        if (!self::$connected) {
            self::$PhpFileCache = new FileCache($folder);
        }
    }

    /**
     * Return True if got error, false otherwise.
     *
     * @return bool
     */
    public function hasError()
    {
        return self::$error;
    }

    /**
     * Cierra la conexión con Memcache
     */
    public function close()
    {
        if (self::$memcache !== null && self::$connected) {
            self::$memcache->close();
        }
    }

    /**
     * Return the Memcache version
     *
     * @return string
     */
    public function getVersion()
    {
        if (self::$connected) {
            return 'Memcache ' . self::$memcache->getVersion();
        }
        return 'Files';
    }

    /**
     * Return if is connected or not.
     *
     * @return bool
     */
    public function isConnected()
    {
        return self::$connected;
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
        if (self::$connected) {
            $this->minilog->debug($this->i18n->trans('memcache-get-key-item', [$key]));
            return self::$memcache->get(FS_CACHE_PREFIX . $key);
        }
        return self::$PhpFileCache->get($key);
    }

    /**
     * Get the array data associated with a key.
     *
     * @param array $key
     * @return array
     */
    public function getArray($key)
    {
        $stringKey = \implode(',', $key);
        $this->minilog->debug($this->i18n->trans('memcache-getarray-key-item', [$stringKey]));
        $result = [];
        if (self::$connected) {
            $data = self::$memcache->get(FS_CACHE_PREFIX . $key);
            if ($data) {
                $result = $data;
            }
        } else {
            $data = self::$PhpFileCache->get($stringKey);
            if ($data) {
                $result = $data;
            }
        }
        return $result;
    }

    /**
     * Get the array data associated with a key.
     * If data not found in cache, $error = true.
     *
     * @param array $key
     * @param bool $error
     *
     * @return array
     */
    public function getArray2($key, &$error)
    {
        $stringKey = \implode(',', $key);
        $this->minilog->debug($this->i18n->trans('memcache-getarray2-key-item', [$stringKey]));
        $result = [];
        $error = true;

        if (self::$connected) {
            $this->minilog->debug($this->i18n->trans('memcache-get-key-item', [$key]));
            $data = self::$memcache->get(FS_CACHE_PREFIX . $key);
            if ($data) {
                $result = $data;
                $error = false;
                $this->minilog->info($this->i18n->trans('element-not-in-memcache', [$key]));
            }
        } else {
            $data = self::$PhpFileCache->get($stringKey);
            if ($data) {
                $result = $data;
                $error = false;
                $this->minilog->info($this->i18n->trans('element-not-in-memcache', [$key]));
            }
        }
        return $result;
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
    public function set($key, $content, $expire = 5400)
    {
        if (\is_array($content)) {
            $contentMsg = \implode(', ', $content);
        } elseif (\is_string($content)) {
            $contentMsg = $content;
        } else {
            $contentMsg = \gettype($content);
        }
        $this->minilog->debug($this->i18n->trans('memcache-set-key-item', [$key, $contentMsg]));
        if (self::$connected) {
            return self::$memcache->set(FS_CACHE_PREFIX . $key, $content, false, $expire);
        }

        return self::$PhpFileCache->set($key, $content);
    }

    /**
     * Delete data from cache.
     * If $key is string, only delete one key.
     * If $key is array, delete all key strings on array.
     *
     * @param array|string $key
     *
     * @return bool true if the data was removed successfully
     */
    public function delete($key)
    {
        $this->minilog->debug($this->i18n->trans('memcache-delete-key-item', [$key]));
        $done = false;

        if (self::$connected) {
            if (\is_array($key)) {
                foreach ($key as $i => $value) {
                    $done = self::$memcache->delete(FS_CACHE_PREFIX . $value);
                }
            } else {
                $done = self::$memcache->delete(FS_CACHE_PREFIX . $key);
            }
        }

        if (\is_array($key)) {
            foreach ($key as $i => $value) {
                $done = self::$PhpFileCache->delete($value);
            }
        } else {
            $done = self::$PhpFileCache->delete($key);
        }
        return $done;
    }

    /**
     * Flush all cache.
     *
     * @return bool always true
     */
    public function clear()
    {
        $this->minilog->debug($this->i18n->trans('memcache-clear'));
        if (self::$connected) {
            return self::$memcache->flush();
        }
        return self::$PhpFileCache->clear();
    }
}
