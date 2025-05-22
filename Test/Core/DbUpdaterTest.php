<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class DbUpdaterTest extends TestCase
{
    use LogErrorsTrait;

    /** @var DataBase */
    private $db;

    public function testTableXmlFileCanBeRead(): void
    {
        $filePath = Tools::folder('Test', '__files', 'test_table.xml');
        $structure = DbUpdater::readTableXml($filePath);
        $this->assertNotEmpty($structure, 'empty-table-structure');
        $this->assertArrayHasKey('columns', $structure, 'columns-structure-not-found');
        $this->assertCount(10, $structure['columns'], 'missing-columns');

        // check first column
        $this->assertArrayHasKey('debaja', $structure['columns'], 'column-debaja-not-found');
        $this->assertEquals('boolean', $structure['columns']['debaja']['type'], 'bad-column-debaja-type');
        $this->assertEquals('YES', $structure['columns']['debaja']['null'], 'bad-column-debaja-null');
        $this->assertEquals('false', $structure['columns']['debaja']['default'], 'bad-column-debaja-default');

        // check other columns
        $this->assertEquals('NO', $structure['columns']['importe']['null'], 'bad-column-importe-null');
        $this->assertEquals('', $structure['columns']['email']['default'], 'bad-column-email-default');

        // check constraints
        $this->assertArrayHasKey('constraints', $structure, 'constraints-structure-not-found');
        $this->assertCount(1, $structure['constraints'], 'missing-constraints');
        $this->assertArrayHasKey('test_table_pkey', $structure['constraints'], 'first-constraint-not-found');
    }

    public function testCanCreateAndDropTable(): void
    {
        $tableName = 'test_table';
        $found = $this->db()->tableExists($tableName);
        $this->assertFalse($found, 'test-table-found-before-create');

        $filePath = Tools::folder('Test', '__files', $tableName . '.xml');
        $structure = DbUpdater::readTableXml($filePath);
        $created = DbUpdater::createTable($tableName, $structure);
        $this->assertTrue($created, 'test-table-not-created');

        $exists = $this->db()->tableExists($tableName);
        $this->assertTrue($exists, 'test-table-not-exists');

        $columns = $this->db()->getColumns($tableName);
        $this->assertNotEmpty($columns, 'test-table-empty-columns');
        $this->assertArrayHasKey('debaja', $columns, 'column-debaja-not-found');
        $this->assertTrue(in_array($columns['debaja']['type'], ['boolean', 'tinyint(1)']), 'column-debaja-bad-type');
        $this->assertEquals('YES', $columns['debaja']['is_nullable'], 'column-debaja-bad-nullable');
        $this->assertTrue(in_array($columns['debaja']['default'], ['false', '0']), 'column-debaja-bad-default');

        $this->assertArrayHasKey('email', $columns, 'column-email-not-found');
        $this->assertArrayHasKey('fechaalta', $columns, 'column-fechaalta-not-found');
        $this->assertArrayHasKey('fechabaja', $columns, 'column-fechabaja-not-found');
        $this->assertArrayHasKey('hora', $columns, 'column-hora-not-found');
        $this->assertArrayHasKey('id', $columns, 'column-id-not-found');
        $this->assertArrayHasKey('importe', $columns, 'column-importe-not-found');
        $this->assertArrayHasKey('lastactivity', $columns, 'column-lastactivity-not-found');
        $this->assertArrayHasKey('numero', $columns, 'column-numero-not-found');
        $this->assertArrayHasKey('observaciones', $columns, 'column-observaciones-not-found');

        $dropped = DbUpdater::dropTable($tableName);
        $this->assertTrue($dropped, 'test-table-not-dropped');
    }

    public function testCanAddColumnsAndConstraintsToTable(): void
    {
        // create
        $tableName = 'test_table';
        $filePath = Tools::folder('Test', '__files', $tableName . '.xml');
        $structure = DbUpdater::readTableXml($filePath);
        $created = DbUpdater::createTable($tableName, $structure);
        $this->assertTrue($created, 'test-table-not-created');

        // update
        DbUpdater::rebuild();
        $newFilePath = Tools::folder('Test', '__files', $tableName . '_update_1.xml');
        $newStructure = DbUpdater::readTableXml($newFilePath);
        $updated = DbUpdater::updateTable($tableName, $newStructure);
        $this->assertTrue($updated, 'test-table-not-updated');

        // check columns
        $columns = $this->db()->getColumns($tableName);
        $this->assertNotEmpty($columns, 'empty-columns');
        $this->assertCount(11, $columns, 'missing-columns');
        $this->assertArrayHasKey('email2', $columns, 'column-email2-not-found');
        $this->assertTrue(in_array($columns['email2']['type'], ['varchar(130)', 'character varying(130)']), 'column-email2-bad-type');
        $this->assertEquals('NO', $columns['email2']['is_nullable'], 'column-email2-bad-nullable');
        $this->assertNull($columns['email2']['default'], 'column-email2-bad-default');

        // check constraints
        $constraints = $this->db()->getConstraints($tableName);
        $this->assertNotEmpty($constraints, 'empty-constraints');
        $this->assertCount(2, $constraints, 'missing-constraints');

        $dropped = DbUpdater::dropTable($tableName);
        $this->assertTrue($dropped, 'test-table-not-dropped');
    }

    public function testCanUpdateTableColumnNullAndDefault(): void
    {
        // create
        $tableName = 'test_table';
        $filePath = Tools::folder('Test', '__files', $tableName . '.xml');
        $structure = DbUpdater::readTableXml($filePath);
        $created = DbUpdater::createTable($tableName, $structure);
        $this->assertTrue($created, 'test-table-not-created');

        // update
        DbUpdater::rebuild();
        $newFilePath = Tools::folder('Test', '__files', $tableName . '_update_2.xml');
        $newStructure = DbUpdater::readTableXml($newFilePath);
        $updated = DbUpdater::updateTable($tableName, $newStructure);
        $this->assertTrue($updated, 'test-table-not-updated');

        // check columns
        $columns = $this->db()->getColumns($tableName);
        $this->assertNotEmpty($columns, 'empty-columns');
        $this->assertCount(10, $columns, 'missing-columns');

        // hora column
        $this->assertArrayHasKey('hora', $columns, 'column-hora-not-found');
        $this->assertEquals(0, strpos($columns['hora']['type'], 'time'), 'column-hora-bad-type');
        $this->assertEquals('NO', $columns['hora']['is_nullable'], 'column-hora-bad-nullable');
        $this->assertNull($columns['hora']['default'], 'column-hora-bad-default');

        // importe column
        $this->assertArrayHasKey('importe', $columns, 'column-importe-not-found');
        $this->assertEquals(0, strpos($columns['importe']['type'], 'double'), 'column-importe-bad-type');
        $this->assertEquals('YES', $columns['importe']['is_nullable'], 'column-importe-bad-nullable');

        // numero column
        $this->assertArrayHasKey('numero', $columns, 'column-numero-not-found');
        $this->assertEquals(0, strpos($columns['numero']['type'], 'int'), 'column-numero-bad-type');
        $this->assertEquals('NO', $columns['numero']['is_nullable'], 'column-numero-bad-nullable');
        $this->assertEquals('7', $columns['numero']['default'], 'column-numero-bad-default');

        $dropped = DbUpdater::dropTable($tableName);
        $this->assertTrue($dropped, 'test-table-not-dropped');
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
