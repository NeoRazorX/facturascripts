<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2025-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\Stock;
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

    public function testFieldNameValidation(): void
    {
        // Crear almacenes de prueba con códigos válidos (1-4 caracteres) en mayúsculas y nombres con mayúsculas/minúsculas
        $almacen1 = $this->getRandomWarehouse();
        $almacen1->codalmacen = 'TST1';
        $almacen1->nombre = 'Almacen Principal';
        $this->assertTrue($almacen1->save());

        $almacen2 = $this->getRandomWarehouse();
        $almacen2->codalmacen = 'TST2';
        $almacen2->nombre = 'Almacen Secundario';
        $this->assertTrue($almacen2->save());

        // Test con campos normales - el código debe estar en mayúsculas como se guardó
        $result = CodeModel::all('almacenes', 'codalmacen', 'nombre', false);
        $this->assertIsArray($result);
        $found = false;
        foreach ($result as $item) {
            if ($item->code === 'TST1') {
                $this->assertEquals('Almacen Principal', $item->description);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'No se encontró el almacén TST1');

        // Test con lower() en fieldCode - el código debe estar en minúsculas
        $result = CodeModel::all('almacenes', 'lower(codalmacen)', 'nombre', false);
        $this->assertIsArray($result);
        $found = false;
        foreach ($result as $item) {
            if ($item->code === 'tst1') {
                $this->assertEquals('Almacen Principal', $item->description);
                // Verificar que NO está en mayúsculas
                $this->assertNotEquals('TST1', $item->code);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'No se encontró el código en minúsculas tst1');

        // Test con upper() en fieldCode - el código debe estar en mayúsculas
        $result = CodeModel::all('almacenes', 'upper(codalmacen)', 'nombre', false);
        $this->assertIsArray($result);
        $found = false;
        foreach ($result as $item) {
            if ($item->code === 'TST1') {
                $this->assertEquals('Almacen Principal', $item->description);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'No se encontró el código en mayúsculas TST1');

        // Test con LOWER() en mayúsculas (case-insensitive) - debe funcionar igual
        $result = CodeModel::all('almacenes', 'LOWER(codalmacen)', 'nombre', false);
        $this->assertIsArray($result);
        $found = false;
        foreach ($result as $item) {
            if ($item->code === 'tst2') {
                $this->assertEquals('Almacen Secundario', $item->description);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'LOWER() no funcionó correctamente');

        // Test con lower() en fieldDescription - la descripción debe estar en minúsculas
        $result = CodeModel::all('almacenes', 'codalmacen', 'lower(nombre)', false);
        $this->assertIsArray($result);
        $found = false;
        foreach ($result as $item) {
            if ($item->code === 'TST1') {
                $this->assertEquals('almacen principal', $item->description);
                // Verificar que NO tiene mayúsculas
                $this->assertNotEquals('Almacen Principal', $item->description);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'No se encontró la descripción en minúsculas');

        // Test con upper() en fieldDescription - la descripción debe estar en mayúsculas
        $result = CodeModel::all('almacenes', 'codalmacen', 'upper(nombre)', false);
        $this->assertIsArray($result);
        $found = false;
        foreach ($result as $item) {
            if ($item->code === 'TST2') {
                $this->assertEquals('ALMACEN SECUNDARIO', $item->description);
                // Verificar que NO tiene minúsculas
                $this->assertNotEquals('Almacen Secundario', $item->description);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'No se encontró la descripción en mayúsculas');

        // Test con lower() en ambos campos
        $result = CodeModel::all('almacenes', 'lower(codalmacen)', 'lower(nombre)', false);
        $this->assertIsArray($result);
        $found = false;
        foreach ($result as $item) {
            if ($item->code === 'tst1') {
                $this->assertEquals('almacen principal', $item->description);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'No funcionó lower() en ambos campos');

        // Test con upper() en ambos campos
        $result = CodeModel::all('almacenes', 'upper(codalmacen)', 'upper(nombre)', false);
        $this->assertIsArray($result);
        $found = false;
        foreach ($result as $item) {
            if ($item->code === 'TST2') {
                $this->assertEquals('ALMACEN SECUNDARIO', $item->description);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'No funcionó upper() en ambos campos');

        // Test con campo de tabla con punto
        $result = CodeModel::all('almacenes', 'almacenes.codalmacen', 'almacenes.nombre', false);
        $this->assertIsArray($result);
        $found = false;
        foreach ($result as $item) {
            if ($item->code === 'TST1') {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'No funcionó con tabla.campo');

        // Test con lower() y tabla.campo
        $result = CodeModel::all('almacenes', 'lower(almacenes.codalmacen)', 'nombre', false);
        $this->assertIsArray($result);
        $found = false;
        foreach ($result as $item) {
            if ($item->code === 'tst1') {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'No funcionó lower() con tabla.campo');

        // Test con función permitida concat()
        $result = CodeModel::all('almacenes', 'concat(codalmacen, nombre)', 'nombre', false);
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Test con función permitida substring()
        $result = CodeModel::all('almacenes', 'codalmacen', 'substring(nombre, 1, 10)', false);
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Test con intento de SQL injection - debe fallar
        $result = CodeModel::all('almacenes', 'codalmacen; DROP TABLE almacenes--', 'nombre', true);
        $this->assertIsArray($result);
        $this->assertCount(1, $result); // Solo debe retornar el elemento vacío
        $this->assertNull($result[0]->code);

        // Test con caracteres especiales no permitidos - debe fallar
        $result = CodeModel::all('almacenes', 'codalmacen OR 1=1', 'nombre', true);
        $this->assertIsArray($result);
        $this->assertCount(1, $result); // Solo debe retornar el elemento vacío
        $this->assertNull($result[0]->code);

        // Limpiar datos de prueba
        $this->assertTrue($almacen1->delete());
        $this->assertTrue($almacen2->delete());
    }

    public function testGetWithEmptyTableName(): void
    {
        $codeModel = new CodeModel();
        $result = $codeModel->get('', 'codalmacen', 'X', 'nombre');
        $this->assertEquals('', $result->code);
        $this->assertEquals('', $result->description);
    }

    public function testGetWithEmptyFieldCode(): void
    {
        // En el branch de tabla, fieldCode vacío no puede construir WHERE válido
        $codeModel = new CodeModel();
        $result = $codeModel->get('almacenes', '', 'X', 'nombre');
        $this->assertEquals('', $result->code);
        $this->assertEquals('', $result->description);
    }

    public function testGetWithInvalidTableName(): void
    {
        // Nombre de tabla con caracteres inválidos: debe rechazarse
        $codeModel = new CodeModel();
        $result = $codeModel->get('almacenes; DROP TABLE x--', 'codalmacen', 'X', 'nombre');
        $this->assertEquals('', $result->code);
        $this->assertEquals('', $result->description);
    }

    public function testGetWithInvalidFieldName(): void
    {
        $codeModel = new CodeModel();
        $result = $codeModel->get('almacenes', 'codalmacen OR 1=1', 'X', 'nombre');
        $this->assertEquals('', $result->code);
        $this->assertEquals('', $result->description);
    }

    public function testAllWithJoinModelName(): void
    {
        // Pasar 'Join\StockProducto' debe entrar en el branch de modelo y, al
        // ser instancia de JoinModel, delegar en su propio ::all() en lugar de
        // caer al fallback SQL (que trataría 'Join\StockProducto' como tabla).
        // Se prefija la tabla en 'referencia' porque también existe en
        // 'variantes' y el JOIN haría la columna ambigua sin el prefijo.
        $result = CodeModel::all('Join\\StockProducto', 'stocks.referencia', 'descripcion', false);
        $this->assertIsArray($result);
    }

    public function testGetWithJoinModel(): void
    {
        $product = $this->getRandomProduct();
        $this->assertTrue($product->save());

        $warehouse = $this->getRandomWarehouse();
        $this->assertTrue($warehouse->save());

        $stock = new Stock();
        $stock->idproducto = $product->idproducto;
        $stock->referencia = $product->referencia;
        $stock->codalmacen = $warehouse->codalmacen;
        $stock->cantidad = 5;
        $this->assertTrue($stock->save());

        $codeModel = new CodeModel();
        $result = $codeModel->get('Join\\StockProducto', 'stocks.referencia', $product->referencia, 'descripcion');
        $this->assertEquals($product->referencia, $result->code);
        $this->assertEquals($product->descripcion, $result->description);

        // sin coincidencia devuelve CodeModel vacío
        $missing = $codeModel->get('Join\\StockProducto', 'stocks.referencia', 'no-existe-xyz', 'descripcion');
        $this->assertEquals('', $missing->code);
        $this->assertEquals('', $missing->description);

        // sin fieldCode no se puede consultar
        $empty = $codeModel->get('Join\\StockProducto', '', $product->referencia, 'descripcion');
        $this->assertEquals('', $empty->code);

        $this->assertTrue($stock->delete());
        $this->assertTrue($product->delete());
        $this->assertTrue($warehouse->delete());
    }

    public function testAllWithJoinModel(): void
    {
        $product = $this->getRandomProduct();
        $this->assertTrue($product->save());

        $warehouse = $this->getRandomWarehouse();
        $this->assertTrue($warehouse->save());

        $stock = new Stock();
        $stock->idproducto = $product->idproducto;
        $stock->referencia = $product->referencia;
        $stock->codalmacen = $warehouse->codalmacen;
        $stock->cantidad = 3;
        $this->assertTrue($stock->save());

        $result = CodeModel::all('Join\\StockProducto', 'stocks.referencia', 'descripcion', false);
        $this->assertIsArray($result);

        $codes = array_map(fn($r) => $r->code, $result);
        $this->assertContains($product->referencia, $codes);

        foreach ($result as $row) {
            if ($row->code === $product->referencia) {
                $this->assertEquals($product->descripcion, $row->description);
            }
        }

        $this->assertTrue($stock->delete());
        $this->assertTrue($product->delete());
        $this->assertTrue($warehouse->delete());
    }

    public function testSearchWithJoinModel(): void
    {
        $product = $this->getRandomProduct();
        $this->assertTrue($product->save());

        $warehouse = $this->getRandomWarehouse();
        $this->assertTrue($warehouse->save());

        $stock = new Stock();
        $stock->idproducto = $product->idproducto;
        $stock->referencia = $product->referencia;
        $stock->codalmacen = $warehouse->codalmacen;
        $stock->cantidad = 7;
        $this->assertTrue($stock->save());

        // búsqueda por descripción debe encontrar el registro
        $result = CodeModel::search('Join\\StockProducto', 'stocks.referencia', 'descripcion', $product->descripcion);
        $this->assertIsArray($result);
        $codes = array_map(fn($r) => $r->code, $result);
        $this->assertContains($product->referencia, $codes);

        $this->assertTrue($stock->delete());
        $this->assertTrue($product->delete());
        $this->assertTrue($warehouse->delete());
    }

    public function testAllWithInvalidTableNameContainingBackslash(): void
    {
        // Un tableName con barra invertida que no corresponde a un modelo real
        // debe ser rechazado por isValidTableName y devolver el resultado vacío.
        $result = CodeModel::all('Join\\NoExiste', 'campo1', 'campo2', true);
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertNull($result[0]->code);
        $this->assertEquals('------', $result[0]->description);
    }

    public function testSearchWithInvalidTableNameContainingBackslash(): void
    {
        $result = CodeModel::search('Join\\NoExiste', 'codigo', 'nombre', 'foo');
        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function testModelBaseNameReflection(): void
    {
        // Verifica que el helper protegido modelBaseName devuelve el último segmento
        $method = new \ReflectionMethod(CodeModel::class, 'modelBaseName');
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }
        $this->assertEquals('PartidaAsiento', $method->invoke(null, 'Join\\PartidaAsiento'));
        $this->assertEquals('Variante', $method->invoke(null, 'Variante'));
        $this->assertEquals('C', $method->invoke(null, 'A\\B\\C'));
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
