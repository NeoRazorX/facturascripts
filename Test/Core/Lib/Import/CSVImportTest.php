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

namespace FacturaScripts\Test\Core\Lib\Import;

use FacturaScripts\Core\Lib\Import\CSVImport;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\EmailNotification;
use PHPUnit\Framework\TestCase;

final class CSVImportTest extends TestCase
{
    public function testGetTableFilePath(): void
    {
        // probamos con una tabla que existe
        $filePath = CSVImport::getTableFilePath(EmailNotification::tableName());
        $this->assertNotEmpty($filePath, 'El archivo CSV de notificaciones debería existir');
        $this->assertFileExists($filePath, 'El archivo CSV debería existir físicamente');

        // verificamos que el archivo está en la ruta correcta según el idioma
        $lang = strtoupper(substr(Tools::config('lang'), 0, 2));
        $expectedPath = FS_FOLDER . '/Dinamic/Data/Lang/' . $lang . '/emails_notifications.csv';
        $fallbackPath = FS_FOLDER . '/Dinamic/Data/Lang/ES/emails_notifications.csv';

        $this->assertTrue(
            $filePath === $expectedPath || $filePath === $fallbackPath,
            'El archivo debería estar en la ruta del idioma configurado o en ES como fallback'
        );
    }

    public function testGetTableFilePathWithCountry(): void
    {
        // guardamos el valor actual
        $originalCodpais = Tools::settings('default', 'codpais');

        // probamos con un país específico
        Tools::settingsSet('default', 'codpais', 'ESP');

        $filePath = CSVImport::getTableFilePath(EmailNotification::tableName());
        $this->assertNotEmpty($filePath);

        // restauramos el valor original
        Tools::settingsSet('default', 'codpais', $originalCodpais);
    }

    public function testGetTableFilePathReturnsEmptyForSettings(): void
    {
        // el método debe retornar vacío para la tabla settings
        $filePath = CSVImport::getTableFilePath('settings');
        $this->assertEmpty($filePath, 'No debería retornar archivo para la tabla settings');
    }

    public function testGetTableFilePathReturnsEmptyForNonExistent(): void
    {
        // el método debe retornar vacío para una tabla que no existe
        $filePath = CSVImport::getTableFilePath('tabla_que_no_existe_xyz123');
        $this->assertEmpty($filePath, 'No debería retornar archivo para una tabla inexistente');
    }

    public function testImportTableSQL(): void
    {
        // probamos generar SQL de importación
        $sql = CSVImport::importTableSQL(EmailNotification::tableName());

        // verificamos que se generó SQL
        $this->assertNotEmpty($sql, 'Debería generar SQL de importación');

        // verificamos que es un INSERT
        $this->assertStringContainsString('INSERT INTO', strtoupper($sql), 'Debería contener INSERT INTO');

        // verificamos que NO contiene ON DUPLICATE KEY UPDATE (MySQL)
        $this->assertStringNotContainsString('ON DUPLICATE KEY UPDATE', strtoupper($sql), 'No debería contener ON DUPLICATE KEY UPDATE');

        // verificamos que NO contiene ON CONFLICT (PostgreSQL)
        $this->assertStringNotContainsString('ON CONFLICT', strtoupper($sql), 'No debería contener ON CONFLICT');
    }

    public function testUpdateTableSQL(): void
    {
        // probamos generar SQL de actualización
        $sql = CSVImport::updateTableSQL(EmailNotification::tableName());

        // verificamos que se generó SQL
        $this->assertNotEmpty($sql, 'Debería generar SQL de actualización');

        // verificamos que es un INSERT
        $this->assertStringContainsString('INSERT INTO', strtoupper($sql), 'Debería contener INSERT INTO');

        // verificamos que contiene ON DUPLICATE KEY UPDATE (MySQL) o ON CONFLICT (PostgreSQL)
        $dbType = Tools::config('db_type');
        if ($dbType === 'mysql') {
            $this->assertStringContainsString('ON DUPLICATE KEY UPDATE', strtoupper($sql), 'Debería contener ON DUPLICATE KEY UPDATE para MySQL');
        } elseif ($dbType === 'postgresql') {
            $this->assertStringContainsString('ON CONFLICT', strtoupper($sql), 'Debería contener ON CONFLICT para PostgreSQL');
        }
    }

    public function testImportTableSQLStructure(): void
    {
        // probamos que el SQL generado tiene la estructura correcta
        $sql = CSVImport::importTableSQL(EmailNotification::tableName());

        // verificamos que contiene los campos de EmailNotification
        $this->assertStringContainsString('name', $sql, 'Debería contener el campo name');
        $this->assertStringContainsString('enabled', $sql, 'Debería contener el campo enabled');
        $this->assertStringContainsString('subject', $sql, 'Debería contener el campo subject');
        $this->assertStringContainsString('body', $sql, 'Debería contener el campo body');

        // verificamos que contiene VALUES
        $this->assertStringContainsString('VALUES', strtoupper($sql), 'Debería contener VALUES');

        // verificamos que termina con punto y coma
        $this->assertStringEndsWith(';', trim($sql), 'Debería terminar con punto y coma');
    }

    public function testImportFileSQL(): void
    {
        // obtenemos la ruta del archivo CSV
        $filePath = CSVImport::getTableFilePath(EmailNotification::tableName());
        $this->assertNotEmpty($filePath);

        // generamos SQL directamente desde el archivo
        $sql = CSVImport::importFileSQL(EmailNotification::tableName(), $filePath);

        // verificamos que se generó SQL
        $this->assertNotEmpty($sql, 'Debería generar SQL desde archivo');

        // verificamos la estructura básica
        $this->assertStringContainsString('INSERT INTO', strtoupper($sql), 'Debería contener INSERT INTO');
        $this->assertStringContainsString('VALUES', strtoupper($sql), 'Debería contener VALUES');
    }

    public function testImportFileSQLWithUpdate(): void
    {
        // obtenemos la ruta del archivo CSV
        $filePath = CSVImport::getTableFilePath(EmailNotification::tableName());
        $this->assertNotEmpty($filePath);

        // generamos SQL con modo update
        $sql = CSVImport::importFileSQL(EmailNotification::tableName(), $filePath, true);

        // verificamos que se generó SQL
        $this->assertNotEmpty($sql, 'Debería generar SQL desde archivo con update');

        // verificamos que contiene ON DUPLICATE KEY UPDATE o ON CONFLICT según el tipo de base de datos
        $dbType = Tools::config('db_type');
        if ($dbType === 'mysql') {
            $this->assertStringContainsString('ON DUPLICATE KEY UPDATE', strtoupper($sql));
        } elseif ($dbType === 'postgresql') {
            $this->assertStringContainsString('ON CONFLICT', strtoupper($sql));
        }
    }
}
