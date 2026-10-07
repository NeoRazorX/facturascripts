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
use FacturaScripts\Core\Tools;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

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

    public function testUpdateRootFilesSeparatesRestoreFileTime(): void
    {
        $origin = Tools::folder('MyFiles', 'Tmp', 'UpdaterTest', 'origin');
        $dest = Tools::folder('MyFiles', 'Tmp', 'UpdaterTest', 'dest');
        Tools::folderCheckOrCreate($origin);
        Tools::folderCheckOrCreate($dest);

        // ambos archivos del paquete con la misma fecha de modificación
        $time = time() - 3600;
        foreach (['index.php' => 'new index', 'replace_index_to_restore.php' => 'restore'] as $name => $content) {
            file_put_contents($origin . DIRECTORY_SEPARATOR . $name, $content);
            touch($origin . DIRECTORY_SEPARATOR . $name, $time);
        }
        file_put_contents($dest . DIRECTORY_SEPARATOR . 'index.php', 'old index');

        $method = new ReflectionMethod(Updater::class, 'updateRootFiles');
        $method->invoke(null, $origin, $dest);

        $index = $dest . DIRECTORY_SEPARATOR . 'index.php';
        $restore = $dest . DIRECTORY_SEPARATOR . 'replace_index_to_restore.php';
        $this->assertSame('new index', file_get_contents($index));
        $this->assertSame('restore', file_get_contents($restore));

        // opcache solo compara la fecha de modificación, así que deben ser distintas
        clearstatcache();
        $this->assertNotSame(filemtime($index), filemtime($restore));

        Tools::folderDelete(Tools::folder('MyFiles', 'Tmp', 'UpdaterTest'));
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
