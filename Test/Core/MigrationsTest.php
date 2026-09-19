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

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Migrations;
use FacturaScripts\Core\Template\MigrationClass;
use FacturaScripts\Core\Tools;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MigrationsTest extends TestCase
{
    /** @var string|null */
    private $backup;

    /** @var string */
    private $file;

    protected function setUp(): void
    {
        // guardamos el registro real de migraciones para restaurarlo al terminar
        $this->file = Tools::folder('MyFiles', Migrations::FILE_NAME);
        $this->backup = file_exists($this->file) ? file_get_contents($this->file) : null;
        MiniLog::clear();
    }

    protected function tearDown(): void
    {
        if ($this->backup === null) {
            if (file_exists($this->file)) {
                unlink($this->file);
            }
            return;
        }

        file_put_contents($this->file, $this->backup);
    }

    public function testSuccessfulMigrationIsMarkedAsExecuted(): void
    {
        $migration = new MigrationsTestOk();
        $this->assertTrue(Migrations::runPluginMigration($migration));
        $this->assertSame(1, $migration->runs);
        $this->assertContains($migration->getFullMigrationName(), $this->executed());

        // una segunda llamada no vuelve a ejecutarla
        $this->assertTrue(Migrations::runPluginMigration($migration));
        $this->assertSame(1, $migration->runs);
    }

    public function testFailedMigrationIsLoggedAndNotMarkedAsExecuted(): void
    {
        $migration = new MigrationsTestFail();
        $this->assertFalse(Migrations::runPluginMigration($migration));
        $this->assertNotContains($migration->getFullMigrationName(), $this->executed());

        $errors = MiniLog::read('', ['error']);
        $this->assertCount(1, $errors);
        $this->assertStringContainsString($migration->getFullMigrationName(), $errors[0]['message']);
        $this->assertStringContainsString('boom', $errors[0]['message']);

        // al no estar marcada, se vuelve a intentar
        $this->assertFalse(Migrations::runPluginMigration($migration));
        $this->assertSame(2, $migration->runs);
    }

    public function testFailedMigrationStopsTheFollowingOnes(): void
    {
        $before = new MigrationsTestOk();
        $fail = new MigrationsTestFail();
        $after = new MigrationsTestOk2();
        $this->assertFalse(Migrations::runPluginMigrations([$before, $fail, $after]));

        // la anterior se ejecuta y se marca; la fallida y la posterior quedan pendientes
        $this->assertSame(1, $before->runs);
        $this->assertSame(1, $fail->runs);
        $this->assertSame(0, $after->runs);
        $executed = $this->executed();
        $this->assertContains($before->getFullMigrationName(), $executed);
        $this->assertNotContains($fail->getFullMigrationName(), $executed);
        $this->assertNotContains($after->getFullMigrationName(), $executed);

        // en el siguiente intento se reanuda desde la fallida
        $this->assertFalse(Migrations::runPluginMigrations([$before, $fail, $after]));
        $this->assertSame(1, $before->runs);
        $this->assertSame(2, $fail->runs);
        $this->assertSame(0, $after->runs);
    }

    public function testExecHelperThrowsOnInvalidSql(): void
    {
        $migration = new MigrationsTestBadSql();
        $this->assertFalse(Migrations::runPluginMigration($migration));
        $this->assertNotContains($migration->getFullMigrationName(), $this->executed());

        $errors = MiniLog::read('', ['error']);
        $messages = array_column($errors, 'message');
        $this->assertNotEmpty(array_filter($messages, function (string $message) {
            return str_contains($message, 'MIGRATION ERROR') && str_contains($message, 'SQL ERROR');
        }));
    }

    public function testCoreRunReturnsBool(): void
    {
        $this->assertTrue(Migrations::run());
    }

    private function executed(): array
    {
        if (!file_exists($this->file)) {
            return [];
        }

        $data = json_decode(file_get_contents($this->file), true);
        return is_array($data) ? $data : [];
    }
}

final class MigrationsTestOk extends MigrationClass
{
    const MIGRATION_NAME = 'migrations_test_ok';

    /** @var int */
    public $runs = 0;

    public function run(): void
    {
        $this->runs++;
    }
}

final class MigrationsTestOk2 extends MigrationClass
{
    const MIGRATION_NAME = 'migrations_test_ok_2';

    /** @var int */
    public $runs = 0;

    public function run(): void
    {
        $this->runs++;
    }
}

final class MigrationsTestFail extends MigrationClass
{
    const MIGRATION_NAME = 'migrations_test_fail';

    /** @var int */
    public $runs = 0;

    public function run(): void
    {
        $this->runs++;
        throw new RuntimeException('boom');
    }
}

final class MigrationsTestBadSql extends MigrationClass
{
    const MIGRATION_NAME = 'migrations_test_bad_sql';

    public function run(): void
    {
        $this->exec('SELECT * FROM table_that_does_not_exist_' . uniqid() . ';');
    }
}
