<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\Vies;
use PHPUnit\Framework\TestCase;

class ViesTest extends TestCase
{
    public function testCheck(): void
    {
        $this->assertEquals(-1, Vies::check('', ''));
        $this->assertEquals(-1, Vies::check('123', ''));
        $this->assertEquals(0, Vies::check('123456789', 'ES'));

        // esperamos medio segundo para no saturar el servicio
        usleep(500000);

        $this->assertEquals(0, Vies::check('ES74003828J', 'ES'));
        usleep(500000);

        $this->assertEquals(1, Vies::check('ES74003828V', 'ES'));
        usleep(500000);

        $this->assertEquals(1, Vies::check('74003828V', 'ES'));
        usleep(500000);

        $this->assertEquals(1, Vies::check('43834596223', 'FR'));
        usleep(500000);

        $this->assertEquals(0, Vies::check('81328757100011', 'FR'));
        usleep(500000);

        $this->assertEquals(1, Vies::check('514356480', 'PT'));
        usleep(500000);

        $this->assertEquals(1, Vies::check('513969144', 'PT'));
        usleep(500000);

        $this->assertEquals(0, Vies::check('513967144', 'PT'));
    }
}
