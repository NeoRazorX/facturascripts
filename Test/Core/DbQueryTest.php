<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\DbQuery;
use FacturaScripts\Core\Model\LogMessage;
use FacturaScripts\Core\Model\Pais;
use FacturaScripts\Core\Where;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class DbQueryTest extends TestCase
{
    use LogErrorsTrait;

    /** @var DataBase */
    private $db;

    public function testTable(): void
    {
        // si no existe la tabla series, saltamos el test
        if (false === $this->db()->tableExists('series')) {
            $this->markTestSkipped('Table series does not exist.');
        }

        $query = DbQuery::table('series');

        $sql = 'SELECT * FROM ' . $this->db()->escapeColumn('series');
        $this->assertEquals($sql, $query->sql());

        $data = $this->db()->select($sql);
        $this->assertEquals($data, $query->get());
    }

    public function testWhere(): void
    {
        // si no existe la tabla clientes, saltamos el test
        if (false === $this->db()->tableExists('clientes')) {
            $this->markTestSkipped('Table clientes does not exist.');
        }

        $query = DbQuery::table('clientes')
            ->select('codcliente, nombre')
            ->where([
                Where::eq('codcliente', 'test'),
                Where::gt('riesgomax', 1000)
            ]);

        $sql = 'SELECT ' . $this->db()->escapeColumn('codcliente')
            . ', ' . $this->db()->escapeColumn('nombre')
            . ' FROM ' . $this->db()->escapeColumn('clientes')
            . ' WHERE ' . $this->db()->escapeColumn('codcliente') . ' = ' . $this->db()->var2str('test')
            . ' AND ' . $this->db()->escapeColumn('riesgomax') . ' > ' . $this->db()->var2str(1000);
        $this->assertEquals($sql, $query->sql());
    }

    public function testWhereEq(): void
    {
        // si no existe la tabla clientes, saltamos el test
        if (false === $this->db()->tableExists('clientes')) {
            $this->markTestSkipped('Table clientes does not exist.');
        }

        $query = DbQuery::table('clientes')
            ->select('codcliente, nombre')
            ->whereEq('codcliente', 'test');

        $sql = 'SELECT ' . $this->db()->escapeColumn('codcliente')
            . ', ' . $this->db()->escapeColumn('nombre')
            . ' FROM ' . $this->db()->escapeColumn('clientes')
            . ' WHERE ' . $this->db()->escapeColumn('codcliente') . ' = ' . $this->db()->var2str('test');
        $this->assertEquals($sql, $query->sql());
    }

    public function testWhereDynamic(): void
    {
        // si no existe la tabla clientes, saltamos el test
        if (false === $this->db()->tableExists('clientes')) {
            $this->markTestSkipped('Table clientes does not exist.');
        }

        $query = DbQuery::table('clientes')
            ->select('codcliente, nombre')
            ->whereNombre('test');

        $sql = 'SELECT ' . $this->db()->escapeColumn('codcliente')
            . ', ' . $this->db()->escapeColumn('nombre')
            . ' FROM ' . $this->db()->escapeColumn('clientes')
            . ' WHERE ' . $this->db()->escapeColumn('nombre') . ' = ' . $this->db()->var2str('test');
        $this->assertEquals($sql, $query->sql());
    }

    public function testOrderBy(): void
    {
        // si no existe la tabla series, saltamos el test
        if (false === $this->db()->tableExists('series')) {
            $this->markTestSkipped('Table series does not exist.');
        }

        $query = DbQuery::table('series')
            ->select('codserie, descripcion')
            ->orderBy('codserie', 'ASC');

        $sql = 'SELECT ' . $this->db()->escapeColumn('codserie')
            . ', ' . $this->db()->escapeColumn('descripcion')
            . ' FROM ' . $this->db()->escapeColumn('series')
            . ' ORDER BY ' . $this->db()->escapeColumn('codserie') . ' ASC';
        $this->assertEquals($sql, $query->sql());
    }

    public function testOrderByAdvanced(): void
    {
        // si no existe la tabla series, saltamos el test
        if (false === $this->db()->tableExists('series')) {
            $this->markTestSkipped('Table series does not exist.');
        }

        // Test 1: Sanitización de ORDER - DESC en mayúsculas
        $query1 = DbQuery::table('series')
            ->orderBy('codserie', 'DESC');
        $expected1 = 'SELECT * FROM ' . $this->db()->escapeColumn('series')
            . ' ORDER BY ' . $this->db()->escapeColumn('codserie') . ' DESC';
        $this->assertEquals($expected1, $query1->sql());

        // Test 2: Sanitización de ORDER - desc en minúsculas
        $query2 = DbQuery::table('series')
            ->orderBy('codserie', 'desc');
        $expected2 = 'SELECT * FROM ' . $this->db()->escapeColumn('series')
            . ' ORDER BY ' . $this->db()->escapeColumn('codserie') . ' DESC';
        $this->assertEquals($expected2, $query2->sql());

        // Test 3: Sanitización de ORDER - valor inválido debe convertirse a ASC
        $query3 = DbQuery::table('series')
            ->orderBy('codserie', 'INVALID');
        $expected3 = 'SELECT * FROM ' . $this->db()->escapeColumn('series')
            . ' ORDER BY ' . $this->db()->escapeColumn('codserie') . ' ASC';
        $this->assertEquals($expected3, $query3->sql());

        // Test 4: Prefijo lower:
        $query4 = DbQuery::table('series')
            ->orderBy('lower:descripcion');
        $expected4 = 'SELECT * FROM ' . $this->db()->escapeColumn('series')
            . ' ORDER BY LOWER(' . $this->db()->escapeColumn('descripcion') . ') ASC';
        $this->assertEquals($expected4, $query4->sql());

        // Test 5: Prefijo upper:
        $query5 = DbQuery::table('series')
            ->orderBy('upper:descripcion', 'DESC');
        $expected5 = 'SELECT * FROM ' . $this->db()->escapeColumn('series')
            . ' ORDER BY UPPER(' . $this->db()->escapeColumn('descripcion') . ') DESC';
        $this->assertEquals($expected5, $query5->sql());

        // Test 6: Prefijo integer:
        $query6 = DbQuery::table('series')
            ->orderBy('integer:codserie');
        $expected6 = 'SELECT * FROM ' . $this->db()->escapeColumn('series')
            . ' ORDER BY ' . $this->db()->castInteger('codserie') . ' ASC';
        $this->assertEquals($expected6, $query6->sql());

        // Test 7: Expresión permitida LOWER()
        $query7 = DbQuery::table('series')
            ->orderBy('LOWER(descripcion)');
        $expected7 = 'SELECT * FROM ' . $this->db()->escapeColumn('series')
            . ' ORDER BY LOWER(descripcion) ASC';
        $this->assertEquals($expected7, $query7->sql());

        // Test 8: Expresión permitida UPPER()
        $query8 = DbQuery::table('series')
            ->orderBy('UPPER(descripcion)');
        $expected8 = 'SELECT * FROM ' . $this->db()->escapeColumn('series')
            . ' ORDER BY UPPER(descripcion) ASC';
        $this->assertEquals($expected8, $query8->sql());

        // Test 9: Expresión permitida CAST()
        $query9 = DbQuery::table('series')
            ->orderBy('CAST(codserie AS INTEGER)');
        $expected9 = 'SELECT * FROM ' . $this->db()->escapeColumn('series')
            . ' ORDER BY CAST(codserie AS INTEGER) ASC';
        $this->assertEquals($expected9, $query9->sql());

        // Test 10: Múltiples orderBy
        $query10 = DbQuery::table('series')
            ->orderBy('codserie', 'ASC')
            ->orderBy('lower:descripcion', 'DESC');
        $expected10 = 'SELECT * FROM ' . $this->db()->escapeColumn('series')
            . ' ORDER BY ' . $this->db()->escapeColumn('codserie') . ' ASC, '
            . 'LOWER(' . $this->db()->escapeColumn('descripcion') . ') DESC';
        $this->assertEquals($expected10, $query10->sql());

        // Test 11: Intento de inyección SQL con SELECT - NO debe permitirse
        $query11 = DbQuery::table('series')
            ->orderBy('(SELECT password FROM users)');
        $expected11 = 'SELECT * FROM ' . $this->db()->escapeColumn('series')
            . ' ORDER BY ' . $this->db()->escapeColumn('(SELECT password FROM users)') . ' ASC';
        $this->assertEquals($expected11, $query11->sql());

        // Test 12: Otra expresión no permitida con paréntesis
        $query12 = DbQuery::table('series')
            ->orderBy('SUBSTRING(descripcion, 1, 10)');
        $expected12 = 'SELECT * FROM ' . $this->db()->escapeColumn('series')
            . ' ORDER BY ' . $this->db()->escapeColumn('SUBSTRING(descripcion, 1, 10)') . ' ASC';
        $this->assertEquals($expected12, $query12->sql());
    }

    public function testOrderByRandom(): void
    {
        // si no existe la tabla series, saltamos el test
        if (false === $this->db()->tableExists('series')) {
            $this->markTestSkipped('Table series does not exist.');
        }

        // Test 1: orderByRandom() - debe usar la función random del engine
        $query1 = DbQuery::table('series')->orderByRandom();
        $expected1 = 'SELECT * FROM ' . $this->db()->escapeColumn('series')
            . ' ORDER BY ' . $this->db()->random();
        $this->assertEquals($expected1, $query1->sql());

        // Test 2: orderBy('RAND()') - debe convertirse automáticamente
        $query2 = DbQuery::table('series')->orderBy('RAND()');
        $expected2 = 'SELECT * FROM ' . $this->db()->escapeColumn('series')
            . ' ORDER BY ' . $this->db()->random();
        $this->assertEquals($expected2, $query2->sql());

        // Test 3: orderBy('RANDOM()') - debe convertirse automáticamente
        $query3 = DbQuery::table('series')->orderBy('RANDOM()');
        $expected3 = 'SELECT * FROM ' . $this->db()->escapeColumn('series')
            . ' ORDER BY ' . $this->db()->random();
        $this->assertEquals($expected3, $query3->sql());

        // Test 4: orderBy('rand()') en minúsculas - debe convertirse automáticamente
        $query4 = DbQuery::table('series')->orderBy('rand()');
        $expected4 = 'SELECT * FROM ' . $this->db()->escapeColumn('series')
            . ' ORDER BY ' . $this->db()->random();
        $this->assertEquals($expected4, $query4->sql());

        // Test 5: orderBy('random()') en minúsculas - debe convertirse automáticamente
        $query5 = DbQuery::table('series')->orderBy('random()');
        $expected5 = 'SELECT * FROM ' . $this->db()->escapeColumn('series')
            . ' ORDER BY ' . $this->db()->random();
        $this->assertEquals($expected5, $query5->sql());

        // Test 6: orderByRandom() con select y where
        $query6 = DbQuery::table('series')
            ->select('codserie, descripcion')
            ->whereEq('codserie', 'A')
            ->orderByRandom();
        $expected6 = 'SELECT ' . $this->db()->escapeColumn('codserie')
            . ', ' . $this->db()->escapeColumn('descripcion')
            . ' FROM ' . $this->db()->escapeColumn('series')
            . ' WHERE ' . $this->db()->escapeColumn('codserie') . ' = ' . $this->db()->var2str('A')
            . ' ORDER BY ' . $this->db()->random();
        $this->assertEquals($expected6, $query6->sql());

        // Test 7: Combinar ordenamiento aleatorio con otro ordenamiento
        $query7 = DbQuery::table('series')
            ->orderByRandom()
            ->orderBy('codserie', 'ASC');
        $expected7 = 'SELECT * FROM ' . $this->db()->escapeColumn('series')
            . ' ORDER BY ' . $this->db()->random() . ', ' . $this->db()->escapeColumn('codserie') . ' ASC';
        $this->assertEquals($expected7, $query7->sql());
    }

    public function testCount(): void
    {
        // si no existe la tabla países, saltamos el test
        if (false === $this->db()->tableExists('paises')) {
            $this->markTestSkipped('Table paises does not exist.');
        }

        // obtenemos el número de registros en la tabla países
        $count = DbQuery::table('paises')->count();

        // lo comprobamos contra el modelo
        $pais = new Pais();
        $this->assertEquals($count, $pais->count());

        // limpiamos la lista de logs
        $sqlDelete = 'DELETE FROM logs;';
        $this->db()->exec($sqlDelete);

        // añadimos 2 mensajes de log al canal test1
        foreach (range(1, 2) as $i) {
            $logMessage = new LogMessage();
            $logMessage->channel = 'test1';
            $logMessage->level = 'info';
            $logMessage->message = 'test' . $i;
            $this->assertTrue($logMessage->save());
        }

        // añadimos un mensaje de log al canal test2
        $logMessage = new LogMessage();
        $logMessage->channel = 'test2';
        $logMessage->level = 'info';
        $logMessage->message = 'test3';
        $this->assertTrue($logMessage->save());

        // obtenemos el número de canales de log
        $count = DbQuery::table('logs')->count('channel');
        $this->assertEquals(2, $count);

        // comprobamos con selectRaw
        $data = DbQuery::table('logs')->selectRaw('COUNT(DISTINCT channel) as c')->first();
        $this->assertEquals(2, $data['c']);

        // comprobamos con selectRaw + count
        $count = DbQuery::table('logs')->selectRaw('DISTINCT channel')->count();
        $this->assertEquals(2, $count);
    }

    public function testInsert(): void
    {
        // si no existe la tabla de impuestos, saltamos el test
        if (false === $this->db()->tableExists('impuestos')) {
            $this->markTestSkipped('Table impuestos does not exist.');
        }

        // insertamos un impuesto
        $data = ['codimpuesto' => 'test', 'descripcion' => 'test', 'iva' => 29.99, 'recargo' => 0];
        $done = DbQuery::table('impuestos')->insert($data);
        $this->assertTrue($done);

        // comprobamos que se ha insertado
        $row = DbQuery::table('impuestos')
            ->select('codimpuesto, descripcion, iva, recargo')
            ->whereEq('codimpuesto', 'test')
            ->first();
        $this->assertEquals($data, $row);

        // eliminamos el impuesto
        $done = DbQuery::table('impuestos')->whereEq('codimpuesto', 'test')->delete();
        $this->assertTrue($done);
    }

    public function testInsertMulti(): void
    {
        // si no existe la tabla de impuestos, saltamos el test
        if (false === $this->db()->tableExists('impuestos')) {
            $this->markTestSkipped('Table impuestos does not exist.');
        }

        $data = [
            ['codimpuesto' => 'test1', 'descripcion' => 'test1', 'iva' => 29.99, 'recargo' => 0],
            ['codimpuesto' => 'test2', 'descripcion' => 'test2', 'iva' => 11.5, 'recargo' => 2.3],
            ['codimpuesto' => 'test3', 'descripcion' => 'test3', 'iva' => 3.76, 'recargo' => 0.5]
        ];

        // insertamos 3 impuestos
        $done = DbQuery::table('impuestos')->insert($data);
        $this->assertTrue($done);

        // comprobamos que se han insertado
        $row1 = DbQuery::table('impuestos')
            ->select('codimpuesto, descripcion, iva, recargo')
            ->whereEq('codimpuesto', 'test1')
            ->first();
        $this->assertEquals($data[0], $row1);

        $row2 = DbQuery::table('impuestos')
            ->select('codimpuesto, descripcion, iva, recargo')
            ->whereEq('codimpuesto', 'test2')
            ->first();
        $this->assertEquals($data[1], $row2);

        $row3 = DbQuery::table('impuestos')
            ->select('codimpuesto, descripcion, iva, recargo')
            ->whereEq('codimpuesto', 'test3')
            ->first();
        $this->assertEquals($data[2], $row3);

        // calculamos la media del recargo
        $avg = DbQuery::table('impuestos')
            ->whereIn('codimpuesto', ['test1', 'test2', 'test3'])
            ->avg('recargo', 3);
        $this->assertEquals(0.933, $avg);

        // calculamos el máximo del iva
        $max = DbQuery::table('impuestos')
            ->whereIn('codimpuesto', ['test1', 'test2', 'test3'])
            ->max('iva');
        $this->assertEquals(29.99, $max);

        // calculamos el mínimo del iva
        $min = DbQuery::table('impuestos')
            ->whereIn('codimpuesto', ['test1', 'test2', 'test3'])
            ->min('iva');
        $this->assertEquals(3.76, $min);

        // calculamos la suma del iva
        $sum = DbQuery::table('impuestos')
            ->whereIn('codimpuesto', ['test1', 'test2', 'test3'])
            ->sum('iva', 2);
        $this->assertEquals(45.25, $sum);

        // eliminamos los impuestos
        $done = DbQuery::table('impuestos')
            ->whereIn('codimpuesto', ['test1', 'test2', 'test3'])
            ->delete();
        $this->assertTrue($done);
    }

    public function testMaxMinString(): void
    {
        // si no existe la tabla de impuestos, saltamos el test
        if (false === $this->db()->tableExists('impuestos')) {
            $this->markTestSkipped('Table impuestos does not exist.');
        }

        $data = [
            ['codimpuesto' => 'test1', 'descripcion' => 'test1', 'iva' => 29.99, 'recargo' => 0],
            ['codimpuesto' => 'test2', 'descripcion' => 'test2', 'iva' => 11.5, 'recargo' => 2.3],
            ['codimpuesto' => 'test3', 'descripcion' => 'test3', 'iva' => 3.76, 'recargo' => 0.5]
        ];

        // insertamos 3 impuestos
        $done = DbQuery::table('impuestos')->insert($data);
        $this->assertTrue($done);

        $maxString = DbQuery::table('impuestos')
            ->whereIn('codimpuesto', ['test1', 'test2', 'test3'])
            ->maxString('codimpuesto');
        $this->assertEquals('test3', $maxString);

        $minString = DbQuery::table('impuestos')
            ->whereIn('codimpuesto', ['test1', 'test2', 'test3'])
            ->minString('codimpuesto');
        $this->assertEquals('test1', $minString);

        // eliminamos los impuestos
        $done = DbQuery::table('impuestos')
            ->whereIn('codimpuesto', ['test1', 'test2', 'test3'])
            ->delete();
        $this->assertTrue($done);
    }

    public function testDelete(): void
    {
        // si no existe la tabla de impuestos, saltamos el test
        if (false === $this->db()->tableExists('impuestos')) {
            $this->markTestSkipped('Table impuestos does not exist.');
        }

        // insertamos un impuesto
        $data = ['codimpuesto' => 'test', 'descripcion' => 'test', 'iva' => 29.99, 'recargo' => 0];
        $done = DbQuery::table('impuestos')->insert($data);
        $this->assertTrue($done);

        // eliminamos el impuesto
        $done = DbQuery::table('impuestos')->whereEq('codimpuesto', 'test')->delete();
        $this->assertTrue($done);

        // comprobamos que se ha eliminado
        $row = DbQuery::table('impuestos')
            ->select('codimpuesto, descripcion, iva, recargo')
            ->whereEq('codimpuesto', 'test')
            ->first();
        $this->assertEmpty($row);
    }

    public function testUpdate(): void
    {
        // si no existe la tabla de impuestos, saltamos el test
        if (false === $this->db()->tableExists('impuestos')) {
            $this->markTestSkipped('Table impuestos does not exist.');
        }

        $data = [
            ['codimpuesto' => 'test1', 'descripcion' => 'test1', 'iva' => 29.99, 'recargo' => 0],
            ['codimpuesto' => 'test2', 'descripcion' => 'test2', 'iva' => 11.5, 'recargo' => 2.3],
            ['codimpuesto' => 'test3', 'descripcion' => 'test3', 'iva' => 3.76, 'recargo' => 0.5]
        ];

        // insertamos 3 impuestos
        $done = DbQuery::table('impuestos')->insert($data);
        $this->assertTrue($done);

        // actualizamos el recargo de test1
        $done = DbQuery::table('impuestos')
            ->whereEq('codimpuesto', 'test1')
            ->update(['recargo' => 1.5]);
        $this->assertTrue($done);

        // comprobamos que se ha actualizado
        $row1 = DbQuery::table('impuestos')
            ->select('codimpuesto, descripcion, iva, recargo')
            ->whereEq('codimpuesto', 'test1')
            ->first();
        $this->assertEquals(1.5, $row1['recargo']);

        // comprobamos que no se ha actualizado test2
        $row2 = DbQuery::table('impuestos')
            ->select('codimpuesto, descripcion, iva, recargo')
            ->whereEq('codimpuesto', 'test2')
            ->first();
        $this->assertEquals(2.3, $row2['recargo']);

        // eliminamos los impuestos
        $done = DbQuery::table('impuestos')
            ->whereIn('codimpuesto', ['test1', 'test2', 'test3'])
            ->delete();
        $this->assertTrue($done);
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
