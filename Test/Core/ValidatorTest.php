<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testAlphaNumeric(): void
    {
        $this->assertTrue(Validator::alphaNumeric('test'));
        $this->assertTrue(Validator::alphaNumeric('test123'));

        $this->assertFalse(Validator::alphaNumeric('test 123'));
        $this->assertTrue(Validator::alphaNumeric('test 123', ' '));

        $this->assertFalse(Validator::alphaNumeric('test-123'));
        $this->assertTrue(Validator::alphaNumeric('test-123', '-'));

        $this->assertFalse(Validator::alphaNumeric('test_123'));
        $this->assertTrue(Validator::alphaNumeric('test_123', '_'));

        $this->assertFalse(Validator::alphaNumeric('test.123'));
        $this->assertTrue(Validator::alphaNumeric('test.123', '.'));

        $this->assertFalse(Validator::alphaNumeric('test,123'));
        $this->assertTrue(Validator::alphaNumeric('test,123', ','));

        $this->assertFalse(Validator::alphaNumeric('test/123'));
        $this->assertTrue(Validator::alphaNumeric('test/123', '/'));

        $this->assertFalse(Validator::alphaNumeric('test\123'));
        $this->assertTrue(Validator::alphaNumeric('test\123', '\\'));

        $this->assertFalse(Validator::alphaNumeric('test:123'));
        $this->assertTrue(Validator::alphaNumeric('test:123', ':'));

        $this->assertFalse(Validator::alphaNumeric('test;123'));
        $this->assertTrue(Validator::alphaNumeric('test;123', ';'));

        $this->assertFalse(Validator::alphaNumeric('test@123'));
        $this->assertTrue(Validator::alphaNumeric('test@123', '@'));

        $this->assertFalse(Validator::alphaNumeric('test#123'));
        $this->assertTrue(Validator::alphaNumeric('test#123', '#'));

        $this->assertFalse(Validator::alphaNumeric('test$123'));
        $this->assertTrue(Validator::alphaNumeric('test$123', '$'));

        $this->assertFalse(Validator::alphaNumeric('test%123'));
        $this->assertTrue(Validator::alphaNumeric('test%123', '%'));

        $this->assertFalse(Validator::alphaNumeric('test&123'));
        $this->assertTrue(Validator::alphaNumeric('test&123', '&'));

        $this->assertFalse(Validator::alphaNumeric('test*123'));
        $this->assertTrue(Validator::alphaNumeric('test*123', '*'));

        $this->assertFalse(Validator::alphaNumeric('test+123'));
        $this->assertTrue(Validator::alphaNumeric('test+123', '+'));

        $this->assertFalse(Validator::alphaNumeric('test(123)'));
        $this->assertTrue(Validator::alphaNumeric('test(123)', '()'));

        $this->assertFalse(Validator::alphaNumeric('test[123]'));
        $this->assertTrue(Validator::alphaNumeric('test[123]', '[]'));

        $this->assertFalse(Validator::alphaNumeric('test{123}'));
        $this->assertTrue(Validator::alphaNumeric('test{123}', '{}'));

        $this->assertFalse(Validator::alphaNumeric('test<123>'));
        $this->assertTrue(Validator::alphaNumeric('test<123>', '<>'));

        $this->assertFalse(Validator::alphaNumeric('test=123'));
        $this->assertTrue(Validator::alphaNumeric('test=123', '='));

        $this->assertFalse(Validator::alphaNumeric('test¿123?'));
        $this->assertTrue(Validator::alphaNumeric('test¿123?', '¿?'));

        $this->assertFalse(Validator::alphaNumeric('test¡123!'));
        $this->assertTrue(Validator::alphaNumeric('test¡123!', '¡!'));

        $this->assertFalse(Validator::alphaNumeric('test|123'));
        $this->assertTrue(Validator::alphaNumeric('test|123', '|'));

        $this->assertFalse(Validator::alphaNumeric('test^123'));
        $this->assertTrue(Validator::alphaNumeric('test^123', '^'));

        $this->assertFalse(Validator::alphaNumeric('test~123'));
        $this->assertTrue(Validator::alphaNumeric('test~123', '~'));

        $this->assertFalse(Validator::alphaNumeric('test`123'));
        $this->assertTrue(Validator::alphaNumeric('test`123', '`'));

        $this->assertFalse(Validator::alphaNumeric('test"123'));
        $this->assertTrue(Validator::alphaNumeric('test"123', '"'));

        $this->assertFalse(Validator::alphaNumeric("test'123"));
        $this->assertTrue(Validator::alphaNumeric("test'123", "'"));

        $this->assertFalse(Validator::alphaNumeric('test-456+Y'));
        $this->assertTrue(Validator::alphaNumeric('test-456+Y', '-_.+\\'));

        $this->assertFalse(Validator::alphaNumeric('test-456+Y', '-_.+\\', 20));
        $this->assertTrue(Validator::alphaNumeric('test-456+Y', '-_.+\\', 10));
        $this->assertTrue(Validator::alphaNumeric('test-456+Y', '-_.+\\', 10, 20));
        $this->assertTrue(Validator::alphaNumeric('test-456+Y', '-_.+\\', 10, 10));
        $this->assertFalse(Validator::alphaNumeric('test-456+Y', '-_.+\\', 1, 9));
    }

    public function testEmail(): void
    {
        $this->assertTrue(Validator::email('carlos@facturascripts.com'));
        $this->assertFalse(Validator::email('carlos'));
        $this->assertFalse(Validator::email('carlos@'));
        $this->assertFalse(Validator::email('@facturascripts.com'));
    }

    public function testString(): void
    {
        $this->assertTrue(Validator::string('test'));
        $this->assertFalse(Validator::string(''));
        $this->assertTrue(Validator::string('', 0));
        $this->assertFalse(Validator::string('test', 5));
        $this->assertTrue(Validator::string('test', 4));
        $this->assertTrue(Validator::string('test', 4, 5));
        $this->assertTrue(Validator::string('test', 4, 4));
        $this->assertFalse(Validator::string('test', 1, 3));
    }

    public function testUrl(): void
    {
        $this->assertTrue(Validator::url('http://facturascripts.com'));
        $this->assertTrue(Validator::url('https://facturascripts.com'));
        $this->assertTrue(Validator::url('ftp://facturascripts.com'));
        $this->assertTrue(Validator::url('ftps://facturascripts.com'));

        $this->assertTrue(Validator::url('www.facturascripts.com'));
        $this->assertFalse(Validator::url('www.facturascripts.com', true));

        $this->assertFalse(Validator::url('javascript:alert("test")'));
        $this->assertFalse(Validator::url('javascript://alert("test")'));
        $this->assertFalse(Validator::url('jAvAsCriPt://alert("test")'));
        $this->assertFalse(Validator::url('data:text/html;base64,PHNjcmlwdD5hbGVydCgiVGVzdCIpOzwvc2NyaXB0Pg=='));
    }

    public function testValidDates(): void
    {
        $validDates = [
            // Formato d-m-Y
            '15-01-2023',
            '31-12-2024',
            '29-02-2020', // Año bisiesto
            // Formato Y-m-d
            '2023-01-15',
            '2024-12-31',
            '2020-02-29', // Año bisiesto
        ];

        foreach ($validDates as $date) {
            $this->assertTrue(Validator::date($date));
        }
    }

    public function testInvalidDates(): void
    {
        $invalidDates = [
            '15/01/2023',   // Separador incorrecto
            '29-02-2023',   // No es año bisiesto (formato d-m-Y)
            '2023-02-29',   // No es año bisiesto (formato Y-m-d)
            '32-01-2023',   // Día inválido (formato d-m-Y)
            '2023-01-32',   // Día inválido (formato Y-m-d)
            '15-13-2023',   // Mes inválido (formato d-m-Y)
            '2023-13-15',   // Mes inválido (formato Y-m-d)
            '01-01-23',     // Formato corto de año
            '23-01-01',     // Formato corto de año
            '2023/01/15',   // Separador incorrecto en formato Y-m-d
            '',
            'not-a-date',
        ];

        foreach ($invalidDates as $date) {
            $this->assertFalse(Validator::date($date));
        }
    }

    public function testValidDateTimes(): void
    {
        $validDateTimes = [
            // Formato d-m-Y H:i:s
            '15-01-2023 14:30:00',
            '31-12-2024 23:59:59',
            '29-02-2020 00:00:00', // Año bisiesto
            // Formato Y-m-d H:i:s
            '2023-01-15 14:30:00',
            '2024-12-31 23:59:59',
            '2020-02-29 00:00:00', // Año bisiesto
            // Formato ISO 8601 con T
            '2023-01-15T14:30:00',
            '2024-12-31T23:59:59',
            '2020-02-29T00:00:00', // Año bisiesto
        ];

        foreach ($validDateTimes as $datetime) {
            $this->assertTrue(Validator::datetime($datetime));
        }
    }

    public function testInvalidDateTimes(): void
    {
        $invalidDateTimes = [
            '29-02-2023 14:30:00',     // Fecha inválida (d-m-Y)
            '2023-02-29 14:30:00',     // Fecha inválida (Y-m-d)
            '2023-02-29T14:30:00',     // Fecha inválida (Y-m-d) con T
            '15-01-2023 24:00:00',     // Hora inválida
            '2023-01-15 24:00:00',     // Hora inválida
            '2023-01-15T24:00:00',     // Hora inválida con T
            '15-01-2023 14:60:00',     // Minutos inválidos
            '15-01-2023 14:30:60',     // Segundos inválidos
            '15/01/2023 14:30:00',     // Separador de fecha incorrecto
            '2023/01/15 14:30:00',     // Separador de fecha incorrecto
            '2023/01/15T14:30:00',     // Separador de fecha incorrecto con T
            '',
            'not-a-datetime',
        ];

        foreach ($invalidDateTimes as $datetime) {
            $this->assertFalse(Validator::datetime($datetime));
        }
    }
}
