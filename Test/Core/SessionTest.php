<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

/**
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
final class SessionTest extends TestCase
{
    use LogErrorsTrait;

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

    public function testUser(): void
    {
        // comprobamos que si no hay usuario, devuelve uno vacío
        $this->assertFalse(Session::user()->exists(), 'session-user-not-empty');

        // creamos un usuario
        $user = new User();
        $user->nick = 'test_' . rand(1, 100);
        $user->password = '1234';
        $this->assertTrue($user->save(), 'session-user-not-saved');

        // asignamos el usuario a la sesión
        Session::set('user', $user);

        // comprobamos que el usuario es el mismo
        $this->assertEquals($user->nick, Session::user()->nick, 'session-user-not-same');

        // comprobamos que se puede cambiar el usuario
        $user2 = new User();
        $user2->nick = 'test_' . rand(101, 200);
        $user2->password = '1234';
        $this->assertTrue($user2->save(), 'session-user2-not-saved');
        Session::set('user', $user2);

        // comprobamos que el usuario es el mismo
        $this->assertEquals($user2->nick, Session::user()->nick, 'session-user2-not-same');

        // eliminamos
        $this->assertTrue($user->delete(), 'session-user-not-deleted');
        $this->assertTrue($user2->delete(), 'session-user2-not-deleted');
    }

    public function testGetClientIp(): void
    {
        // comprobamos que la llamada devuelve ::1
        $this->assertEquals('::1', Session::getClientIp(), 'session-ip-not-found');
    }

    public function testAnonymousTokens(): void
    {
        // creamos un token anónimo
        $token = Session::token(true);

        // creamos un segundo token anónimo
        $token2 = Session::token(true);
        $this->assertNotEquals($token, $token2, 'session-token-anonymous-not-different');

        // comprobamos que el token es correcto
        $this->assertIsString($token, 'session-token-not-string');
        $this->assertStringContainsString('|', $token, 'session-token-not-separator');
        $this->assertTrue(Session::tokenValidate($token), 'session-token-not-valid');

        // comprobamos que se puede validar varias veces
        $this->assertTrue(Session::tokenValidate($token), 'session-token-not-valid-2');
        $this->assertTrue(Session::tokenValidate($token), 'session-token-not-valid-3');

        // añadimos un número a la parte derecha del token y comprobamos que sigue siendo válido
        $alterToken = $token . '1';
        $this->assertTrue(Session::tokenValidate($alterToken), 'session-token-cant-add-number');

        // creamos un token no válido
        $invalidToken = Tools::randomString(10) . '|' . Tools::randomString(4);
        $this->assertFalse(Session::tokenValidate($invalidToken), 'session-token-invalid');

        // comprobamos que el token no está en la lista de tokens usados
        $this->assertFalse(Session::tokenExists($token), 'session-token-exists');

        // comprobamos que ahora el token si está en la lista de tokens usados
        $this->assertTrue(Session::tokenExists($token), 'session-token-not-exists');
    }

    public function testPrivateToken(): void
    {
        // añadimos una semilla
        Session::tokenSetSeed('test-seed');

        // creamos 2 tokens privados
        $token = Session::token();
        $token2 = Session::token();

        // comprobamos que los tokens son diferentes
        $this->assertNotEquals($token, $token2, 'session-token-private-not-different');

        // comprobamos que los tokens son válidos
        $this->assertTrue(Session::tokenValidate($token), 'session-token-private-not-valid');
        $this->assertTrue(Session::tokenValidate($token2), 'session-token-private2-not-valid');

        // comprobamos que los tokens no están en la lista de tokens usados
        $this->assertFalse(Session::tokenExists($token), 'session-token-private-exists');
        $this->assertFalse(Session::tokenExists($token2), 'session-token-private2-exists');

        // comprobamos que ahora los tokens si están en la lista de tokens usados
        $this->assertTrue(Session::tokenExists($token), 'session-token-private-not-exists');
        $this->assertTrue(Session::tokenExists($token2), 'session-token-private2-not-exists');

        // comprobamos que se puede validar varias veces
        $this->assertTrue(Session::tokenValidate($token), 'session-token-private-not-valid-2');
        $this->assertTrue(Session::tokenValidate($token), 'session-token-private-not-valid-3');

        // cambiamos la semilla
        Session::tokenSetSeed('test-seed2');

        // comprobamos que los tokens no son válidos
        $this->assertFalse(Session::tokenValidate($token), 'session-token-private-invalid');
        $this->assertFalse(Session::tokenValidate($token2), 'session-token-private2-invalid');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
