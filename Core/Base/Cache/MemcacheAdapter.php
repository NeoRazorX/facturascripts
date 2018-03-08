<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez     <carlos@facturascripts.com>
 * Copyright (C) 2017       Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
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
 * Class to connect and interact with memcache.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class MemcacheAdapter implements AdaptorInterface
{
    /**
     * Memcache object
     *
     * @var \Memcache
     */
    private static $memcache;

    /**
     * True if connected, or False when not
     *
     * @var bool
     */
    private static $connected;

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
     * MemcacheAdaptor constructor.
     * If Memcache can't be used, default option is FileCache
     */
    public function __construct()
    {
        $this->minilog = new MiniLog();
        $this->i18n = new Translator();

        self::$connected = false;
        if (self::$memcache === null) {
            self::$memcache = new \Memcache();
            if (@self::$memcache->connect(FS_CACHE_HOST, (int) FS_CACHE_PORT)) {
                self::$connected = true;
                $this->minilog->debug($this->i18n->trans('using-memcache'));
            } else {
                $this->minilog->error($this->i18n->trans('error-connecting-memcache'));
            }
        }
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
            $this->minilog->debug($this->i18n->trans('memcache-get-key-item', ['%item%' => $key]));

            return self::$memcache->get(FS_CACHE_PREFIX . $key);
        }

        return false;
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
        $this->minilog->debug($this->i18n->trans('memcache-set-key-item', ['%item%' => $key]));
        if (self::$connected) {
            return self::$memcache->set(FS_CACHE_PREFIX . $key, $content, 0, $expire);
        }

        return false;
    }

    /**
     * Delete data from cache.
     * If $key is string, only delete one key.
     * If $key is array, delete all key strings on array.
     *
     * @param string $key
     *
     * @return bool true if the data was removed successfully
     */
    public function delete($key)
    {
        $this->minilog->debug($this->i18n->trans('memcache-delete-key-item', ['%item%' => $key]));

        if (self::$connected) {
            return self::$memcache->delete(FS_CACHE_PREFIX . $key);
        }

        return false;
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

        return false;
    }
}
