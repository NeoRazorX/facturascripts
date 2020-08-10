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
namespace FacturaScripts\Test\Core\App;

use FacturaScripts\Core\App\AppCron;
use PHPUnit\Framework\TestCase;

/**
 * Description of AppCronTest
 *
 * @author Carlos Carlos Garcia Gomez <carlos@facturascripts.com>
 * @covers \FacturaScripts\Core\App\AppCron
 */
class AppCronTest extends TestCase
{

    /**
     * @var AppCron
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new AppCron();
    }

    /**
     * @covers \FacturaScripts\Core\App\AppCron::connect
     */
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
