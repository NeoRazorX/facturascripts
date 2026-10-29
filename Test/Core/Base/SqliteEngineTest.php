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

namespace FacturaScripts\Test\Core\Base;

use FacturaScripts\Core\Base\DataBase\SqliteEngine;
use FacturaScripts\Core\Base\DataBase\SqliteQueries;
use FacturaScripts\Core\KernelException;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Prueba el motor SQLite sobre una base de datos en memoria, sin depender
 * del motor configurado, para que se ejecute con cualquier base de datos.
 */
final class SqliteEngineTest extends TestCase
{
    /** @var SqliteEngine */
    private $engine;

    /** @var PDO */
    private $link;

    protected function setUp(): void
    {
        if (false === in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite not available.');
        }

        $this->engine = new SqliteEngine();
        $this->link = new PDO('sqlite::memory:');
        $this->link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function testCreateTableFromGeneratedSql(): void
    {
        $columns = [
            'id' => ['name' => 'id', 'type' => 'serial', 'null' => 'NO', 'default' => null],
            'code' => ['name' => 'code', 'type' => 'character varying(10)', 'null' => 'NO', 'default' => ''],
            'active' => ['name' => 'active', 'type' => 'boolean', 'null' => 'YES', 'default' => 'true'],
            'created' => ['name' => 'created', 'type' => 'date', 'null' => 'YES', 'default' => 'CURRENT_DATE'],
            'notes' => ['name' => 'notes', 'type' => 'text', 'null' => 'YES', 'default' => null],
        ];
        $constraints = [
            ['name' => 'test_pkey', 'constraint' => 'PRIMARY KEY (id)'],
            ['name' => 'uniq_code', 'constraint' => 'UNIQUE (code)'],
            ['name' => 'ignored_check', 'constraint' => 'CHECK (id > 0)'],
        ];
        $indexes = [['name' => 'code_idx', 'columns' => 'code']];

        $sql = $this->engine->getSQL()->sqlCreateTable('test_sqlite', $columns, $constraints, $indexes);
        $this->assertStringContainsString('"id" INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
        $this->assertStringNotContainsString('test_pkey', $sql, 'serial already defines the primary key');
        $this->assertStringContainsString('CONSTRAINT uniq_code UNIQUE (code)', $sql);
        $this->assertStringNotContainsString('ignored_check', $sql);
        $this->assertStringContainsString('DEFAULT TRUE', $sql);
        $this->assertStringContainsString('DEFAULT CURRENT_DATE', $sql);
        $this->assertStringContainsString('"notes" text DEFAULT NULL', $sql);

        // el SQL multi-sentencia debe ejecutarse completo
        $this->assertTrue($this->engine->exec($this->link, $sql));
        $this->assertSame(['test_sqlite'], $this->engine->listTables($this->link));

        $indexes = $this->engine->select($this->link, $this->engine->getSQL()->sqlIndexes('test_sqlite'));
        $this->assertContains('fs_code_idx', array_column($indexes, 'key_name'));

        $pk = $this->engine->select($this->link, $this->engine->getSQL()->sqlConstraints('test_sqlite'));
        $this->assertSame('PRIMARY', $pk[0]['name']);

        $pkCols = $this->engine->select($this->link, $this->engine->getSQL()->sqlConstraintsExtended('test_sqlite'));
        $this->assertSame(['id'], array_column($pkCols, 'column_name'));

        // columnas leídas de vuelta
        $data = $this->engine->select($this->link, $this->engine->getSQL()->sqlColumns('test_sqlite'));
        $cols = [];
        foreach ($data as $row) {
            $cols[$row['name']] = $this->engine->columnFromData($row);
        }
        $this->assertSame('serial', $cols['id']['type']);
        $this->assertSame('NO', $cols['id']['is_nullable']);
        $this->assertSame('character varying(10)', $cols['code']['type']);
        $this->assertSame('NO', $cols['code']['is_nullable']);
        $this->assertSame('true', $cols['active']['default']);
        $this->assertNull($cols['notes']['default']);

        // alter, rename, índices y borrado
        $sqlite = $this->engine->getSQL();
        $this->assertTrue($this->engine->exec($this->link, $sqlite->sqlAlterAddColumn('test_sqlite', [
            'name' => 'extra', 'type' => 'integer', 'null' => 'YES', 'default' => '0'
        ])));
        $this->assertTrue($this->engine->exec($this->link, $sqlite->sqlRenameColumn('test_sqlite', 'extra', 'extra2')));
        $this->assertTrue($this->engine->exec($this->link, $sqlite->sqlAddIndex('test_sqlite', 'extra_idx', 'extra2')));
        $this->assertTrue($this->engine->exec($this->link, $sqlite->sqlDropIndex('test_sqlite', ['name' => 'extra_idx'])));
        $this->assertTrue($this->engine->exec($this->link, $sqlite->sqlDropTable('test_sqlite')));
        $this->assertSame([], $this->engine->listTables($this->link));
    }

    public function testExecSplitsStatementsRespectingQuotes(): void
    {
        $sql = "CREATE TABLE t1 (txt TEXT, \"semi;col\" TEXT);"
            . " INSERT INTO t1 (txt, \"semi;col\") VALUES ('a;b', 'it''s; fine');"
            . " INSERT INTO t1 (txt) VALUES ('c')";
        $this->assertTrue($this->engine->exec($this->link, $sql));

        $rows = $this->engine->select($this->link, 'SELECT txt, "semi;col" AS s FROM t1 ORDER BY txt;');
        $this->assertSame([['txt' => 'a;b', 's' => "it's; fine"], ['txt' => 'c', 's' => null]], $rows);

        $this->assertTrue($this->engine->exec($this->link, '   '));
        $this->assertTrue($this->engine->exec($this->link, 'DELETE FROM t1;'));
    }

    public function testErrorsAreReportedNotThrown(): void
    {
        $this->assertFalse($this->engine->exec($this->link, 'INSERT INTO missing VALUES (1);'));
        $this->assertStringContainsString('missing', $this->engine->errorMessage($this->link));

        $this->assertSame([], $this->engine->select($this->link, 'SELECT * FROM missing;'));
        $this->assertStringContainsString('missing', $this->engine->errorMessage($this->link));

        $this->assertSame([], $this->engine->select($this->link, 'SELECT 1 AS one WHERE 0;'));
        $this->assertSame('', $this->engine->errorMessage($this->link));
    }

    public function testTransactions(): void
    {
        $this->engine->exec($this->link, 'CREATE TABLE t2 (id INTEGER);');

        $this->assertTrue($this->engine->beginTransaction($this->link));
        $this->assertTrue($this->engine->beginTransaction($this->link), 'nested begin is a no-op');
        $this->assertTrue($this->engine->inTransaction($this->link));
        $this->engine->exec($this->link, 'INSERT INTO t2 VALUES (1);');
        $this->assertTrue($this->engine->rollback($this->link));
        $this->assertFalse($this->engine->inTransaction($this->link));
        $this->assertSame([], $this->engine->select($this->link, 'SELECT * FROM t2;'));

        $this->assertTrue($this->engine->beginTransaction($this->link));
        $this->engine->exec($this->link, 'INSERT INTO t2 VALUES (2);');
        $this->assertTrue($this->engine->commit($this->link));
        $this->assertTrue($this->engine->commit($this->link), 'commit without transaction');
        $this->assertTrue($this->engine->rollback($this->link), 'rollback without transaction');
        $this->assertCount(1, $this->engine->select($this->link, 'SELECT * FROM t2;'));

        $this->engine->beginTransaction($this->link);
        $this->assertTrue($this->engine->close($this->link));
        $this->assertFalse($this->engine->inTransaction($this->link), 'close rolls back');
    }

    public function testEscapingAndHelpers(): void
    {
        $this->assertSame('"name"', $this->engine->escapeColumn($this->link, 'name'));
        $this->assertSame('"t"."na""me"', $this->engine->escapeColumn($this->link, 't.na"me'));
        $this->assertSame("it''s", $this->engine->escapeString($this->link, "it's"));
        $this->assertSame('CAST("num" AS INTEGER)', $this->engine->castInteger($this->link, 'num'));
        $this->assertSame('RANDOM()', $this->engine->random());
        $this->assertStringStartsWith('SQLITE 3.', $this->engine->version($this->link));

        $sqlite = new SqliteQueries();
        $this->assertSame('CAST(x as INTEGER)', $sqlite->sql2Int('x'));
        $this->assertSame('SELECT last_insert_rowid() as num;', $sqlite->sqlLastValue());
        $this->assertSame('', $sqlite->sqlAddConstraint('t', 'c', 'x'));
        $this->assertSame('', $sqlite->sqlAlterColumnDefault('t', []));
        $this->assertSame('', $sqlite->sqlAlterColumnNull('t', []));
        $this->assertSame('', $sqlite->sqlAlterModifyColumn('t', []));
        $this->assertSame('', $sqlite->sqlDropConstraint('t', []));
        $this->assertSame(', CONSTRAINT pk PRIMARY KEY (id)', $sqlite->sqlTableConstraints([
            ['name' => 'pk', 'constraint' => 'PRIMARY KEY (id)'],
        ]));
        $this->assertStringContainsString("pragma_table_info('o''t')", $sqlite->sqlConstraints("o't"));
    }

    public function testCompareDataTypes(): void
    {
        $this->assertTrue($this->engine->compareDataTypes('integer', 'integer'));
        $this->assertTrue($this->engine->compareDataTypes('INTEGER', 'int'));
        $this->assertTrue($this->engine->compareDataTypes('integer', 'boolean'));
        $this->assertTrue($this->engine->compareDataTypes('real', 'double precision'));
        $this->assertTrue($this->engine->compareDataTypes('varchar(10)', 'character varying(10)'));
        $this->assertFalse($this->engine->compareDataTypes('varchar(10)', 'character varying(20)'));
        $this->assertTrue($this->engine->compareDataTypes('character varying(5)', 'character varying(5)'));
        $this->assertFalse($this->engine->compareDataTypes('character varying(5)', 'character varying(6)'));
        $this->assertFalse($this->engine->compareDataTypes('text', 'integer'));
    }

    public function testColumnDefaultsAreNormalized(): void
    {
        $column = function ($default) {
            return $this->engine->columnFromData(['name' => 'c', 'type' => 'TEXT', 'dflt_value' => $default]);
        };

        $this->assertNull($column(null)['default']);
        $this->assertNull($column('NULL')['default']);
        $this->assertSame('abc', $column("'abc'")['default']);
        $this->assertSame('abc', $column('"abc"')['default']);
        $this->assertSame('false', $column('FALSE')['default']);
        $this->assertSame('CURRENT_DATE', $column('CURRENT_DATE')['default']);
        $this->assertSame('text', $column(null)['type']);
        $this->assertSame('YES', $column(null)['is_nullable']);
    }

    public function testDatabasePath(): void
    {
        $this->assertSame(':memory:', SqliteEngine::getDatabasePath(':memory:'));
        $this->assertSame('/tmp/fs.sqlite', SqliteEngine::getDatabasePath('/tmp/fs.sqlite'));
        $this->assertSame('C:\\fs.sqlite', SqliteEngine::getDatabasePath('C:\\fs.sqlite'));
        $this->assertSame(
            FS_FOLDER . DIRECTORY_SEPARATOR . 'MyFiles/fs.sqlite',
            SqliteEngine::getDatabasePath('MyFiles/fs.sqlite')
        );
        $this->assertStringEndsWith('facturascripts.sqlite', SqliteEngine::getDatabasePath(''));
    }

    public function testDatabasePathRejectsTraversal(): void
    {
        $this->expectException(KernelException::class);
        SqliteEngine::getDatabasePath('../outside.sqlite');
    }

    public function testDatabasePathRejectsNullByte(): void
    {
        $this->expectException(KernelException::class);
        SqliteEngine::getDatabasePath("fs\0.sqlite");
    }
}
