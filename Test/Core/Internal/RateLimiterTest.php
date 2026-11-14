<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Internal;

use FacturaScripts\Core\Internal\RateLimiter;
use PHPUnit\Framework\TestCase;

final class RateLimiterTest extends TestCase
{
    private const KEY = 'test-rate-limiter';
    private const OTHER_KEY = 'test-rate-limiter-other';

    public function testBlocksWhenLimitIsReached(): void
    {
        // ventana amplia para que el test no dependa de la velocidad de la máquina
        $this->assertTrue(RateLimiter::attempt(self::KEY, 3, 60));
        $this->assertTrue(RateLimiter::attempt(self::KEY, 3, 60));
        $this->assertTrue(RateLimiter::attempt(self::KEY, 3, 60));
        $this->assertFalse(RateLimiter::attempt(self::KEY, 3, 60), 'fourth-request-must-be-blocked');
        $this->assertFalse(RateLimiter::attempt(self::KEY, 3, 60), 'fifth-request-must-be-blocked');
    }

    public function testClearResetsTheCounter(): void
    {
        $this->assertTrue(RateLimiter::attempt(self::KEY, 1, 60));
        $this->assertFalse(RateLimiter::attempt(self::KEY, 1, 60));

        RateLimiter::clear(self::KEY);
        $this->assertTrue(RateLimiter::attempt(self::KEY, 1, 60));
    }

    public function testKeysAreIndependent(): void
    {
        $this->assertTrue(RateLimiter::attempt(self::KEY, 1, 60));
        $this->assertFalse(RateLimiter::attempt(self::KEY, 1, 60));
        $this->assertTrue(RateLimiter::attempt(self::OTHER_KEY, 1, 60), 'other-key-must-not-be-affected');
    }

    public function testLimitBelowOneDisablesTheCheck(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->assertTrue(RateLimiter::attempt(self::KEY, 0, 60));
            $this->assertTrue(RateLimiter::attempt(self::KEY, -1, 60));
        }
    }

    public function testRequestsAreAllowedAgainWhenTheWindowExpires(): void
    {
        $this->assertTrue(RateLimiter::attempt(self::KEY, 1, 0.2));
        $this->assertFalse(RateLimiter::attempt(self::KEY, 1, 0.2));

        usleep(250000);
        $this->assertTrue(RateLimiter::attempt(self::KEY, 1, 0.2), 'request-must-be-allowed-after-window');
    }

    public function testRetryAfterReturnsTheRemainingSeconds(): void
    {
        $this->assertSame(0, RateLimiter::retryAfter(self::KEY, 60), 'no-requests-means-no-wait');

        $this->assertTrue(RateLimiter::attempt(self::KEY, 1, 60));
        $this->assertFalse(RateLimiter::attempt(self::KEY, 1, 60));

        $retryAfter = RateLimiter::retryAfter(self::KEY, 60);
        $this->assertGreaterThanOrEqual(59, $retryAfter);
        $this->assertLessThanOrEqual(60, $retryAfter);
    }

    protected function setUp(): void
    {
        RateLimiter::clear(self::KEY);
        RateLimiter::clear(self::OTHER_KEY);
    }

    protected function tearDown(): void
    {
        RateLimiter::clear(self::KEY);
        RateLimiter::clear(self::OTHER_KEY);
    }
}
