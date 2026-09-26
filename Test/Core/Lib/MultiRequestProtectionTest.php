<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\AppKey;
use FacturaScripts\Core\Lib\MultiRequestProtection;
use PHPUnit\Framework\TestCase;

final class MultiRequestProtectionTest extends TestCase
{
    public function testTokenDependsOnSeed(): void
    {
        $protection = new MultiRequestProtection();
        $protection->clearSeed();
        $protection->addSeed('user1');
        $token = $protection->newToken();
        $this->assertTrue($protection->validate($token), 'token-not-valid');

        // el token de un usuario no sirve para otro
        $protection->clearSeed();
        $protection->addSeed('user2');
        $this->assertFalse($protection->validate($token), 'token-valid-for-other-user');

        $protection->clearSeed();
    }

    public function testValidate(): void
    {
        $protection = new MultiRequestProtection();
        $protection->clearSeed();

        $token = $protection->newToken();
        $this->assertTrue($protection->validate($token), 'token-not-valid');

        // la parte aleatoria no se comprueba, porque puede cambiarla el javascript
        $parts = explode('|', $token);
        $this->assertTrue($protection->validate($parts[0] . '|abc123'), 'token-with-other-random-not-valid');

        // tokens mal formados o con firma incorrecta
        $this->assertFalse($protection->validate(''), 'empty-token-valid');
        $this->assertFalse($protection->validate($parts[0]), 'token-without-random-valid');
        $this->assertFalse($protection->validate(strrev($parts[0]) . '|' . $parts[1]), 'bad-token-valid');

        // los tokens caducan tras MAX_TOKEN_AGE horas
        $hours = MultiRequestProtection::MAX_TOKEN_AGE;
        $oldDate = date('YmdH', strtotime('-' . ($hours + 1) . ' hours'));
        $oldToken = AppKey::sign(MultiRequestProtection::PURPOSE, '|' . $oldDate) . '|abc123';
        $this->assertFalse($protection->validate($oldToken), 'expired-token-valid');

        $lastDate = date('YmdH', strtotime('-' . $hours . ' hours'));
        $lastToken = AppKey::sign(MultiRequestProtection::PURPOSE, '|' . $lastDate) . '|abc123';
        $this->assertTrue($protection->validate($lastToken), 'last-valid-token-not-valid');
    }
}
