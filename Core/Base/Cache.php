<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Base;

use FacturaScripts\Core\Base\Cache\APCAdapter;
use FacturaScripts\Core\Base\Cache\FileCache;
use FacturaScripts\Core\Base\Cache\MemcacheAdapter;

/**
 * Cache management class.
 */
final class Cache
{

    /**
     * The engine used for cache
     *
     * @var FileCache|APCAdapter|MemcacheAdapter
     */
    private static $engine;

    /**
     * Default constructor
     */
    public function __construct()
    {
        if (self::$engine === null) {
            if (extension_loaded('apc') && ini_get('apc.enabled') && ini_get('apc.enable_cli')) {
                self::$engine = new APCAdapter();
            } elseif (\FS_CACHE_HOST !== '' && \class_exists('Memcache')) {
                $this->loadMemcache();
            }

            if (self::$engine === null) {
                self::$engine = new FileCache();
            }
        }
    }

    /**
     * Cleans all the cache contents
     *
     * @return bool
     */
    public function clear()
    {
        return self::$engine->clear();
    }

    /**
     * Deletes the contents from the cache associated to $key
     *
     * @param string $key
     *
     * @return bool
     */
    public function delete($key)
    {
        return self::$engine->delete($key);
    }

    /**
     * Returns the contents in the cache associated to $key
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        return self::$engine->get($key);
    }

    /**
     * Saves contents in the cache and associates them to $key
     * 
     * @param string $key
     * @param mixed  $content
     * @param int    $expire
     * 
     * @return bool
     */
    public function set($key, $content, $expire = 3600)
    {
        return self::$engine->set($key, $content, $expire);
    }

    /**
     * Loads memcache engine
     */
    private function loadMemcache()
    {
        self::$engine = new MemcacheAdapter();
        if (!self::$engine->isConnected()) {
            self::$engine = null;
        }
    }
}
