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

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\DbUpdater;
use FacturaScripts\Core\Model\LogMessage;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class SqliteSupportTest extends TestCase
{
    use LogErrorsTrait;

    /** @var DataBase */
    private $db;

    public function testConnectionAndVersion(): void
    {
        if ($this->db()->type() !== 'sqlite') {
            $this->markTestSkipped('SQLite-only test.');
        }

        $this->assertTrue($this->db()->connect());
        $version = $this->db()->select('SELECT sqlite_version() AS version;');
        $this->assertNotEmpty($version);
        $this->assertNotEmpty($version[0]['version']);
    }

    public function testSchemaCreateAndIndexUpdate(): void
    {
        if ($this->db()->type() !== 'sqlite') {
            $this->markTestSkipped('SQLite-only test.');
        }

        DbUpdater::rebuild();
        DbUpdater::dropTable('test_table');

        $structure = DbUpdater::readTableXml(Tools::folder('Test', '__files', 'test_table.xml'));
        $this->assertTrue(DbUpdater::createTable('test_table', $structure));
        $this->assertTrue($this->db()->tableExists('test_table'));

        DbUpdater::rebuild();
        $updated = DbUpdater::readTableXml(Tools::folder('Test', '__files', 'test_table_update_4.xml'));
        $this->assertTrue(DbUpdater::updateTable('test_table', $updated));
        $this->assertCount(1, $this->db()->getIndexes('test_table'));

        $this->assertTrue(DbUpdater::dropTable('test_table'));
    }

    public function testSimpleModelCrud(): void
    {
        if ($this->db()->type() !== 'sqlite') {
            $this->markTestSkipped('SQLite-only test.');
        }

        $log = new LogMessage();
        $log->channel = 'sqlite-test';
        $log->level = 'info';
        $log->message = 'created from sqlite test';
        $this->assertTrue($log->save());
        $this->assertNotEmpty($log->id);

        $loaded = new LogMessage();
        $this->assertTrue($loaded->loadFromCode($log->id));
        $this->assertSame('created from sqlite test', $loaded->message);

        $loaded->message = 'updated from sqlite test';
        $this->assertTrue($loaded->save());

        $reloaded = new LogMessage();
        $this->assertTrue($reloaded->loadFromCode($log->id));
        $this->assertSame('updated from sqlite test', $reloaded->message);
        $this->assertTrue($reloaded->delete());
    }

    private function db(): DataBase
    {
        if (null === $this->db) {
            $this->db = new DataBase();
            $this->db->connect();
        }

        return $this->db;
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
