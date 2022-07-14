<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Balance;
use PHPUnit\Framework\TestCase;

final class BalanceTest extends TestCase
{
    public function testCreate()
    {
        // creamos un balance
        $balance = new Balance();
        $balance->codbalance = 'test';
        $balance->descripcion1 = 'test';
        $balance->naturaleza = 'A';
        $this->assertTrue($balance->save(), 'cant-save-balance');

        // eliminamos
        $this->assertTrue($balance->delete(), 'cant-delete-balance');
    }

    public function testCantCreateEmpty()
    {
        $balance = new Balance();
        $this->assertFalse($balance->save(), 'cant-save-balance');
    }

    public function testHtmlOnFields()
    {
        $balance = new Balance();
        $balance->codbalance = '<test>';
        $balance->descripcion1 = '<test>';
        $balance->descripcion2 = '<test>';
        $balance->descripcion3 = '<test>';
        $balance->descripcion4 = '<test>';
        $balance->descripcion4ba = '<test>';
        $balance->naturaleza = '<test>';
        $this->assertFalse($balance->save(), 'cant-save-balance-with-html');

        // cambiamos el codigo a un codigo válido
        $balance->codbalance = 'test';
        $this->assertTrue($balance->save(), 'cant-save-balance-2');

        // comprobamos que el html se ha escapado
        $this->assertEquals('&lt;test&gt;', $balance->descripcion1);
        $this->assertEquals('&lt;test&gt;', $balance->descripcion2);
        $this->assertEquals('&lt;test&gt;', $balance->descripcion3);
        $this->assertEquals('&lt;test&gt;', $balance->descripcion4);
        $this->assertEquals('&lt;test&gt;', $balance->descripcion4ba);
        $this->assertEquals('&lt;test&gt;', $balance->naturaleza);

        // eliminamos
        $this->assertTrue($balance->delete(), 'cant-delete-balance');
    }
}
