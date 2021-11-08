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

namespace FacturaScripts\Test\Core\Lib;

use FacturaScripts\Core\Lib\IPFilter;
use FacturaScripts\Test\Core\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

/**
 * Description of IPFilterTest
 *
 * @author Carlos Carlos Garcia Gomez <carlos@facturascripts.com>
 * @covers \FacturaScripts\Core\Lib\IPFilter
 */
final class IPFilterTest extends TestCase
{

    use LogErrorsTrait;

    const CLEAN_IP = '192.168.0.2';
    const TARGET_IP = '192.168.0.11';

    public function testBanIP()
    {
        $ipFilter = new IPFilter();
        $this->assertFalse($ipFilter->isBanned(self::TARGET_IP), 'target-ip-banned');
        $this->assertFalse($ipFilter->isBanned(self::CLEAN_IP), 'clean-ip-banned');

        for ($attempt = 0; $attempt < IPFilter::MAX_ATTEMPTS; $attempt++) {
            $ipFilter->setAttempt(self::TARGET_IP);
            $this->assertFalse($ipFilter->isBanned(self::TARGET_IP), 'target-ip-banned-' . $attempt);
        }

        $this->assertTrue($ipFilter->isBanned(self::TARGET_IP), 'target-ip-not-banned');
        $this->assertFalse($ipFilter->isBanned(self::CLEAN_IP), 'clean-ip-banned');

        $ipFilter->clear();
        $this->assertFalse($ipFilter->isBanned(self::TARGET_IP), 'target-ip-banned');
    }

    protected function tearDown()
    {
        $this->logErrors();
    }
}
