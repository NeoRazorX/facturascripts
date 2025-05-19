<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\FiscalNumberValidator;
use PHPUnit\Framework\TestCase;

class FiscalNumberValidatorTest extends TestCase
{
    public function testValidateCif(): void
    {
        $results = [
            ['type' => '', 'number' => '45678', 'expected' => true],
            ['type' => null, 'number' => '9999999', 'expected' => true],
            ['type' => 'CIF', 'number' => 'P4698162G', 'expected' => true],
            ['type' => 'CIF', 'number' => 'P4698162', 'expected' => false],
            ['type' => 'CIF', 'number' => 'P4698162G1', 'expected' => false],
            ['type' => 'CIF', 'number' => 'U10994408', 'expected' => true],
            ['type' => 'DNI', 'number' => '25296158E', 'expected' => true],
            ['type' => 'DNI', 'number' => '25296158S', 'expected' => false],
            ['type' => 'NIF', 'number' => '74003828V', 'expected' => true],
            ['type' => 'NIF', 'number' => '74003828J', 'expected' => false],
            ['type' => 'NIE', 'number' => 'Y1234567X', 'expected' => true],
        ];

        foreach ($results as $item) {
            $this->assertEquals(
                $item['expected'],
                FiscalNumberValidator::validate($item['type'], $item['number'], true),
                sprintf('Error validating %s %s', $item['type'], $item['number'])
            );
        }
    }
}
