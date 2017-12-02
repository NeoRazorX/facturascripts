<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017    Carlos Garcia Gomez  <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\App;

use FacturaScripts\Core\App\AppCron;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2017-07-19 at 11:58:07.
 */
class AppCronTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var AppCron
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new AppCron(FS_FOLDER);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        
    }

    public function testConnect()
    {
        $this->assertTrue($this->object->connect());
    }

    /**
     * @covers \FacturaScripts\Core\App\AppCron::run
     */
    public function testRun()
    {
        $this->assertTrue($this->object->run());
    }
}
