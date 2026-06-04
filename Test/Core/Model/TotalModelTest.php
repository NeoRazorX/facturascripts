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

namespace FacturaScripts\Test\Core\Model;

use FacturaScripts\Core\Model\TotalModel;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class TotalModelTest extends TestCase
{
    use LogErrorsTrait;

    public function testConstructor(): void
    {
        $tm = new TotalModel();
        $this->assertEquals('', $tm->code);
        $this->assertEquals([], $tm->totals);

        $tm = new TotalModel(['code' => 'X', 'total' => 42, 'cuenta' => null]);
        $this->assertEquals('X', $tm->code);
        $this->assertEquals(42, $tm->totals['total']);
        $this->assertEquals(0, $tm->totals['cuenta']);
    }

    public function testClearTotals(): void
    {
        $tm = new TotalModel(['total' => 100, 'cuenta' => 5]);
        $tm->clearTotals(['total', 'cuenta', 'extra']);
        $this->assertEquals(0.0, $tm->totals['total']);
        $this->assertEquals(0.0, $tm->totals['cuenta']);
        $this->assertEquals(0.0, $tm->totals['extra']);
    }

    public function testAllWithValidTable(): void
    {
        // Una tabla común que existe en cualquier instalación FS
        $result = TotalModel::all('almacenes', [], ['total' => 'COUNT(*)']);
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('total', $result[0]->totals);
    }

    public function testAllWithInvalidTableName(): void
    {
        $result = TotalModel::all('alm; DROP TABLE x--', [], ['total' => 'COUNT(*)']);
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        // resultado vacío: totales a cero
        $this->assertEquals(0.0, $result[0]->totals['total']);
    }

    public function testAllWithInvalidFieldCode(): void
    {
        $result = TotalModel::all('almacenes', [], ['total' => 'COUNT(*)'], 'codalmacen OR 1=1');
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(0.0, $result[0]->totals['total']);
    }

    public function testAllWithInvalidAlias(): void
    {
        // Alias con caracteres no permitidos
        $result = TotalModel::all('almacenes', [], ['total OR 1' => 'COUNT(*)']);
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    public function testAllWithInvalidAggregateExpression(): void
    {
        // Expresión no agregada / con SQL injection
        $result = TotalModel::all('almacenes', [], ['total' => '1 UNION SELECT password FROM users--']);
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(0.0, $result[0]->totals['total']);
    }

    public function testAllWithMultipleAggregates(): void
    {
        $result = TotalModel::all('almacenes', [], [
            'count' => 'COUNT(*)',
            'maxId' => 'MAX(codalmacen)',
        ]);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('count', $result[0]->totals);
        $this->assertArrayHasKey('maxId', $result[0]->totals);
    }

    public function testSumWithValidArgs(): void
    {
        // Tabla común con columna numérica
        $result = TotalModel::sum('paises', 'codpais', []);
        $this->assertIsFloat($result);
    }

    public function testSumWithInvalidTableName(): void
    {
        $result = TotalModel::sum('foo; DROP--', 'campo', []);
        $this->assertEquals(0.0, $result);
    }

    public function testSumWithInvalidFieldName(): void
    {
        $result = TotalModel::sum('almacenes', 'campo OR 1=1', []);
        $this->assertEquals(0.0, $result);
    }

    public function testSumWithEmptyFieldName(): void
    {
        $result = TotalModel::sum('almacenes', '', []);
        $this->assertEquals(0.0, $result);
    }

    public function testSumWithNonExistentTable(): void
    {
        $result = TotalModel::sum('tabla_inexistente_xyz', 'campo', []);
        $this->assertEquals(0.0, $result);
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
