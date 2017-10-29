<?php
/**
 * This file is part of FacturaScripts
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
 * Clase para conectar e interactuar con APC.
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class APCAdapter implements AdaptorInterface
{
    /**
     * APC, contiene True, si está en uso, sino False
     *
     * @var bool
     */
    private static $apc;

    /**
     * Objeto FileCache
     *
     * @var FileCache
     */
    private static $PhpFileCache;

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
     * APCAdaptor constructor.
     * If APC can't be used, default option is FileCache
     *
     * @param string $folder
     */
    public function __construct($folder = '')
    {
        if (extension_loaded('apc') && ini_get('apc.enabled')) {
            self::$apc = true;
            $this->minilog->debug($this->i18n->trans('using-apc'));
        } else {
            self::$apc = false;
            $this->minilog->error($this->i18n->trans('apc-not-found'));
            self::$PhpFileCache = new FileCache($folder);
        }
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
        $this->minilog->debug($this->i18n->trans('apc-get-key-item', [$key]));

        if (self::$apc) {
            return apc_fetch($key);
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
        $this->minilog->debug($this->i18n->trans('apc-getarray-key-item', [$stringKey]));

        if (self::$apc) {
            return apc_fetch($key) ?: [];
        }

        $result = [];
        $stringKey = \implode(',', $key);
        $data = self::$PhpFileCache->get($stringKey);
        if ($data) {
            $result = $data;
        }
        return $result;
    }

    /**
     * Put content into the cache.
     *
     * @param string|array $key if string, value on content
     *                          if array, key => value
     * @param mixed  $content   the the content you want to store
     * @param int    $expire    time to expire
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
        $this->minilog->debug($this->i18n->trans('apc-set-key-item', [$key, $contentMsg]));

        if (self::$apc) {
            if (\is_array($key)) {
                $result = apc_store($key, null, $expire);
                return empty($result);
            }
            return apc_store($key, $content, $expire);
        }

        return self::$PhpFileCache->set($key, $content);
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
        $this->minilog->debug($this->i18n->trans('apc-delete-key-item', [$key]));
        if (self::$apc) {
            return apc_delete($key) || ! apc_exists($key);
        }

        return self::$PhpFileCache->delete($key);
    }

    /**
     * Flush all cache.
     *
     * @return bool always true
     */
    public function clear()
    {
        $this->minilog->debug($this->i18n->trans('apc-clear'));
        if (self::$apc) {
            return apc_clear_cache() && apc_clear_cache('user');
        }

        return self::$PhpFileCache->clear();
    }
}
