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
use FacturaScripts\Core\Lib\ValidadorEcuador;
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
            ['type' => 'CI', 'number' => '1718137159', 'expected' => true],
            ['type' => 'CI', 'number' => '1784567890', 'expected' => false],
            //RUC Persona natural
            ['type' => 'RUC', 'number' => '1000000008001', 'expected' => true],
            ['type' => 'RUC', 'number' => '0102030405001', 'expected' => false],
            //RUC Sociedad pública
            ['type' => 'RUC', 'number' => '1760001550001', 'expected' => true],
            ['type' => 'RUC', 'number' => '2560001234001', 'expected' => false],
            //RUC sociedad privada
            ['type' => 'RUC', 'number' => '0190000001001', 'expected' => true],
            ['type' => 'RUC', 'number' => '2598765432001', 'expected' => false]
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

    public function testValidateEcuadorCi(): void
    {
        $valid = ['1718137159', '0102039849', '1104680135'];
        foreach ($valid as $number) {
            $this->assertTrue(ValidadorEcuador::validarCedula($number));
        }

        $invalid = ['1784567890', '1234567890', '1718137158'];
        foreach ($invalid as $number) {
            $this->assertFalse(ValidadorEcuador::validarCedula($number));
        }
    }

    public function testValidateEcuadorRucNatural(): void
    {
        $valid = ['0102039849001', '0926687856001', '1710034065001'];
        foreach ($valid as $number) {
            $this->assertTrue(ValidadorEcuador::validarRucNatural($number));
        }

        $invalid = ['0102030405001', '0926687856000', '2512345678001'];
        foreach ($invalid as $number) {
            $this->assertFalse(ValidadorEcuador::validarRucNatural($number));
        }
    }

    public function testValidateEcuadorRucPublica(): void
    {
        $valid = ['1760001550001', '1760000150001', '0160000000001'];
        foreach ($valid as $number) {
            $this->assertTrue(ValidadorEcuador::validarRucPublica($number));
        }

        $invalid = ['2560001234001', '1760001230001', '2560001550001'];
        foreach ($invalid as $number) {
            $this->assertFalse(ValidadorEcuador::validarRucPublica($number));
        }
    }

    public function testValidateEcuadorRucPrivada(): void
    {
        $valid = ['0190000001001', '0190000036001', '0190000028001'];
        foreach ($valid as $number) {
            $this->assertTrue(ValidadorEcuador::validarRucPrivada($number));
        }

        $invalid = ['2598765432001', '2590001234001', '1790012345001'];
        foreach ($invalid as $number) {
            $this->assertFalse(ValidadorEcuador::validarRucPrivada($number));
        }
    }
}
