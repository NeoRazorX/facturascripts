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
    public function testValidate(): void
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

    public function testValidateSpainCif(): void
    {
        $valid = ['P4698162G', 'B43359165', 'B85461424', 'A82744681', 'R2200465I'];
        foreach ($valid as $number) {
            $this->assertTrue(FiscalNumberValidator::isValidSpainCIF($number));
        }

        $invalid = ['P4698162E', 'P4698162G1', 'P4698162G2', 'P4698162G3', 'P4698162G4'];
        foreach ($invalid as $number) {
            $this->assertFalse(FiscalNumberValidator::isValidSpainCIF($number));
        }
    }

    public function testValidateSpainDni(): void
    {
        $valid = ['25296158E', '74003828V', '36155837K'];
        foreach ($valid as $number) {
            $this->assertTrue(FiscalNumberValidator::isValidSpainDNI($number));
        }

        $invalid = ['25296158S', '74003828J', '12345678B', '12345678C'];
        foreach ($invalid as $number) {
            $this->assertFalse(FiscalNumberValidator::isValidSpainDNI($number));
        }
    }
}
