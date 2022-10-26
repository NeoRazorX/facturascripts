<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Cache as NewCache;

/**
 * Cache management class.
 * @deprecated since version 2022.5
 */
final class Cache
{
    public function clear(): bool
    {
        NewCache::clear();
        return true;
    }

    /**
     * Deletes the contents from the cache associated to $key
     *
     * @param string $key
     *
     * @return bool
     */
    public function delete($key): bool
    {
        NewCache::delete($key);
        return true;
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
        return NewCache::get($key);
    }

    /**
     * Saves contents in the cache and associates them to $key
     *
     * @param string $key
     * @param mixed $content
     * @param int $expire
     *
     * @return bool
     */
    public function set($key, $content, $expire = 3600): bool
    {
        NewCache::set($key, $content);
        return true;
    }
}
