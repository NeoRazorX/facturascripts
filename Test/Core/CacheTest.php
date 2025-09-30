<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Internal\CacheWithMemory;
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

    public function testWithMemoryReturnsInstance(): void
    {
        $instance = Cache::withMemory();
        $this->assertInstanceOf(CacheWithMemory::class, $instance, 'withMemory should return CacheWithMemory instance');
    }

    public function testWithMemoryValueIsStoredAndRemoved(): void
    {
        Cache::clear();

        $key = 'test-memory-key';
        $value = 'memory-value';

        $memoryCache = Cache::withMemory();
        $memoryCache->set($key, $value);

        $this->assertEquals($value, $memoryCache->get($key), 'memory-cache-value-not-found');
        $this->assertEquals($value, Cache::get($key), 'file-cache-should-also-have-value');

        $memoryCache->delete($key);
        $this->assertNull($memoryCache->get($key), 'memory-cache-value-not-erased');
        $this->assertNull(Cache::get($key), 'file-cache-value-not-erased');
    }

    public function testWithMemoryPrioritizesMemoryOverFile(): void
    {
        Cache::clear();

        $key = 'test-priority-key';
        $fileValue = 'file-value';
        $memoryValue = 'memory-value';

        Cache::set($key, $fileValue);

        $memoryCache = Cache::withMemory();
        $memoryCache->set($key, $memoryValue);

        $this->assertEquals($memoryValue, $memoryCache->get($key), 'should-return-memory-value-over-file');
        $this->assertEquals($memoryValue, Cache::get($key), 'file-should-be-updated-too');
    }

    public function testWithMemoryRemember(): void
    {
        Cache::clear();

        $key = 'test-memory-remember';
        $value = 'remember-value';

        $memoryCache = Cache::withMemory();
        $result = $memoryCache->remember($key, function () use ($value) {
            return $value;
        });

        $this->assertEquals($value, $result, 'remember-should-return-callback-value');
        $this->assertEquals($value, $memoryCache->get($key), 'value-should-be-cached-in-memory');

        $result2 = $memoryCache->remember($key, function () {
            return 'should-not-be-called';
        });

        $this->assertEquals($value, $result2, 'remember-should-return-cached-value');
    }

    public function testWithMemoryClear(): void
    {
        $key1 = 'test-clear-memory-1';
        $key2 = 'test-clear-memory-2';

        $memoryCache = Cache::withMemory();
        $memoryCache->set($key1, 'value1');
        $memoryCache->set($key2, 'value2');

        $this->assertEquals('value1', $memoryCache->get($key1));
        $this->assertEquals('value2', $memoryCache->get($key2));

        CacheWithMemory::clear();

        $this->assertNull($memoryCache->get($key1), 'memory-should-be-cleared');
        $this->assertNull($memoryCache->get($key2), 'memory-should-be-cleared');
        $this->assertNull(Cache::get($key1), 'file-should-be-cleared');
        $this->assertNull(Cache::get($key2), 'file-should-be-cleared');
    }

    public function testWithMemoryDeleteMultiWorks(): void
    {
        Cache::clear();

        $prefix = 'test-memory-multi-';
        $memoryCache = Cache::withMemory();

        for ($num = 1; $num <= 5; $num++) {
            $key = $prefix . $num;
            $memoryCache->set($key, $num);
            $this->assertEquals($num, $memoryCache->get($key), 'memory-cache-bad-value-' . $num);
        }

        $memoryCache->deleteMulti($prefix);

        for ($num = 1; $num <= 5; $num++) {
            $key = $prefix . $num;
            $this->assertNull($memoryCache->get($key), 'memory-value-should-be-deleted');
            $this->assertNull(Cache::get($key), 'file-value-should-be-deleted');
        }
    }
}
