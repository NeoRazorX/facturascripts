<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Base;

use FacturaScripts\Core\Base\Cache;
use PHPUnit\Framework\TestCase;

/**
 * Description of CacheTest
 *
 * @author Carlos Carlos Garcia Gomez <carlos@facturascripts.com>
 * @covers \FacturaScripts\Core\Base\Cache
 */
final class CacheTest extends TestCase
{
    const KEY = 'test-1234';
    const VALUE = 'Test-test-Test';

    public function testCanSetAndGet()
    {
        $cache = new Cache();
        $cache->set(self::KEY, self::VALUE);
        $this->assertEquals(self::VALUE, $cache->get(self::KEY), 'value-not-found');
    }

    public function testClear()
    {
        $cache = new Cache();
        $cache->set(self::KEY, self::VALUE);
        $cache->clear();
        $this->assertNull($cache->get(self::KEY), 'value-still-found');
    }

    public function testSetAndDelete()
    {
        $cache = new Cache();
        $cache->set(self::KEY, self::VALUE);
        $cache->delete(self::KEY);
        $this->assertNull($cache->get(self::KEY), 'value-still-found');
    }

    public function testMassiveSetGetDelete()
    {
        $cache = new Cache();
        for ($num = 1; $num <= 100; $num++) {
            $cache->set('test-' . $num, $num);
        }
        for ($num = 1; $num <= 100; $num++) {
            $this->assertEquals($num, $cache->get('test-' . $num), 'value-' . $num . '-not-found');
        }
        for ($num = 1; $num <= 100; $num++) {
            $cache->delete('test-' . $num);
        }
        for ($num = 1; $num <= 100; $num++) {
            $this->assertNull($cache->get('test-' . $num), 'value-' . $num . '-still-found');
        }
    }

    public function testMassiveSetGetClear()
    {
        $cache = new Cache();
        for ($num = 1; $num <= 200; $num++) {
            $cache->set('test-' . $num, $num);
        }
        for ($num = 1; $num <= 200; $num++) {
            $this->assertEquals($num, $cache->get('test-' . $num), 'value-' . $num . '-not-found');
        }
        $cache->clear();
        for ($num = 1; $num <= 200; $num++) {
            $this->assertNull($cache->get('test-' . $num), 'value-' . $num . '-still-found');
        }
    }
}
