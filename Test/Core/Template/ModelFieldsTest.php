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
use FacturaScripts\Core\Cache;
use FacturaScripts\Core\DbUpdater;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

/**
 * Comprueba que un modelo no se queda con una lista de campos obsoleta cuando
 * su tabla gana columnas después de haber cargado los campos (por ejemplo,
 * cuando un plugin amplía una tabla del core).
 */
final class ModelFieldsTest extends TestCase
{
    use LogErrorsTrait;

    const TABLE_NAME = 'test_model_fields';

    public function testFieldsAreNotCachedWhileTableCheckIsSkipped(): void
    {
        // el modelo crea la tabla
        $this->writeTableXml('test_table.xml');
        new TestModelFields();

        // la tabla debe ganar la columna email2, pero empezamos una petición nueva dentro de una transacción
        $this->writeTableXml('test_table_update_1.xml');
        DbUpdater::rebuild();
        TestModelFields::forgetFields();

        $db = new DataBase();
        $db->beginTransaction();
        try {
            // en transacción no se comprueba la tabla, así que los campos no se guardan en caché
            $model = new TestModelFields();
            $this->assertFalse(DbUpdater::isTableChecked(self::TABLE_NAME), 'table-checked-in-transaction');
            $this->assertArrayNotHasKey('email2', $model->getModelFields(), 'email2-found-in-transaction');
            $this->assertNull(Cache::get('model-fields-TestModelFields'), 'fields-cached-in-transaction');
        } finally {
            $db->rollback();
        }

        // fuera de la transacción se actualiza la tabla y se recargan los campos
        $model = new TestModelFields();
        $this->assertTrue(DbUpdater::isTableChecked(self::TABLE_NAME), 'table-not-checked');
        $this->assertArrayHasKey('email2', $model->getModelFields(), 'email2-not-found-after-update');
    }

    public function testFieldsAreReloadedWhenTableIsUpdated(): void
    {
        // el modelo crea la tabla y carga sus campos
        $this->writeTableXml('test_table.xml');
        $model = new TestModelFields();
        $this->assertCount(10, $model->getModelFields(), 'bad-initial-fields');
        $this->assertArrayNotHasKey('email2', $model->getModelFields(), 'email2-found-before-update');

        // la tabla gana la columna email2 y se vuelve a comprobar
        $this->writeTableXml('test_table_update_1.xml');
        DbUpdater::rebuild();
        $model = new TestModelFields();
        $this->assertTrue(DbUpdater::isTableChecked(self::TABLE_NAME), 'table-not-checked');
        $this->assertArrayHasKey('email2', $model->getModelFields(), 'email2-not-found-after-update');
        $this->assertArrayHasKey('email2', Cache::get('model-fields-TestModelFields') ?? [], 'email2-not-cached');

        // el INSERT debe incluir la columna nueva
        $model->email = 'test@test.com';
        $model->email2 = 'test2@test.com';
        $model->numero = 1;
        $this->assertTrue($model->save(), 'cant-save-model');

        $saved = TestModelFields::find($model->id);
        $this->assertNotNull($saved, 'model-not-found');
        $this->assertEquals('test2@test.com', $saved->email2, 'email2-not-saved');
    }

    /** Elimina la tabla de prueba si existe. */
    private function dropTable(): void
    {
        $db = new DataBase();
        if ($db->tableExists(self::TABLE_NAME)) {
            DbUpdater::dropTable(self::TABLE_NAME);
        }
    }

    protected function setUp(): void
    {
        $this->dropTable();
        TestModelFields::forgetFields();
    }

    protected function tearDown(): void
    {
        $this->dropTable();
        TestModelFields::forgetFields();

        $file = Tools::folder('Dinamic', 'Table', self::TABLE_NAME . '.xml');
        if (file_exists($file)) {
            unlink($file);
        }

        $this->logErrors();
    }

    /**
     * Copia a Dinamic/Table la estructura de Test/__files/$source con el nombre de la
     * tabla de prueba, para que el modelo la use al comprobar su tabla.
     */
    private function writeTableXml(string $source): void
    {
        $xml = file_get_contents(Tools::folder('Test', '__files', $source));
        $xml = str_replace('test_table', self::TABLE_NAME, $xml);

        Tools::folderCheckOrCreate(Tools::folder('Dinamic', 'Table'));
        file_put_contents(Tools::folder('Dinamic', 'Table', self::TABLE_NAME . '.xml'), $xml);
    }
}
