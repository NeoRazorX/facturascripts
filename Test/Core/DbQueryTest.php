<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\LogMessage;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Familia;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\Impuesto;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Pais;
use FacturaScripts\Dinamic\Model\Serie;
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

    public function testJoinProductsAndFamilies(): void
    {
        // si no existe la tabla productos o familias, saltamos el test
        if (false === $this->db()->tableExists('productos') || false === $this->db()->tableExists('familias')) {
            $this->markTestSkipped('Table productos or familias does not exist.');
        }

        // Limpiamos los datos de prueba de ejecuciones anteriores
        DbQuery::table('productos')->whereEq('referencia', 'ProJNTest')->delete();
        DbQuery::table('familias')->whereEq('codfamilia', 'FaJNTest')->delete();

        // Insertar datos de prueba usando objetos Familia y Producto
        $familia = new Familia();
        $familia->codfamilia = 'FaJNTest';
        $familia->descripcion = 'Familia para Join Test';
        $this->assertTrue($familia->save());

        $producto = new Producto();
        $producto->referencia = 'ProJNTest';
        $producto->descripcion = 'Producto para Join Test';
        $producto->codfamilia = 'FaJNTest';
        $producto->precio = 100.00;
        $this->assertTrue($producto->save());

        // Construir la consulta con JOIN
        $query = DbQuery::table('productos')
            ->select('productos.referencia, productos.descripcion as productoDesc, familias.descripcion AS familiaDesc')
            ->join('familias', 'codfamilia', 'codfamilia')
            ->where([Where::like('productos.referencia', 'ProJN%')]);

        // Verificar la consulta SQL generada
        $expectedSql = 'SELECT ' . $this->db()->escapeColumn('productos.referencia')
            . ', ' . $this->db()->escapeColumn('productos.descripcion') . ' AS ' . $this->db()->escapeColumn('productoDesc')
            . ', ' . $this->db()->escapeColumn('familias.descripcion') . ' AS ' . $this->db()->escapeColumn('familiaDesc')
            . ' FROM ' . $this->db()->escapeColumn('productos')
            . ' JOIN ' . $this->db()->escapeColumn('familias') . ' ON ' . $this->db()->escapeColumn('productos.codfamilia') . ' = ' . $this->db()->escapeColumn('familias.codfamilia')
            . ' WHERE ' . Where::like('productos.referencia', 'ProJN%')->sql();

        $this->assertEquals($expectedSql, $query->sql());

        // Ejecutar la consulta y verificar los resultados
        $results = $query->get();
        $this->assertCount(1, $results);

        // Verificar el resultado
        $this->assertEquals('ProJNTest', $results[0]['referencia']);
        $this->assertEquals('Producto para Join Test', $results[0]['productoDesc']);
        $this->assertEquals('Familia para Join Test', $results[0]['familiaDesc']);

        // Limpiar los datos de prueba
        DbQuery::table('productos')->whereEq('referencia', 'ProJNTest')->delete();
        DbQuery::table('familias')->whereEq('codfamilia', 'FaJNTest')->delete();
    }

    public function testHaving(): void
    {
        // si no existe la tabla productos, saltamos el test
        if (false === $this->db()->tableExists('productos')) {
            $this->markTestSkipped('Table productos does not exist.');
        }

        // Limpiamos los productos y familias creados por este test en ejecuciones anteriores
        $done = DbQuery::table('productos')
            ->whereIn('codfamilia', ['FAM1', 'FAM2', 'FAM3'])
            ->delete();
        $this->assertTrue($done);
        $done = DbQuery::table('familias')
            ->whereIn('codfamilia', ['FAM1', 'FAM2', 'FAM3'])
            ->delete();
        $this->assertTrue($done);

        $data = [
            ['referencia' => 'PROD_FAM1_A', 'descripcion' => 'Producto Familia 1 A', 'codfamilia' => 'FAM1', 'precio' => 10],
            ['referencia' => 'PROD_FAM1_B', 'descripcion' => 'Producto Familia 1 B', 'codfamilia' => 'FAM1', 'precio' => 20],
            ['referencia' => 'PROD_FAM2_A', 'descripcion' => 'Producto Familia 2 A', 'codfamilia' => 'FAM2', 'precio' => 5],
            ['referencia' => 'PROD_FAM3_A', 'descripcion' => 'Producto Familia 3 A', 'codfamilia' => 'FAM3', 'precio' => 30],
        ];

        // insertamos los productos
        foreach ($data as $item) {
            $familia = new Familia();
            if(!$familia->loadFromCode($item['codfamilia'])) {
                $familia->codfamilia = $item['codfamilia'];
                $familia->descripcion = $item['codfamilia'];
                $this->assertTrue($familia->save());
            }

            $producto = new Producto();
            $producto->referencia = $item['referencia'];
            $producto->descripcion = $item['descripcion'];
            $producto->codfamilia = $item['codfamilia'];
            $producto->precio = $item['precio'];
            $this->assertTrue($producto->save());
        }

        // construimos la consulta con groupBy y having
        $query = DbQuery::table('productos')
            ->select(
                'codfamilia,'
                .' SUM(' . $this->db()->escapeColumn('precio') . ') as total_precio'
            )
            ->groupBy('codfamilia')
            ->having('total_precio > 25')
            ->whereLike('codfamilia', 'FAM%');

        $sql = 'SELECT ' . $this->db()->escapeColumn('codfamilia') . ', SUM(' . $this->db()->escapeColumn('precio') . ') AS ' . $this->db()->escapeColumn('total_precio')
            . ' FROM ' . $this->db()->escapeColumn('productos')
            . ' WHERE ' . Where::like('codfamilia', 'FAM%')->sql()
            . ' GROUP BY ' . $this->db()->escapeColumn('codfamilia')
            . ' HAVING total_precio > 25';
        $this->assertEquals($sql, $query->sql());

        $results = $query->get();
        $this->assertCount(2, $results);
        $this->assertEquals('FAM1', $results[0]['codfamilia']);
        $this->assertEquals(30, $results[0]['total_precio']);
        $this->assertEquals('FAM3', $results[1]['codfamilia']);
        $this->assertEquals(30, $results[1]['total_precio']);

        // Limpiamos los productos y familias
        $done = DbQuery::table('productos')
            ->whereIn('codfamilia', ['FAM1', 'FAM2', 'FAM3'])
            ->delete();
        $this->assertTrue($done);
        $done = DbQuery::table('familias')
            ->whereIn('codfamilia', ['FAM1', 'FAM2', 'FAM3'])
            ->delete();
        $this->assertTrue($done);
    }

    public function testGroupBy(): void
    {
        // si no existe la tabla productos, saltamos el test
        if (false === $this->db()->tableExists('productos')) {
            $this->markTestSkipped('Table productos does not exist.');
        }

        // Limpiamos los productos y familias creados por este test en ejecuciones anteriores
        $done = DbQuery::table('productos')
            ->whereIn('codfamilia', ['FAM1', 'FAM2', 'FAM3'])
            ->delete();
        $this->assertTrue($done);
        $done = DbQuery::table('familias')
            ->whereIn('codfamilia', ['FAM1', 'FAM2', 'FAM3'])
            ->delete();
        $this->assertTrue($done);

        $data = [
            ['referencia' => 'PROD_FAM1_A', 'descripcion' => 'Producto Familia 1 A', 'codfamilia' => 'FAM1', 'precio' => 10],
            ['referencia' => 'PROD_FAM1_B', 'descripcion' => 'Producto Familia 1 B', 'codfamilia' => 'FAM1', 'precio' => 20],
            ['referencia' => 'PROD_FAM2_A', 'descripcion' => 'Producto Familia 2 A', 'codfamilia' => 'FAM2', 'precio' => 5],
            ['referencia' => 'PROD_FAM3_A', 'descripcion' => 'Producto Familia 3 A', 'codfamilia' => 'FAM3', 'precio' => 30],
        ];

        // insertamos los productos
        foreach ($data as $item) {
            $familia = new Familia();
            if(!$familia->loadFromCode($item['codfamilia'])) {
                $familia->codfamilia = $item['codfamilia'];
                $familia->descripcion = $item['codfamilia'];
                $this->assertTrue($familia->save());
            }

            $producto = new Producto();
            $producto->referencia = $item['referencia'];
            $producto->descripcion = $item['descripcion'];
            $producto->codfamilia = $item['codfamilia'];
            $producto->precio = $item['precio'];
            $this->assertTrue($producto->save());
        }

        // construimos la consulta con groupBy
        $query = DbQuery::table('productos')
            ->select('codfamilia, COUNT(*) AS total_productos')
            ->groupBy('codfamilia')
            ->whereLike('codfamilia', 'FAM%');

        $sql = 'SELECT ' . $this->db()->escapeColumn('codfamilia') . ', COUNT(*) AS ' . $this->db()->escapeColumn('total_productos')
            . ' FROM ' . $this->db()->escapeColumn('productos')
            . ' WHERE ' . Where::like('codfamilia', 'FAM%')->sql()
            . ' GROUP BY ' . $this->db()->escapeColumn('codfamilia');
        $this->assertEquals($sql, $query->sql());

        $results = $query->get();
        $this->assertCount(3, $results);

        // Ordenamos los resultados para asegurar un orden consistente en las aserciones
        usort($results, function($a, $b) {
            return strcmp($a['codfamilia'], $b['codfamilia']);
        });

        $this->assertEquals('FAM1', $results[0]['codfamilia']);
        $this->assertEquals(2, $results[0]['total_productos']);
        $this->assertEquals('FAM2', $results[1]['codfamilia']);
        $this->assertEquals(1, $results[1]['total_productos']);
        $this->assertEquals('FAM3', $results[2]['codfamilia']);
        $this->assertEquals(1, $results[2]['total_productos']);

        // Limpiamos los productos y familias
        $done = DbQuery::table('productos')
            ->whereIn('codfamilia', ['FAM1', 'FAM2', 'FAM3'])
            ->delete();
        $this->assertTrue($done);
        $done = DbQuery::table('familias')
            ->whereIn('codfamilia', ['FAM1', 'FAM2', 'FAM3'])
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

    protected function setUp(): void
    {
        new Serie();
        new Cliente();
        new Pais();
        new Impuesto();
        new Producto();
        new Familia();

    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
