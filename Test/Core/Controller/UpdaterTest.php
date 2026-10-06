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

namespace FacturaScripts\Test\Core\Controller;

use FacturaScripts\Core\Controller\Updater;
use FacturaScripts\Core\Internal\Forja;
use FacturaScripts\Core\Kernel;
use PHPUnit\Framework\TestCase;

final class UpdaterTest extends TestCase
{
    protected function tearDown(): void
    {
        Forja::$builds = null;
    }

    public function testCoreUpdateSkipsBuildsRequiringNewerPhp(): void
    {
        $nextVersion = Kernel::version() + 1;
        Forja::$builds = [[
            'project' => Forja::CORE_PROJECT_ID,
            'name' => 'CORE',
            'builds' => [
                $this->build($nextVersion, '99.0'),
                $this->build($nextVersion + 1, '99.0'),
            ],
        ]];
        $this->assertSame([], $this->coreItems());

        // una build posterior compatible con la versión actual de PHP sí se ofrece
        Forja::$builds[0]['builds'][] = $this->build($nextVersion + 2, PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION);
        $items = $this->coreItems();
        $this->assertCount(1, $items);
        $this->assertSame($nextVersion + 2, $items[0]['version']);
    }

    private function build(float $version, ?string $minPhp): array
    {
        return [
            'version' => $version,
            'stable' => true,
            'beta' => false,
            'mincore' => null,
            'maxcore' => null,
            'min_php' => $minPhp,
        ];
    }

    private function coreItems(): array
    {
        return array_values(array_filter(Updater::getUpdateItems(), static function (array $item): bool {
            return $item['id'] === Forja::CORE_PROJECT_ID;
        }));
    }
}
