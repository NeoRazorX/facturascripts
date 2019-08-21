<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2019  Carlos Garcia Gomez     <carlos@facturascripts.com>
 * Copyright (C) 2017       Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
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
use Memcache;

/**
 * Class to connect and interact with memcache.
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 */
class MemcacheAdapter implements AdaptorInterface
{

    /**
     * True if connected, or False when not
     *
     * @var bool
     */
    private static $connected;

    /**
     * Memcache object
     *
     * @var Memcache
     */
    private static $memcache;

    /**
     * MemcacheAdaptor constructor.
     * If Memcache can't be used, default option is FileCache
     */
    public function __construct()
    {
        if (!isset(self::$memcache)) {
            self::$connected = false;

            self::$memcache = new Memcache();
            if (self::$memcache->connect(\FS_CACHE_HOST, (int) \FS_CACHE_PORT)) {
                self::$connected = true;
            } else {
                $this->toolBox()->i18nLog()->error('error-connecting-memcache');
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
        if (self::$connected) {
            return self::$memcache->flush();
        }

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
        if (self::$connected) {
            return self::$memcache->delete(\FS_CACHE_PREFIX . $key);
        }

        return false;
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
            /**
             * Memcache::get() returns false if key is not found.
             * To distinguish this case from when it is stored false, whe must use $falgs.
             */
            $flags = false;
            $data = self::$memcache->get(\FS_CACHE_PREFIX . $key, $flags);
            if (false === $data && false === $flags) {
                return null;
            }

            return $data;
        }

        return null;
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
        if (self::$connected) {
            return self::$memcache->set(\FS_CACHE_PREFIX . $key, $content, 0, $expire);
        }

        return false;
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
