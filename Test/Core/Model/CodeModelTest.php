<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Model;

use FacturaScripts\Core\Model\CodeModel;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class CodeModelTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    public function testConstructor(): void
    {
        // Test constructor sin datos
        $codeModel = new CodeModel();
        $this->assertEquals('', $codeModel->code);
        $this->assertEquals('', $codeModel->description);

        // Test constructor con datos
        $data = ['code' => 'TEST01', 'description' => 'Test Description'];
        $codeModel = new CodeModel($data);
        $this->assertEquals('TEST01', $codeModel->code);
        $this->assertEquals('Test Description', $codeModel->description);
    }

    public function testArray2CodeModel(): void
    {
        // Test sin opción vacía
        $data = [
            'code1' => 'Description 1',
            'code2' => 'Description 2',
            'code3' => 'Description 3'
        ];

        $result = CodeModel::array2codeModel($data, false);
        $this->assertCount(3, $result);
        $this->assertEquals('code1', $result[0]->code);
        $this->assertEquals('Description 1', $result[0]->description);
        $this->assertEquals('code2', $result[1]->code);
        $this->assertEquals('Description 2', $result[1]->description);

        // Test con opción vacía
        $result = CodeModel::array2codeModel($data, true);
        $this->assertCount(4, $result);
        $this->assertNull($result[0]->code);
        $this->assertEquals('------', $result[0]->description);
        $this->assertEquals('code1', $result[1]->code);
        $this->assertEquals('Description 1', $result[1]->description);
    }

    public function testAllWithTable(): void
    {
        // Crear datos de prueba
        $almacen1 = $this->getRandomWarehouse();
        $this->assertTrue($almacen1->save());

        $almacen2 = $this->getRandomWarehouse();
        $this->assertTrue($almacen2->save());

        // Test all sin opción vacía
        $result = CodeModel::all('almacenes', 'codalmacen', 'nombre', false);
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(2, count($result));

        // Verificar que contiene nuestros almacenes
        $codes = array_map(function ($item) {
            return $item->code;
        }, $result);
        $this->assertContains($almacen1->codalmacen, $codes);
        $this->assertContains($almacen2->codalmacen, $codes);

        // Test all con opción vacía
        $result = CodeModel::all('almacenes', 'codalmacen', 'nombre', true);
        $this->assertIsArray($result);
        $this->assertNull($result[0]->code);
        $this->assertEquals('------', $result[0]->description);

        // Limpiar datos de prueba
        $this->assertTrue($almacen1->delete());
        $this->assertTrue($almacen2->delete());
    }

    public function testAllWithModel(): void
    {
        // Crear datos de prueba
        $variante1 = $this->getRandomVariant();
        $this->assertTrue($variante1->save());

        $variante2 = $this->getRandomVariant();
        $this->assertTrue($variante2->save());

        // Test usando el nombre del modelo
        $result = CodeModel::all('Variante', 'referencia', 'referencia', false);
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(2, count($result));

        // Verificar que contiene nuestras formas de pago
        $codes = array_map(function ($item) {
            return $item->code;
        }, $result);
        $this->assertContains($variante1->referencia, $codes);
        $this->assertContains($variante1->referencia, $codes);

        // Limpiar datos de prueba
        $this->assertTrue($variante1->getProducto()->delete());
        $this->assertTrue($variante2->getProducto()->delete());
    }

    public function testGet(): void
    {
        // Crear dato de prueba
        $almacen = $this->getRandomWarehouse();
        $almacen->codalmacen = 'TEST';
        $this->assertTrue($almacen->save());

        // Test get con tabla
        $codeModel = new CodeModel();
        $result = $codeModel->get('almacenes', 'codalmacen', 'TEST', 'nombre');
        $this->assertEquals($almacen->codalmacen, $result->code);
        $this->assertEquals($almacen->nombre, $result->description);

        // Test get con código inexistente
        $result = $codeModel->get('almacenes', 'codalmacen', 'NOEXISTE', 'nombre');
        $this->assertEquals('', $result->code);
        $this->assertEquals('', $result->description);

        // Limpiar datos de prueba
        $this->assertTrue($almacen->delete());
    }

    public function testGetDescription(): void
    {
        // Crear dato de prueba
        $almacen = $this->getRandomWarehouse();
        $almacen->codalmacen = 'ADES';
        $almacen->nombre = 'Almacén para Descripción';
        $this->assertTrue($almacen->save());

        // Test getDescription con código existente
        $codeModel = new CodeModel();
        $description = $codeModel->getDescription('almacenes', 'codalmacen', 'ADES', 'nombre');
        $this->assertEquals('Almacén para Descripción', $description);

        // Test getDescription con código inexistente
        $description = $codeModel->getDescription('almacenes', 'codalmacen', 'NOEXISTE', 'nombre');
        $this->assertEquals('NOEXISTE', $description);

        // Limpiar datos de prueba
        $this->assertTrue($almacen->delete());
    }

    public function testSearch(): void
    {
        // Crear datos de prueba
        $almacen1 = $this->getRandomWarehouse();
        $almacen1->nombre = 'Almacén Central Madrid';
        $this->assertTrue($almacen1->save());

        $almacen2 = $this->getRandomWarehouse();
        $almacen2->nombre = 'Almacén Norte Barcelona';
        $this->assertTrue($almacen2->save());

        $almacen3 = $this->getRandomWarehouse();
        $almacen3->nombre = 'Almacén Sur Sevilla';
        $this->assertTrue($almacen3->save());

        // Test búsqueda por descripción
        $result = CodeModel::search('almacenes', 'codalmacen', 'nombre', 'Madrid');
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('Almacén Central Madrid', $result[0]->description);

        // Test búsqueda por código
        $result = CodeModel::search('almacenes', 'codalmacen', 'nombre', 'NORTE');
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('Almacén Norte Barcelona', $result[0]->description);

        // Test búsqueda parcial
        $result = CodeModel::search('almacenes', 'codalmacen', 'nombre', 'Almacén');
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(3, count($result));

        // Test búsqueda sin resultados
        $result = CodeModel::search('almacenes', 'codalmacen', 'nombre', 'NoExiste');
        $this->assertIsArray($result);
        $this->assertCount(0, $result);

        // Limpiar datos de prueba
        $this->assertTrue($almacen1->delete());
        $this->assertTrue($almacen2->delete());
        $this->assertTrue($almacen3->delete());
    }

    public function testSetAndGetLimit(): void
    {
        // Test límite por defecto
        $this->assertEquals(1000, CodeModel::getLimit());

        // Test cambiar límite
        CodeModel::setLimit(50);
        $this->assertEquals(50, CodeModel::getLimit());

        // Restaurar límite por defecto
        CodeModel::setLimit(1000);
        $this->assertEquals(1000, CodeModel::getLimit());
    }

    public function testAllWithNonExistentTable(): void
    {
        // Test con tabla inexistente
        $result = CodeModel::all('tabla_no_existe', 'campo1', 'campo2', false);
        $this->assertIsArray($result);
        $this->assertCount(0, $result);

        // Test con tabla inexistente y opción vacía
        $result = CodeModel::all('tabla_no_existe', 'campo1', 'campo2', true);
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertNull($result[0]->code);
        $this->assertEquals('------', $result[0]->description);
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
