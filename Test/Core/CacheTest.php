<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core;

use FacturaScripts\Core\Cache;
use PHPUnit\Framework\TestCase;

/**
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
final class CacheTest extends TestCase
{
    public function testValueIsStoredAndRemoved(): void
    {
        Cache::clear();

        $key = 'test-key';
        $value = '1234';
        Cache::set($key, $value);
        $this->assertEquals($value, Cache::get($key), 'cache-value-not-found');

        Cache::delete($key);
        $this->assertNull(Cache::get($key), 'cache-value-not-erased');
    }

    public function testSlashOnKeys(): void
    {
        Cache::clear();

        $key = 'test/key';
        $value = '1234';
        Cache::set($key, $value);
        $this->assertEquals($value, Cache::get($key), 'cache-value-not-found');

        Cache::delete($key);
        $this->assertNull(Cache::get($key), 'cache-value-not-erased');
    }

    public function testDeleteMultiWorks(): void
    {
        Cache::clear();

        $prefix = 'test-delete-multi-';
        for ($num = 1; $num <= 10; $num++) {
            $key = $prefix . $num;
            Cache::set($key, $num);
            $this->assertEquals($num, Cache::get($key), 'cache-bad-value-' . $num);
        }

        Cache::deleteMulti($prefix);
        for ($num = 1; $num <= 10; $num++) {
            $key = $prefix . $num;
            $this->assertNull(Cache::get($key));
        }
    }

    public function testClearWorks(): void
    {
        Cache::clear();

        $prefix = 'test-clear-';
        for ($num = 1; $num <= 100; $num++) {
            $key = $prefix . $num;
            Cache::set($key, $num);
            $this->assertEquals($num, Cache::get($key), 'cache-bad-value-' . $num);
        }

        Cache::clear();
        for ($num = 1; $num <= 100; $num++) {
            $key = $prefix . $num;
            $this->assertNull(Cache::get($key));
        }
    }

    public function testCacheRememberKeyNotExist(): void
    {
        Cache::clear();

        $key = 'test-key';
        $value = 1;

        $cacheValue = Cache::remember($key, function () use ($value) {
            return $value + 1;
        });

        $this->assertEquals(Cache::get($key), $cacheValue, 'cache-value-not-found');
        $this->assertNotEquals(Cache::get($key), $value, 'cache-value-not-found');
    }

    public function testCacheRememberKeyExist(): void
    {
        Cache::clear();

        $key = 'test-key';
        $value = '1234';
        $closureValue = '5678';

        Cache::set($key, $value);

        $cacheValue = Cache::remember($key, function () use ($closureValue) {
            return $closureValue;
        });

        $this->assertEquals(Cache::get($key), $cacheValue, 'cache-value-not-found');
        $this->assertNotEquals(Cache::get($key), $closureValue, 'cache-value-not-found');
    }
}
