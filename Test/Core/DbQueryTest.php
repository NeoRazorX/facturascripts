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
use FacturaScripts\Core\Where;
use PHPUnit\Framework\TestCase;

final class DbQueryTest extends TestCase
{
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

    private function db(): DataBase
    {
        if (null === $this->db) {
            $this->db = new DataBase();
        }

        return $this->db;
    }
}