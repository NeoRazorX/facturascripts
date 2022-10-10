<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Session;
use PHPUnit\Framework\TestCase;

/**
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
final class SessionTest extends TestCase
{
    public function testSet(): void
    {
        // añadimos un valor a la sesión
        $key = 'test-key';
        $value = '1234';
        Session::set($key, $value);

        // comprobamos que se ha añadido
        $this->assertEquals($value, Session::get($key), 'session-value-not-found');

        // comprobamos que se puede cambiar el valor
        Session::set($key, '5678');
        $this->assertEquals('5678', Session::get($key), 'session-value-not-changed');
    }

    public function testGetNull(): void
    {
        // comprobamos que devuelve null si no existe
        $this->assertNull(Session::get('not-found'), 'session-value-not-null');
    }

    public function testGetClientIp(): void
    {
        // comprobamos que la llamada devuelve ::1
        $this->assertEquals('::1', Session::getClientIp(), 'session-ip-not-found');
    }
}
