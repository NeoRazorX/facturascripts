<?php
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

use FacturaScripts\Core\Controller\Login;
use PHPUnit\Framework\TestCase;

final class LoginTest extends TestCase
{
    public function testBlockIP(): void
    {
        // inicializamos el controlador
        $controller = new Login('Login', '/login');

        // bloqueamos la IP 5 veces y comprobamos que no se bloquea
        $ip = $this->getRandomIp();
        for ($i = 0; $i < 5; $i++) {
            $controller->saveIncident($ip);
            $this->assertFalse($controller->userHasManyIncidents($ip));
        }

        // bloqueamos la IP por sexta vez y comprobamos que se bloquea
        $controller->saveIncident($ip);
        $this->assertTrue($controller->userHasManyIncidents($ip));

        // bloqueamos la IP por séptima vez y comprobamos que se bloquea
        $controller->saveIncident($ip);
        $this->assertTrue($controller->userHasManyIncidents($ip));

        // limpiamos la lista de IP bloqueadas
        $controller->clearIncidents();

        // comprobamos que la IP ya no está bloqueada
        $this->assertFalse($controller->userHasManyIncidents($ip));
    }

    public function testBlockIPExpired(): void
    {
        // inicializamos el controlador
        $controller = new Login('Login', '/login');

        // bloqueamos la IP 10 veces cpn fecha de hace 2 horas y comprobamos que no se bloquea
        $ip = $this->getRandomIp();
        for ($i = 0; $i < 10; $i++) {
            $controller->saveIncident($ip, '', strtotime('-2 hours'));
            $this->assertFalse($controller->userHasManyIncidents($ip));
        }

        // bloqueamos la IP 5 veces más, con fecha actual, y comprobamos que no se bloquea
        for ($i = 0; $i < 5; $i++) {
            $controller->saveIncident($ip);
            $this->assertFalse($controller->userHasManyIncidents($ip));
        }

        // bloqueamos la IP una vez más, con fecha actual, y comprobamos que se bloquea
        $controller->saveIncident($ip);
        $this->assertTrue($controller->userHasManyIncidents($ip));

        // limpiamos la lista de IP bloqueadas
        $controller->clearIncidents();

        // comprobamos que la IP ya no está bloqueada
        $this->assertFalse($controller->userHasManyIncidents($ip));
    }

    public function testBlockUser(): void
    {
        // inicializamos el controlador
        $controller = new Login('Login', '/login');

        // bloqueamos el usuario 5 veces, con IPs distintas y comprobamos que no se bloquea
        $user = 'user1';
        for ($i = 0; $i < 5; $i++) {
            $ip = $this->getRandomIp();
            $controller->saveIncident($ip, $user);
            $this->assertFalse($controller->userHasManyIncidents($ip, $user));
        }

        // bloqueamos el usuario por sexta vez, con IPs distintas, y comprobamos que se bloquea
        $ip = $this->getRandomIp();
        $controller->saveIncident($ip, $user);
        $this->assertTrue($controller->userHasManyIncidents($ip, $user));

        // bloqueamos el usuario por séptima vez, con IPs distintas, y comprobamos que se bloquea
        $ip = $this->getRandomIp();
        $controller->saveIncident($ip, $user);

        // limpiamos la lista de IP bloqueadas
        $controller->clearIncidents();

        // comprobamos que el usuario ya no está bloqueado
        $this->assertFalse($controller->userHasManyIncidents($ip, $user));
    }

    public function testBlockUserExpired(): void
    {
        // inicializamos el controlador
        $controller = new Login('Login', '/login');

        // bloqueamos el usuario 10 veces, con IPs distintas, con fecha de hace 2 horas y comprobamos que no se bloquea
        $user = 'user2';
        for ($i = 0; $i < 10; $i++) {
            $ip = $this->getRandomIp();
            $controller->saveIncident($ip, $user, strtotime('-2 hours'));
            $this->assertFalse($controller->userHasManyIncidents($ip, $user));
        }

        // bloqueamos el usuario 5 veces más, con IPs distintas, con fecha actual, y comprobamos que no se bloquea
        for ($i = 0; $i < 5; $i++) {
            $ip = $this->getRandomIp();
            $controller->saveIncident($ip, $user);
            $this->assertFalse($controller->userHasManyIncidents($ip, $user));
        }

        // bloqueamos el usuario una vez más, con IPs distintas, con fecha actual, y comprobamos que se bloquea
        $ip = $this->getRandomIp();
        $controller->saveIncident($ip, $user);
        $this->assertTrue($controller->userHasManyIncidents($ip, $user));

        // limpiamos la lista de IP bloqueadas
        $controller->clearIncidents();

        // comprobamos que el usuario ya no está bloqueado
        $this->assertFalse($controller->userHasManyIncidents($ip, $user));
    }

    private function getRandomIp(): string
    {
        return rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255);
    }
}
