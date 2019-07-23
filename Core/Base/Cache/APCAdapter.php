<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017       Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 * Copyright (C) 2017-2019  Carlos Garcia Gomez      <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Base\Translator;

/**
 * Class to connect and interact with APC.
 *
 * @author Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 */
class APCAdapter implements AdaptorInterface
{

    /**
     * Translator object
     *
     * @var Translator
     */
    private $i18n;

    /**
     * MiniLog Object
     *
     * @var MiniLog
     */
    private $minilog;

    /**
     * APCAdaptor constructor.
     */
    public function __construct()
    {
        $this->i18n = new Translator();
        $this->minilog = new MiniLog();
        $this->minilog->debug($this->i18n->trans('using-apc'));
    }

    /**
     * Flush all cache.
     *
     * @return bool always true
     */
    public function clear()
    {
        $this->minilog->debug($this->i18n->trans('apc-clear'));
        /**
         * If cache_type is "user", the user cache will be cleared;
         * otherwise, the system cache (cached files) will be cleared.
         * On shared hostings, users only have perms to his own apache user.
         *
         * @source: http://php.net/manual/function.apc-clear-cache.php
         */
        return apc_clear_cache('user');
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
        $this->minilog->debug($this->i18n->trans('apc-delete-key-item', ['%item%' => $key]));
        return apc_delete(\FS_CACHE_PREFIX . $key) || !apc_exists([\FS_CACHE_PREFIX . $key]);
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
        $this->minilog->debug($this->i18n->trans('apc-get-key-item', ['%item%' => $key]));
        if (apc_exists([\FS_CACHE_PREFIX . $key])) {
            $result = apc_fetch(\FS_CACHE_PREFIX . $key);
            return $result === false ? null : $result;
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
        $this->minilog->debug($this->i18n->trans('apc-set-key-item', ['%item%' => $key]));
        return (bool) apc_store(\FS_CACHE_PREFIX . $key, $content, $expire);
    }
}
