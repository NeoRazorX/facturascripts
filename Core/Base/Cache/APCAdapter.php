<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 * Copyright (C) 2017  Carlos Garcia Gomez      <carlos@facturascripts.com>
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
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class APCAdapter implements AdaptorInterface
{

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
     */
    public function __construct()
    {
        $this->minilog->debug($this->i18n->trans('using-apc'));
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
        return apc_fetch($key);
    }

    /**
     * Put content into the cache.
     *
     * @param string $key
     * @param mixed  $content   the the content you want to store
     * @param int    $expire    time to expire
     *
     * @return bool whether if the operation was successful or not
     */
    public function set($key, $content, $expire = 5400)
    {
        $this->minilog->debug($this->i18n->trans('apc-set-key-item', [$key]));
        return apc_store($key, $content, $expire);
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
        return apc_delete($key) || !apc_exists($key);
    }

    /**
     * Flush all cache.
     *
     * @return bool always true
     */
    public function clear()
    {
        $this->minilog->debug($this->i18n->trans('apc-clear'));
        return apc_clear_cache();
    }
}
