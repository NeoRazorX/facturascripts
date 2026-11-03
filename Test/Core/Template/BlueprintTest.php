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

namespace FacturaScripts\Test\Core\Template;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\DbUpdater;
use FacturaScripts\Core\Template\Blueprint;
use FacturaScripts\Core\Template\TableClass;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class BlueprintTest extends TestCase
{
    use LogErrorsTrait;

    /** @var DataBase */
    private $db;

    public function testColumnTypes(): void
    {
        $table = new Blueprint('t');
        $table->id();
        $table->serial('num');
        $table->string('a');
        $table->string('b', 8);
        $table->text('c');
        $table->integer('d');
        $table->float('e');
        $table->boolean('f');
        $table->date('g');
        $table->time('h');
        $table->timestamp('i');
        $columns = $table->toArray()['columns'];

        $types = ['id' => 'serial', 'num' => 'serial', 'a' => 'character varying(255)', 'b' => 'character varying(8)',
            'c' => 'text', 'd' => 'integer', 'e' => 'double precision', 'f' => 'boolean', 'g' => 'date',
            'h' => 'time', 'i' => 'timestamp'];
        foreach ($types as $name => $type) {
            $this->assertEquals($type, $columns[$name]['type'], $name);
        }

        // serial: not null y sin default
        $this->assertEquals('NO', $columns['id']['null']);
        $this->assertNull($columns['id']['default']);
        $this->assertEquals('NO', $columns['num']['null']);

        // el resto: not null, sin default ni rename
        $this->assertEquals(['name' => 'a', 'type' => 'character varying(255)', 'null' => 'NO', 'default' => '', 'rename' => ''], $columns['a']);
    }

    public function testModifiers(): void
    {
        $table = new Blueprint('t');
        $table->string('a')->nullable()->rename('old');
        $table->boolean('b')->default(true);
        $table->boolean('c')->default(false);
        $table->integer('d')->default(0);
        $columns = $table->toArray()['columns'];

        $this->assertEquals('YES', $columns['a']['null']);
        $this->assertEquals('old', $columns['a']['rename']);
        $this->assertEquals('true', $columns['b']['default']);
        $this->assertEquals('false', $columns['c']['default']);
        $this->assertSame('0', $columns['d']['default']);
    }

    public function testStringDefaultsAreQuotedOnce(): void
    {
        $table = new Blueprint('t');
        $table->string('a')->default('nuevo');
        $table->string('b')->default("'nuevo'");
        $table->string('c')->default("it's");
        $table->string('d')->default('');
        $columns = $table->toArray()['columns'];

        $this->assertSame("'nuevo'", $columns['a']['default']);
        $this->assertSame("'nuevo'", $columns['b']['default']);
        $this->assertSame("'it''s'", $columns['c']['default']);
        $this->assertSame("''", $columns['d']['default']);
    }

    public function testConstraints(): void
    {
        $table = new Blueprint('t');
        $table->id();
        $table->string('code', 10)->unique();
        $table->string('nick', 50)->references('users', 'nick')->onDelete('set null');
        $table->integer('idx')->references('other', 'idx');
        $table->integer('x');
        $table->integer('y');
        $table->unique('x', 'y');
        $table->primary('x', 'y');
        $constraints = $table->toArray()['constraints'];

        $this->assertEquals('PRIMARY KEY (x,y)', $constraints['t_pkey']['constraint']);
        $this->assertEquals('UNIQUE (code)', $constraints['uniq_t_code']['constraint']);
        $this->assertEquals('UNIQUE (x,y)', $constraints['uniq_t_x_y']['constraint']);
        $this->assertEquals('ca_t_nick', $constraints['ca_t_nick']['name']);
        $this->assertEquals(
            'FOREIGN KEY (nick) REFERENCES users (nick) ON DELETE SET NULL ON UPDATE CASCADE',
            $constraints['ca_t_nick']['constraint']
        );
        $this->assertEquals(
            'FOREIGN KEY (idx) REFERENCES other (idx) ON DELETE RESTRICT ON UPDATE CASCADE',
            $constraints['ca_t_idx']['constraint']
        );
    }

    public function testIndexes(): void
    {
        $table = new Blueprint('t');
        $table->string('a')->index();
        $table->string('b');
        $table->string('c');
        $table->index('b', 'c');
        $indexes = $table->toArray()['indexes'];

        $this->assertEquals(['name' => 't_a', 'columns' => 'a'], $indexes['t_a']);
        $this->assertEquals(['name' => 't_b_c', 'columns' => 'b,c'], $indexes['t_b_c']);
    }

    public function testTableClassStructure(): void
    {
        $class = new class extends TableClass {
            public function define(Blueprint $table): void
            {
                $table->id();
                $table->string('name', 20);
            }
        };
        $structure = $class->structure('demo');

        $this->assertEquals(['id', 'name'], array_keys($structure['columns']));
        $this->assertArrayHasKey('demo_pkey', $structure['constraints']);
        $this->assertEmpty($structure['indexes']);
    }

    public function testDbUpdaterUsesClassBeforeXml(): void
    {
        $this->registerTestClass();

        $expected = (new BlueprintTestTable())->structure('test_blueprint');
        $this->assertEquals($expected, DbUpdater::getStructure('test_blueprint'));
        $this->assertArrayHasKey('test_blueprint_pkey', $expected['constraints']);
    }

    public function testDbUpdaterFallsBackToXml(): void
    {
        // clientes sigue definida por xml
        $expected = DbUpdater::readTableXml(DbUpdater::getTableXmlLocation('clientes'));
        $this->assertNotEmpty($expected['columns']);
        $this->assertEquals($expected, DbUpdater::getStructure('clientes'));
        $this->assertFileExists(Tools::folder('Core', 'Table', 'clientes.xml'));
    }

    public function testCanCreateAndDropTable(): void
    {
        $table_name = 'test_table';
        $this->assertFalse($this->db()->tableExists($table_name));

        try {
            $table = new Blueprint($table_name);
            $table->id();
            $table->boolean('debaja')->default(false)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('codigo', 10)->unique();
            $table->date('fechaalta')->nullable();
            $table->time('hora')->nullable();
            $table->float('importe')->default(0);
            $table->timestamp('lastactivity')->nullable();
            $table->integer('numero');
            $table->text('observaciones')->nullable();
            $table->index('fechaalta', 'email');

            $this->assertTrue(DbUpdater::createTable($table_name, $table->toArray()));
            $this->assertTrue($this->db()->tableExists($table_name));

            $columns = $this->db()->getColumns($table_name);
            $this->assertCount(10, $columns);
            $this->assertEquals('NO', $columns['numero']['is_nullable']);
            $this->assertEquals('YES', $columns['email']['is_nullable']);
            $this->assertTrue(in_array($columns['debaja']['default'], ['false', '0']));
            $this->assertNotEmpty(array_filter($this->db()->getIndexes($table_name), fn($i) => str_contains($i['name'], 'test_table_fechaalta_email')));
        } finally {
            DbUpdater::dropTable($table_name);
        }
    }

    public function testCanUpdateAndRenameColumns(): void
    {
        $table_name = 'test_table';

        try {
            $table = new Blueprint($table_name);
            $table->id();
            $table->text('observaciones')->nullable();
            $table->string('email', 100)->nullable()->index();
            $this->assertTrue(DbUpdater::createTable($table_name, $table->toArray()));
            $this->assertCount(1, $this->db()->getIndexes($table_name));

            // renombramos una columna, añadimos otra y quitamos el índice
            DbUpdater::rebuild();
            $table = new Blueprint($table_name);
            $table->id();
            $table->text('notas')->nullable()->rename('observaciones');
            $table->string('email', 100)->nullable();
            $table->integer('numero')->default(5);
            $this->assertTrue(DbUpdater::updateTable($table_name, $table->toArray()));

            $columns = $this->db()->getColumns($table_name);
            $this->assertArrayHasKey('notas', $columns);
            $this->assertArrayHasKey('numero', $columns);
            $this->assertArrayNotHasKey('observaciones', $columns);
            $this->assertEmpty($this->db()->getIndexes($table_name));
        } finally {
            DbUpdater::dropTable($table_name);
        }
    }

    public function testCanCreateTableFromClass(): void
    {
        $this->registerTestClass();

        // la tabla se crea desde la clase sin pasar estructura
        $table_name = 'test_blueprint';
        try {
            $this->assertTrue(DbUpdater::createOrUpdateTable($table_name));

            $columns = $this->db()->getColumns($table_name);
            $this->assertEquals(['id', 'nombre', 'web'], array_keys($columns));
            $this->assertEquals('NO', $columns['nombre']['is_nullable']);
            $this->assertEquals('YES', $columns['web']['is_nullable']);
        } finally {
            DbUpdater::dropTable($table_name);
        }
    }

    /** Registra BlueprintTestTable como Dinamic\Table\TestBlueprint, que es donde DbUpdater busca la clase de test_blueprint. */
    private function registerTestClass(): void
    {
        if (false === class_exists('FacturaScripts\Dinamic\Table\TestBlueprint', false)) {
            class_alias(BlueprintTestTable::class, 'FacturaScripts\Dinamic\Table\TestBlueprint');
        }
    }

    private function db(): DataBase
    {
        if (null === $this->db) {
            $this->db = new DataBase();
        }

        return $this->db;
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}

final class BlueprintTestTable extends TableClass
{
    public function define(Blueprint $table): void
    {
        $table->id();
        $table->string('nombre', 50);
        $table->string('web', 100)->nullable();
    }
}
