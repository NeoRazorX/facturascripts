<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

    public function testHasReturnsTrueWhenKeyExists(): void
    {
        Cache::clear();

        $key = 'test-has-key';
        $value = 'test-value';
        Cache::set($key, $value);

        $this->assertTrue(Cache::has($key), 'has-should-return-true-for-existing-key');
    }

    public function testHasReturnsFalseWhenKeyDoesNotExist(): void
    {
        Cache::clear();

        $key = 'non-existent-key';

        $this->assertFalse(Cache::has($key), 'has-should-return-false-for-non-existent-key');
    }

    public function testHasReturnsFalseAfterDelete(): void
    {
        Cache::clear();

        $key = 'test-delete-has-key';
        $value = 'test-value';
        Cache::set($key, $value);

        $this->assertTrue(Cache::has($key), 'has-should-return-true-before-delete');

        Cache::delete($key);

        $this->assertFalse(Cache::has($key), 'has-should-return-false-after-delete');
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

    public function testDeleteMultiWithContainsWorks(): void
    {
        Cache::clear();

        // claves con el formato de los JoinModel: prefijo + tablas + sufijo
        Cache::set('join-model-stocks-variantes-productos-abc123-count', 100);
        Cache::set('join-model-variantes-productos-impuestos-def456-count', 200);
        Cache::set('join-model-facturascli-clientes-ghi789-count', 300);
        Cache::set('other-prefix-stocks-jkl012-count', 400);

        // borramos solo las entradas del prefijo que contengan la tabla stocks
        Cache::deleteMulti('join-model-', '-stocks-');

        $this->assertNull(Cache::get('join-model-stocks-variantes-productos-abc123-count'), 'stocks-entry-should-be-deleted');
        $this->assertEquals(200, Cache::get('join-model-variantes-productos-impuestos-def456-count'), 'other-tables-entry-should-remain');
        $this->assertEquals(300, Cache::get('join-model-facturascli-clientes-ghi789-count'), 'other-tables-entry-should-remain');
        $this->assertEquals(400, Cache::get('other-prefix-stocks-jkl012-count'), 'entry-with-other-prefix-should-remain');

        // el delimitador evita coincidencias parciales de nombres de tabla
        Cache::deleteMulti('join-model-', '-producto-');
        $this->assertEquals(200, Cache::get('join-model-variantes-productos-impuestos-def456-count'), 'productos-should-not-match-producto');

        // sin subcadena, borra todo el prefijo como antes
        Cache::deleteMulti('join-model-');
        $this->assertNull(Cache::get('join-model-variantes-productos-impuestos-def456-count'), 'prefix-entry-should-be-deleted');
        $this->assertNull(Cache::get('join-model-facturascli-clientes-ghi789-count'), 'prefix-entry-should-be-deleted');
        $this->assertEquals(400, Cache::get('other-prefix-stocks-jkl012-count'), 'entry-with-other-prefix-should-remain');
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

    public function testGetReturnsLegitimateCachedFalse(): void
    {
        Cache::clear();

        $key = 'test-false-key';
        Cache::set($key, false);

        $this->assertFalse(Cache::get($key), 'cached-false-should-be-returned');
        $this->assertTrue(Cache::has($key), 'cached-false-should-exist');

        // remember no debe recalcular cuando hay un false legítimo cacheado
        $cacheValue = Cache::remember($key, function () {
            return 'should-not-be-called';
        });
        $this->assertFalse($cacheValue, 'remember-should-return-cached-false');
    }

    public function testGetReturnsDefaultWhenCacheIsCorrupted(): void
    {
        Cache::clear();

        $key = 'test-corrupted-key';
        Cache::set($key, ['a', 'b', 'c']);
        $this->assertEquals(['a', 'b', 'c'], Cache::get($key), 'cache-value-not-found');

        // corrompemos el fichero de caché con datos que unserialize() no puede leer
        $fileName = FS_FOLDER . Cache::FILE_PATH . '/' . $key . '.cache';
        file_put_contents($fileName, 'esto-no-es-serializable');

        // un fichero corrupto debe tratarse como ausencia de valor, no devolver false
        $this->assertNull(Cache::get($key), 'corrupted-cache-should-return-default');

        // remember debe recalcular en lugar de propagar el false espurio
        $cacheValue = Cache::remember($key, function () {
            return ['x', 'y'];
        });
        $this->assertEquals(['x', 'y'], $cacheValue, 'remember-should-recompute-on-corrupted-cache');
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

    public function testWithMemoryDeleteMultiWithContainsWorks(): void
    {
        Cache::clear();
        CacheWithMemory::clear();

        $memoryCache = Cache::withMemory();
        $memoryCache->set('join-model-stocks-variantes-abc123-count', 100);
        $memoryCache->set('join-model-variantes-productos-def456-count', 200);

        CacheWithMemory::deleteMulti('join-model-', '-stocks-');

        $this->assertNull($memoryCache->get('join-model-stocks-variantes-abc123-count'), 'memory-stocks-entry-should-be-deleted');
        $this->assertNull(Cache::get('join-model-stocks-variantes-abc123-count'), 'file-stocks-entry-should-be-deleted');
        $this->assertEquals(200, $memoryCache->get('join-model-variantes-productos-def456-count'), 'memory-other-entry-should-remain');
        $this->assertEquals(200, Cache::get('join-model-variantes-productos-def456-count'), 'file-other-entry-should-remain');
    }
}
