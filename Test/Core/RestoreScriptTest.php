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

use FacturaScripts\Core\Tools;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas de las funciones de replace_index_to_restore.php (sin ejecutar la restauración).
 */
final class RestoreScriptTest extends TestCase
{
    /** @var string */
    private $packagePath;

    /** @var string */
    private $runningScript;

    public static function setUpBeforeClass(): void
    {
        if (!defined('FS_RESTORE_SKIP_RUN')) {
            define('FS_RESTORE_SKIP_RUN', true);
        }

        require_once FS_FOLDER . '/replace_index_to_restore.php';
    }

    public function testScriptVersion(): void
    {
        $this->assertSame(FS_RESTORE_VERSION, restoreScriptVersion(file_get_contents($this->runningScript)));
        $this->assertSame(7, restoreScriptVersion("<?php\nconst FS_RESTORE_URL = 'x';\nconst FS_RESTORE_VERSION = 7;\n"));

        // los scripts anteriores no tienen la constante
        $this->assertSame(0, restoreScriptVersion("<?php\nconst FS_RESTORE_URL = 'x';\n"));
    }

    public function testKeepsRunningScriptWhenPackageIsOlder(): void
    {
        $this->writePackage("<?php\n// old restore script\n");

        restorePrepareScript($this->packagePath, $this->runningScript);

        $this->assertSame(file_get_contents($this->runningScript), file_get_contents($this->packageScript()));
        $this->assertRestoreTimeDiffers();
    }

    public function testUsesPackageScriptWhenNewerOrEqual(): void
    {
        foreach ([FS_RESTORE_VERSION, FS_RESTORE_VERSION + 1] as $version) {
            $content = "<?php\nconst FS_RESTORE_VERSION = " . $version . ";\n// package script\n";
            $this->writePackage($content);

            restorePrepareScript($this->packagePath, $this->runningScript);

            $this->assertSame($content, file_get_contents($this->packageScript()), 'version ' . $version);
            $this->assertRestoreTimeDiffers();
        }
    }

    protected function setUp(): void
    {
        $this->packagePath = Tools::folder('MyFiles', 'Tmp', 'RestoreScriptTest');
        Tools::folderDelete($this->packagePath);
        Tools::folderCheckOrCreate($this->packagePath);

        $this->runningScript = FS_FOLDER . DIRECTORY_SEPARATOR . 'replace_index_to_restore.php';
    }

    protected function tearDown(): void
    {
        Tools::folderDelete($this->packagePath);
    }

    private function assertRestoreTimeDiffers(): void
    {
        clearstatcache();
        $index = $this->packagePath . DIRECTORY_SEPARATOR . 'index.php';
        $this->assertSame(filemtime($index) - 60, filemtime($this->packageScript()), 'same-mtime-as-index');
    }

    private function packageScript(): string
    {
        return $this->packagePath . DIRECTORY_SEPARATOR . 'replace_index_to_restore.php';
    }

    private function writePackage(string $restoreContent): void
    {
        file_put_contents($this->packagePath . DIRECTORY_SEPARATOR . 'index.php', "<?php\n// index\n");
        file_put_contents($this->packageScript(), $restoreContent);
    }
}
