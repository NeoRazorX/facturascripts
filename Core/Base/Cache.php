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

namespace FacturaScripts\Core\Base;

use FacturaScripts\Core\Base\Cache\MemcacheAdapter;

/**
 * Class Cache
 *
 * @package FacturaScripts\Core\Base
 */
class Cache
{
    /**
     * El motor utilizado para la cache.
     *
     * @var MemcacheAdapter
     */
    private static $engine;

    /**
     * Constructor por defecto.
     *
     * @param string $folder carpeta de trabajo
     */
    public function __construct($folder = '')
    {
        if (self::$engine === null) {
            self::$engine = new MemcacheAdapter($folder);
        }
    }

    /**
     * Devuelve el contenido asociado a esa $key que hay en la cache.
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
     * Guarda en la cache el contenido y lo asocia a $key
     *
     * @param string $key
     * @param mixed  $content
     *
     * @return bool
     */
    public function set($key, $content)
    {
        return self::$engine->set($key, $content);
    }

    /**
     * Elimina de la cache el contenido asociado a la $key
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
     * Limpia el contenido de la cache al completo.
     *
     * @return bool
     */
    public function clear()
    {
        return self::$engine->clear();
    }
}
