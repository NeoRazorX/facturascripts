<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\Lib\MultiRequestProtection;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Tools;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class LoginTest extends TestCase
{
    /** @var Login */
    private $controller;

    protected function setUp(): void
    {
        $this->controller = new Login('Login', '/login');
        $this->controller->clearIncidents();
    }

    protected function tearDown(): void
    {
        $this->controller->clearIncidents();
        (new MultiRequestProtection())->clearSeed();
    }

    public function testBlockIP(): void
    {
        // bloqueamos la IP MAX_INCIDENT_COUNT - 1 veces y comprobamos que no se bloquea
        $ip = $this->getRandomIp();
        for ($i = 0; $i < Login::MAX_INCIDENT_COUNT - 1; $i++) {
            $this->controller->saveIncident($ip);
            $this->assertFalse($this->controller->userHasManyIncidents($ip));
        }

        // alcanzamos el umbral exacto y comprobamos que se bloquea
        $this->controller->saveIncident($ip);
        $this->assertTrue($this->controller->userHasManyIncidents($ip));

        // un incidente adicional sigue bloqueado
        $this->controller->saveIncident($ip);
        $this->assertTrue($this->controller->userHasManyIncidents($ip));

        // limpiamos la lista de IP bloqueadas
        $this->controller->clearIncidents();

        // comprobamos que la IP ya no está bloqueada
        $this->assertFalse($this->controller->userHasManyIncidents($ip));
    }

    public function testBlockIPExpired(): void
    {
        // bloqueamos la IP 10 veces con fecha de hace 2 horas y comprobamos que no se bloquea
        $ip = $this->getRandomIp();
        for ($i = 0; $i < 10; $i++) {
            $this->controller->saveIncident($ip, '', strtotime('-2 hours'));
            $this->assertFalse($this->controller->userHasManyIncidents($ip));
        }

        // bloqueamos la IP MAX_INCIDENT_COUNT - 1 veces más, con fecha actual, y comprobamos que no se bloquea
        for ($i = 0; $i < Login::MAX_INCIDENT_COUNT - 1; $i++) {
            $this->controller->saveIncident($ip);
            $this->assertFalse($this->controller->userHasManyIncidents($ip));
        }

        // bloqueamos la IP una vez más, con fecha actual, y comprobamos que se bloquea
        $this->controller->saveIncident($ip);
        $this->assertTrue($this->controller->userHasManyIncidents($ip));
    }

    public function testBlockUser(): void
    {
        // bloqueamos el usuario MAX_INCIDENT_COUNT - 1 veces, con IPs distintas, y comprobamos que no se bloquea
        $user = 'user1';
        for ($i = 0; $i < Login::MAX_INCIDENT_COUNT - 1; $i++) {
            $ip = $this->getRandomIp();
            $this->controller->saveIncident($ip, $user);
            $this->assertFalse($this->controller->userHasManyIncidents($ip, $user));
        }

        // alcanzamos el umbral exacto y comprobamos que se bloquea
        $ip = $this->getRandomIp();
        $this->controller->saveIncident($ip, $user);
        $this->assertTrue($this->controller->userHasManyIncidents($ip, $user));

        // un incidente adicional sigue bloqueado
        $ip = $this->getRandomIp();
        $this->controller->saveIncident($ip, $user);
        $this->assertTrue($this->controller->userHasManyIncidents($ip, $user));
    }

    public function testBlockUserExpired(): void
    {
        // bloqueamos el usuario 10 veces, con IPs distintas y fecha de hace 2 horas
        $user = 'user2';
        for ($i = 0; $i < 10; $i++) {
            $ip = $this->getRandomIp();
            $this->controller->saveIncident($ip, $user, strtotime('-2 hours'));
            $this->assertFalse($this->controller->userHasManyIncidents($ip, $user));
        }

        // bloqueamos el usuario MAX_INCIDENT_COUNT - 1 veces más, con IPs distintas y fecha actual
        for ($i = 0; $i < Login::MAX_INCIDENT_COUNT - 1; $i++) {
            $ip = $this->getRandomIp();
            $this->controller->saveIncident($ip, $user);
            $this->assertFalse($this->controller->userHasManyIncidents($ip, $user));
        }

        // alcanzamos el umbral exacto y comprobamos que se bloquea
        $ip = $this->getRandomIp();
        $this->controller->saveIncident($ip, $user);
        $this->assertTrue($this->controller->userHasManyIncidents($ip, $user));
    }

    public function testSaveIncidentWithoutUserOnlyAffectsIpList(): void
    {
        // varios incidentes sin usuario no deben acumular contador por usuario
        $ip = $this->getRandomIp();
        $user = 'ghost';
        for ($i = 0; $i < Login::MAX_INCIDENT_COUNT; $i++) {
            $this->controller->saveIncident($ip);
        }

        // la IP sí está bloqueada
        $this->assertTrue($this->controller->userHasManyIncidents($ip));

        // pero un usuario no relacionado consultado desde otra IP no
        $this->assertFalse($this->controller->userHasManyIncidents($this->getRandomIp(), $user));
    }

    public function testIncidentsAreIndependentBetweenUsers(): void
    {
        // un usuario acumula incidentes hasta justo por debajo del umbral
        $ip = $this->getRandomIp();
        $userA = 'alice';
        $userB = 'bob';
        for ($i = 0; $i < Login::MAX_INCIDENT_COUNT - 1; $i++) {
            $this->controller->saveIncident($this->getRandomIp(), $userA);
        }

        // otro usuario, consultado desde una IP nueva, no debe verse afectado
        $this->assertFalse($this->controller->userHasManyIncidents($ip, $userB));
    }

    public function testClearIncidentsRemovesBothLists(): void
    {
        $ip = $this->getRandomIp();
        $user = 'carol';
        for ($i = 0; $i < Login::MAX_INCIDENT_COUNT; $i++) {
            $this->controller->saveIncident($ip, $user);
        }
        $this->assertTrue($this->controller->userHasManyIncidents($ip));
        $this->assertTrue($this->controller->userHasManyIncidents($ip, $user));

        $this->controller->clearIncidents();

        $this->assertFalse($this->controller->userHasManyIncidents($ip));
        $this->assertFalse($this->controller->userHasManyIncidents($ip, $user));
    }

    public function testTitleBeforeAndAfterWizard(): void
    {
        $homepage = Tools::settings('default', 'homepage');

        try {
            // antes del asistente no se muestra el nombre provisional de la empresa
            Tools::settingsSet('default', 'homepage', null);
            $this->runController();
            $this->assertSame('FacturaScripts', $this->controller->title);

            // después del asistente se muestra el nombre de la empresa
            Tools::settingsSet('default', 'homepage', 'Root');
            $this->runController();
            $this->assertSame(Empresas::default()->nombrecorto, $this->controller->title);
        } finally {
            Tools::settingsSet('default', 'homepage', $homepage);
        }
    }

    public function testFormTokenIgnoresStaleNickCookie(): void
    {
        // el formulario de login genera el token sin nick
        $token = $this->newToken('');

        // aunque el navegador conserve la cookie fsNick de una sesión anterior, el token es válido
        $request = new Request([
            'cookies' => ['fsNick' => 'admin'],
            'request' => ['multireqtoken' => $token],
        ]);
        $this->assertTrue($this->invoke('validateFormToken', [$request]));
    }

    public function testLogoutTokenRequiresNick(): void
    {
        // el enlace de cerrar sesión se genera en páginas con sesión, con el nick en la semilla
        $request = new Request([
            'cookies' => ['fsNick' => 'admin'],
            'query' => ['multireqtoken' => $this->newToken('admin')],
        ]);
        $this->assertTrue($this->invoke('checkFormToken', [$request, 'admin']));

        // un token sin el nick no sirve para cerrar la sesión de ese usuario
        $request = new Request([
            'cookies' => ['fsNick' => 'admin'],
            'query' => ['multireqtoken' => $this->newToken('')],
        ]);
        $this->assertFalse($this->invoke('checkFormToken', [$request, 'admin']));
    }

    private function getRandomIp(): string
    {
        return rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255);
    }

    private function invoke(string $method, array $args)
    {
        return (new ReflectionMethod(Login::class, $method))->invokeArgs($this->controller, $args);
    }

    private function newToken(string $seed): string
    {
        $multiRequestProtection = new MultiRequestProtection();
        $multiRequestProtection->clearSeed();
        $multiRequestProtection->addSeed($seed);
        return $multiRequestProtection->newToken();
    }

    private function runController(): void
    {
        // run() imprime la página de login, que no necesitamos
        ob_start();
        $this->controller->run();
        ob_end_clean();
    }
}
