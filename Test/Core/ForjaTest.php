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

use FacturaScripts\Core\Internal\Forja;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class ForjaTest extends TestCase
{
    protected function tearDown(): void
    {
        Forja::$builds = null;
        $this->setPluginList(null);
    }

    public function testBuildsDiscardInvalidItems(): void
    {
        Forja::$builds = [
            'error',
            [
                'project' => 1,
                'name' => 'CORE',
                'builds' => [
                    'error',
                    [
                        'version' => 2026.1,
                        'stable' => true,
                        'beta' => false,
                        'mincore' => null,
                        'maxcore' => null,
                    ],
                    ['version' => 2026.2, 'stable' => true],
                ],
            ],
            ['project' => 2, 'builds' => []],
            ['project' => '3', 'name' => 'InvalidProject', 'builds' => []],
        ];

        $this->assertSame([[
            'project' => 1,
            'name' => 'CORE',
            'builds' => [[
                'version' => 2026.1,
                'stable' => true,
                'beta' => false,
                'mincore' => null,
                'maxcore' => null,
            ]],
        ]], Forja::builds());
    }

    public function testBuildsDiscardInvalidResponse(): void
    {
        Forja::$builds = 'service unavailable';

        $this->assertSame([], Forja::builds());
    }

    public function testPluginsDiscardInvalidItems(): void
    {
        $this->setPluginList([
            'error',
            ['name' => 'PluginOne', 'description' => 'First plugin'],
            ['description' => 'Missing name'],
            ['name' => null],
            ['name' => 'PluginTwo'],
        ]);

        $this->assertSame([
            ['name' => 'PluginOne', 'description' => 'First plugin'],
            ['name' => 'PluginTwo'],
        ], Forja::plugins());
    }

    public function testPluginsDiscardInvalidResponse(): void
    {
        $this->setPluginList('service unavailable');

        $this->assertSame([], Forja::plugins());
    }

    private function setPluginList($value): void
    {
        $property = new ReflectionProperty(Forja::class, 'pluginList');
        $property->setValue(null, $value);
    }
}
