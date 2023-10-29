<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace Core;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\DbQuery;
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
        $query = DbQuery::table('series');

        $sql = 'SELECT * FROM ' . $this->db()->escapeColumn('series');
        $this->assertEquals($sql, $query->sql());

        $data = $this->db()->select($sql);
        $this->assertEquals($data, $query->get());
    }

    public function testWhereEq(): void
    {
        $query = DbQuery::table('clientes')
            ->select('codcliente, nombre')
            ->whereEq('codcliente', 'test');

        $sql = 'SELECT ' . $this->db()->escapeColumn('codcliente')
            . ', ' . $this->db()->escapeColumn('nombre')
            . ' FROM ' . $this->db()->escapeColumn('clientes')
            . ' WHERE ' . $this->db()->escapeColumn('codcliente') . ' = ' . $this->db()->var2str('test');
        $this->assertEquals($sql, $query->sql());
    }

    public function testWhere(): void
    {
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

    public function testOrderBy(): void
    {
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
        // obtenemos el número de registros en la tabla paises
        $count = DbQuery::table('paises')->count();

        // lo comprobamos contra el modelo
        $pais = new Pais();
        $this->assertEquals($count, $pais->count());
    }

    public function testInsert(): void
    {
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