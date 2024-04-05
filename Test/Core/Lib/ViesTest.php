<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

final class ViesTest extends TestCase
{
    public function testCheck(): void
    {
        $data = [
            ['results' => -1, 'number' => '', 'iso' => ''],
            ['results' => -1, 'number' => '123', 'iso' => ''],
            ['results' => 0, 'number' => '123456789', 'iso' => 'ES'],
            ['results' => 0, 'number' => 'ES74003828J', 'iso' => 'ES'],
            ['results' => 1, 'number' => 'ES74003828V', 'iso' => 'ES'],
            ['results' => 1, 'number' => '74003828V', 'iso' => 'ES'],
            ['results' => 1, 'number' => '43834596223', 'iso' => 'FR'],
            ['results' => 0, 'number' => '81328757100011', 'iso' => 'FR'],
            ['results' => 1, 'number' => '514356480', 'iso' => 'PT'],
            ['results' => 1, 'number' => '513969144', 'iso' => 'PT'],
            ['results' => 0, 'number' => '513967144', 'iso' => 'PT'],
        ];

        foreach ($data as $item) {
            $check = Vies::check($item['number'], $item['iso']);

            if ($check == -1 && $item['results'] != -1) {
                $this->markTestSkipped('Vies service returns error: ' . Vies::getLastError());
            }

            $this->assertEquals($item['results'], $check);

            // esperamos medio segundo para no saturar el servicio
            usleep(500000);
        }
    }
}
