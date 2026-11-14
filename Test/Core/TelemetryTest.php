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

namespace FacturaScripts\Test\Core;

use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Telemetry;
use FacturaScripts\Core\Tools;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class TelemetryTest extends TestCase
{
    /** @var array */
    private $original = [];

    /** @var User[] */
    private $users = [];

    public function testLangUsers(): void
    {
        $defaultLang = Tools::config('lang');
        $before = $this->getLangUsers();

        // un usuario activo en catalán, otro sin idioma y otro desactivado en euskera
        $this->createUser('ca_ES', true);
        $this->createUser(null, true);
        $this->createUser('eu_ES', false);

        // los usuarios sin idioma cuentan como el idioma de la instalación y los desactivados no cuentan
        $after = $this->getLangUsers();
        $this->assertEquals(($before['ca_ES'] ?? 0) + 1, $after['ca_ES'] ?? 0);
        $this->assertEquals(($before[$defaultLang] ?? 0) + 1, $after[$defaultLang] ?? 0);
        $this->assertEquals($before['eu_ES'] ?? 0, $after['eu_ES'] ?? 0);
        $this->assertArrayNotHasKey('', $after);
    }

    public function testUpdateNotRegistered(): void
    {
        // sin instalación registrada no se envía nada, aunque se fuerce
        Tools::settingsSet('default', 'telemetryinstall', null);
        Tools::settingsSet('default', 'telemetrylastu', null);

        $telemetry = new Telemetry();
        $this->assertFalse($telemetry->ready());
        $this->assertFalse($telemetry->update());
        $this->assertFalse($telemetry->update(true));
    }

    public function testUpdateRespectsInterval(): void
    {
        // instalación registrada y actualizada hace poco
        $lastUpdate = time();
        Tools::settingsSet('default', 'telemetryinstall', 999999);
        Tools::settingsSet('default', 'telemetrylastu', $lastUpdate);

        // sin forzar, no se envía ni se modifica la fecha de la última actualización
        $telemetry = new Telemetry();
        $this->assertTrue($telemetry->ready());
        $this->assertFalse($telemetry->update());
        $this->assertEquals($lastUpdate, Tools::settings('default', 'telemetrylastu'));
    }

    private function createUser(?string $langcode, bool $enabled): void
    {
        $user = new User();
        $user->nick = 'test_tlm_' . mt_rand(1, 99999);
        $user->setPassword('test-telemetry-1234');
        $user->langcode = $langcode;
        $user->enabled = $enabled;
        $this->assertTrue($user->save(), 'can-not-save-user');
        $this->users[] = $user;
    }

    private function getLangUsers(): array
    {
        $method = new ReflectionMethod(Telemetry::class, 'collectData');
        $method->setAccessible(true);
        $data = $method->invoke(new Telemetry());

        // convertimos "ca_ES:1,es_ES:4" en ['ca_ES' => 1, 'es_ES' => 4]
        $result = [];
        foreach (array_filter(explode(',', $data['langusers'])) as $item) {
            [$lang, $total] = explode(':', $item);
            $result[$lang] = (int)$total;
        }
        return $result;
    }

    protected function setUp(): void
    {
        foreach (['telemetryinstall', 'telemetrykey', 'telemetrylastu'] as $key) {
            $this->original[$key] = Tools::settings('default', $key);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->users as $user) {
            $user->delete();
        }
        $this->users = [];

        foreach ($this->original as $key => $value) {
            Tools::settingsSet('default', $key, $value);
        }
    }
}
