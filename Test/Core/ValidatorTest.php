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
}