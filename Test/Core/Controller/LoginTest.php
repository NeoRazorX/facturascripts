<?php declare(strict_types=1);
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Controller;

use FacturaScripts\Core\Lib\Incident;
use PHPUnit\Framework\TestCase;

final class LoginTest extends TestCase
{
    /** @var Incident */
    private $incident;

    protected function setUp(): void
    {
        $this->incident = new Incident();
    }

    public function testBlockIP(): void
    {
        // bloqueamos la IP 5 veces y comprobamos que no se bloquea
        $ip = $this->getRandomIp();
        for ($i = 0; $i < 5; $i++) {
            $this->incident->saveIncident($ip);
            $this->assertFalse($this->incident->userHasManyIncidents($ip));
        }

        // bloqueamos la IP por sexta vez y comprobamos que se bloquea
        $this->incident->saveIncident($ip);
        $this->assertTrue($this->incident->userHasManyIncidents($ip));

        // bloqueamos la IP por séptima vez y comprobamos que se bloquea
        $this->incident->saveIncident($ip);
        $this->assertTrue($this->incident->userHasManyIncidents($ip));

        // limpiamos la lista de IP bloqueadas
        $this->incident->clearIncidents();

        // comprobamos que la IP ya no está bloqueada
        $this->assertFalse($this->incident->userHasManyIncidents($ip));
    }

    public function testBlockIPExpired(): void
    {
        // bloqueamos la IP 10 veces cpn fecha de hace 2 horas y comprobamos que no se bloquea
        $ip = $this->getRandomIp();
        for ($i = 0; $i < 10; $i++) {
            $this->incident->saveIncident($ip, '', strtotime('-2 hours'));
            $this->assertFalse($this->incident->userHasManyIncidents($ip));
        }

        // bloqueamos la IP 5 veces más, con fecha actual, y comprobamos que no se bloquea
        for ($i = 0; $i < 5; $i++) {
            $this->incident->saveIncident($ip);
            $this->assertFalse($this->incident->userHasManyIncidents($ip));
        }

        // bloqueamos la IP una vez más, con fecha actual, y comprobamos que se bloquea
        $this->incident->saveIncident($ip);
        $this->assertTrue($this->incident->userHasManyIncidents($ip));

        // limpiamos la lista de IP bloqueadas
        $this->incident->clearIncidents();

        // comprobamos que la IP ya no está bloqueada
        $this->assertFalse($this->incident->userHasManyIncidents($ip));
    }

    public function testBlockUser(): void
    {
        // bloqueamos el usuario 5 veces, con IPs distintas y comprobamos que no se bloquea
        $user = 'user1';
        for ($i = 0; $i < 5; $i++) {
            $ip = $this->getRandomIp();
            $this->incident->saveIncident($ip, $user);
            $this->assertFalse($this->incident->userHasManyIncidents($ip, $user));
        }

        // bloqueamos el usuario por sexta vez, con IPs distintas, y comprobamos que se bloquea
        $ip = $this->getRandomIp();
        $this->incident->saveIncident($ip, $user);
        $this->assertTrue($this->incident->userHasManyIncidents($ip, $user));

        // bloqueamos el usuario por séptima vez, con IPs distintas, y comprobamos que se bloquea
        $ip = $this->getRandomIp();
        $this->incident->saveIncident($ip, $user);

        // limpiamos la lista de IP bloqueadas
        $this->incident->clearIncidents();

        // comprobamos que el usuario ya no está bloqueado
        $this->assertFalse($this->incident->userHasManyIncidents($ip, $user));
    }

    public function testBlockUserExpired(): void
    {
        // bloqueamos el usuario 10 veces, con IPs distintas, con fecha de hace 2 horas y comprobamos que no se bloquea
        $user = 'user2';
        for ($i = 0; $i < 10; $i++) {
            $ip = $this->getRandomIp();
            $this->incident->saveIncident($ip, $user, strtotime('-2 hours'));
            $this->assertFalse($this->incident->userHasManyIncidents($ip, $user));
        }

        // bloqueamos el usuario 5 veces más, con IPs distintas, con fecha actual, y comprobamos que no se bloquea
        for ($i = 0; $i < 5; $i++) {
            $ip = $this->getRandomIp();
            $this->incident->saveIncident($ip, $user);
            $this->assertFalse($this->incident->userHasManyIncidents($ip, $user));
        }

        // bloqueamos el usuario una vez más, con IPs distintas, con fecha actual, y comprobamos que se bloquea
        $ip = $this->getRandomIp();
        $this->incident->saveIncident($ip, $user);
        $this->assertTrue($this->incident->userHasManyIncidents($ip, $user));

        // limpiamos la lista de IP bloqueadas
        $this->incident->clearIncidents();

        // comprobamos que el usuario ya no está bloqueado
        $this->assertFalse($this->incident->userHasManyIncidents($ip, $user));
    }

    private function getRandomIp(): string
    {
        return random_int(1, 255) . '.' . random_int(1, 255) . '.' . random_int(1, 255) . '.' . random_int(1, 255);
    }
}
