<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use PHPUnit\Framework\TestCase;

/**
 * Description of IPFilterTest
 *
 * @author Carlos Carlos Garcia Gomez <carlos@facturascripts.com>
 * @covers \FacturaScripts\Core\Lib\IPFilter
 */
class IPFilterTest extends TestCase
{

    /**
     * @var IPFilter
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new IPFilter();
        $this->object->clear();
    }

    /**
     * @covers \FacturaScripts\Core\Base\IPFilter::setAttempt
     */
    public function testSetAttempt()
    {
        $this->object->clear();
        $this->object->setAttempt('192.168.1.1');

        /// leemos directamente del archivo para ver si hay algo
        $data = file_get_contents(\FS_FOLDER . '/MyFiles/Cache/ip.list');
        $this->assertNotEmpty($data);
    }

    /**
     * @covers \FacturaScripts\Core\Base\IPFilter::isBanned
     */
    public function testIsBanned()
    {
        /// forzamos el baneo de la IP
        $this->object->setAttempt('192.168.1.1');
        $this->object->setAttempt('192.168.1.1');
        $this->object->setAttempt('192.168.1.1');
        $this->object->setAttempt('192.168.1.1');
        $this->object->setAttempt('192.168.1.1');
        $this->object->setAttempt('192.168.1.1');

        $this->assertTrue($this->object->isBanned('192.168.1.1'));
    }
}
