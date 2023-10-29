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
use FacturaScripts\Core\Where;
use PHPUnit\Framework\TestCase;

final class WhereTest extends TestCase
{
    /** @var DataBase */
    private $db;

    public function testNewInstance(): void
    {
        $item = new Where('test', 'value');
        $this->assertEquals('test', $item->fields);
        $this->assertEquals('value', $item->value);
        $this->assertEquals('=', $item->operator);
        $this->assertEquals('AND', $item->operation);

        $sql = $this->db()->escapeColumn('test') . ' = ' . $this->db()->var2str('value');
        $this->assertEquals($sql, $item->sql());
    }

    public function testColumn(): void
    {
        $item = Where::column('test2', 'value2');
        $this->assertEquals('test2', $item->fields);
        $this->assertEquals('value2', $item->value);
        $this->assertEquals('=', $item->operator);
        $this->assertEquals('AND', $item->operation);

        $sql = $this->db()->escapeColumn('test2') . ' = ' . $this->db()->var2str('value2');
        $this->assertEquals($sql, $item->sql());
    }

    private function db(): DataBase
    {
        if (null === $this->db) {
            $this->db = new DataBase();
        }

        return $this->db;
    }
}