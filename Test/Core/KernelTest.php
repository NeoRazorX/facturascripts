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

namespace FacturaScripts\Test\Core;

use FacturaScripts\Core\Kernel;
use PHPUnit\Framework\TestCase;

final class KernelTest extends TestCase
{
    public function testVersion(): void
    {
        $this->assertIsFloat(Kernel::version());
        $this->assertGreaterThan(2023.0, Kernel::version());
    }

    public function testTimers(): void
    {
        // iniciamos un temporizador
        $name = 'test-timer';
        Kernel::startTimer($name);

        // comprobamos que el temporizador existe
        $timers = Kernel::getTimers();
        $this->assertArrayHasKey($name, $timers);

        // comprobamos que el temporizador tiene un tiempo mayor que 0
        $this->assertGreaterThan(0, $timers[$name]['start']);

        // esperamos 1 segundo
        sleep(1);

        // paramos el temporizador
        $total = Kernel::stopTimer($name);

        // comprobamos que el temporizador tiene un tiempo mayor o igual a 1 segundo
        $this->assertGreaterThanOrEqual(1, $total);

        // ahora obtenemos el temporizador
        $timer = Kernel::getTimer($name);

        // comprobamos que el temporizador tiene un tiempo mayor o igual a 1 segundo
        $this->assertGreaterThanOrEqual(1, $timer);
    }
}
