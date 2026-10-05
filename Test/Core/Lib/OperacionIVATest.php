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

namespace FacturaScripts\Test\Core\Lib;

use FacturaScripts\Core\Lib\OperacionIVA;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class OperacionIVATest extends TestCase
{
    /** @var array */
    private $originalValues = [];

    public function testAddCustomOperation(): void
    {
        OperacionIVA::add('custom-operation', 'custom-operation-label');

        $all = OperacionIVA::all();
        $this->assertArrayHasKey('custom-operation', $all);
        $this->assertEquals('custom-operation-label', $all['custom-operation']);
    }

    public function testAddKeyIsTruncatedTo20Chars(): void
    {
        $longKey = 'this-key-is-way-too-long-for-limit';
        OperacionIVA::add($longKey, 'long-label');

        $truncated = substr($longKey, 0, 20);
        $all = OperacionIVA::all();
        $this->assertArrayHasKey($truncated, $all);
        $this->assertArrayNotHasKey($longKey, $all);
    }

    public function testAddOverridesExistingLabel(): void
    {
        OperacionIVA::add(OperacionIVA::ES_OPERATION_04, 'new-label');

        $this->assertEquals('new-label', OperacionIVA::all()[OperacionIVA::ES_OPERATION_04]);
    }

    public function testAllReturnsDefaults(): void
    {
        $this->assertEquals([
            OperacionIVA::ES_OPERATION_01 => 'es-operation-tax-added-value',
            OperacionIVA::ES_OPERATION_02 => 'es-operation-tax-ceuta-melilla',
            OperacionIVA::ES_OPERATION_03 => 'es-operation-tax-igic',
            OperacionIVA::ES_OPERATION_04 => 'es-operation-tax-ipsi',
            OperacionIVA::ES_OPERATION_99 => 'es-operation-tax-other',
        ], OperacionIVA::all());
    }

    public function testDefaultOperation(): void
    {
        $this->assertEquals(OperacionIVA::ES_OPERATION_01, OperacionIVA::default());
    }

    protected function setUp(): void
    {
        $property = new ReflectionProperty(OperacionIVA::class, 'values');
        $this->originalValues = $property->getValue();
        $property->setValue(null, []);
    }

    protected function tearDown(): void
    {
        $property = new ReflectionProperty(OperacionIVA::class, 'values');
        $property->setValue(null, $this->originalValues);
    }
}
