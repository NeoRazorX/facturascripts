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

use FacturaScripts\Core\Telemetry;
use FacturaScripts\Core\Tools;
use PHPUnit\Framework\TestCase;

final class TelemetryTest extends TestCase
{
    /** @var array */
    private $original = [];

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

    protected function setUp(): void
    {
        foreach (['telemetryinstall', 'telemetrykey', 'telemetrylastu'] as $key) {
            $this->original[$key] = Tools::settings('default', $key);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->original as $key => $value) {
            Tools::settingsSet('default', $key, $value);
        }
    }
}
