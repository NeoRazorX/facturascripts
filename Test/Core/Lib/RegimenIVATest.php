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

use FacturaScripts\Core\Lib\RegimenIVA;
use PHPUnit\Framework\TestCase;

final class RegimenIVATest extends TestCase
{
    public function testAllReturnsDefaults(): void
    {
        $all = RegimenIVA::all();

        $this->assertArrayHasKey(RegimenIVA::TAX_SYSTEM_GENERAL, $all);
        $this->assertArrayHasKey(RegimenIVA::TAX_SYSTEM_EXEMPT, $all);
        $this->assertArrayHasKey(RegimenIVA::TAX_SYSTEM_SURCHARGE, $all);
        $this->assertArrayHasKey(RegimenIVA::TAX_SYSTEM_SIMPLIFIED, $all);
        $this->assertArrayHasKey(RegimenIVA::TAX_SYSTEM_AGRARIAN, $all);
        $this->assertArrayHasKey(RegimenIVA::TAX_SYSTEM_CASH_CRITERIA, $all);
        $this->assertArrayHasKey(RegimenIVA::TAX_SYSTEM_GROUP_ENTITIES, $all);
        $this->assertArrayHasKey(RegimenIVA::TAX_SYSTEM_TRAVEL, $all);
        $this->assertArrayHasKey(RegimenIVA::TAX_SYSTEM_USED_GOODS, $all);
        $this->assertArrayHasKey(RegimenIVA::TAX_SYSTEM_GOLD, $all);
        $this->assertArrayHasKey(RegimenIVA::TAX_SYSTEM_SPECIAL_SMALL_BUSINESS, $all);
        $this->assertArrayHasKey(RegimenIVA::TAX_SYSTEM_ONE_STOP_SHOP_OSS, $all);
        $this->assertArrayHasKey(RegimenIVA::TAX_SYSTEM_ONE_STOP_SHOP_IOSS, $all);
        $this->assertCount(13, $all);
    }

    public function testDefaultValue(): void
    {
        $this->assertEquals(RegimenIVA::TAX_SYSTEM_GENERAL, RegimenIVA::defaultValue());
    }

    public function testAddCustomRegime(): void
    {
        RegimenIVA::add('custom-regime', 'custom-regime-label');

        $all = RegimenIVA::all();
        $this->assertArrayHasKey('custom-regime', $all);
        $this->assertEquals('custom-regime-label', $all['custom-regime']);

        // limpiamos
        RegimenIVA::remove('custom-regime');
        $this->assertArrayNotHasKey('custom-regime', RegimenIVA::all());
    }

    public function testAddOverridesExistingLabel(): void
    {
        $original = RegimenIVA::all()[RegimenIVA::TAX_SYSTEM_GENERAL];

        RegimenIVA::add(RegimenIVA::TAX_SYSTEM_GENERAL, 'new-label');
        $this->assertEquals('new-label', RegimenIVA::all()[RegimenIVA::TAX_SYSTEM_GENERAL]);

        // restauramos
        RegimenIVA::add(RegimenIVA::TAX_SYSTEM_GENERAL, $original);
    }

    public function testRemoveDefaultRegime(): void
    {
        RegimenIVA::remove(RegimenIVA::TAX_SYSTEM_GOLD);

        $this->assertArrayNotHasKey(RegimenIVA::TAX_SYSTEM_GOLD, RegimenIVA::all());

        // restauramos
        RegimenIVA::add(RegimenIVA::TAX_SYSTEM_GOLD, 'es-tax-regime-gold');
        $this->assertArrayHasKey(RegimenIVA::TAX_SYSTEM_GOLD, RegimenIVA::all());
    }

    public function testAddKeyIsTruncatedTo20Chars(): void
    {
        $longKey = 'this-key-is-way-too-long-for-limit';
        RegimenIVA::add($longKey, 'long-label');

        $truncated = substr($longKey, 0, 20);
        $all = RegimenIVA::all();
        $this->assertArrayHasKey($truncated, $all);
        $this->assertArrayNotHasKey($longKey, $all);

        // limpiamos
        RegimenIVA::remove($longKey);
    }

    public function testRemoveAndReAdd(): void
    {
        RegimenIVA::remove(RegimenIVA::TAX_SYSTEM_EXEMPT);
        $this->assertArrayNotHasKey(RegimenIVA::TAX_SYSTEM_EXEMPT, RegimenIVA::all());

        RegimenIVA::add(RegimenIVA::TAX_SYSTEM_EXEMPT, 'es-tax-regime-exempt');
        $this->assertArrayHasKey(RegimenIVA::TAX_SYSTEM_EXEMPT, RegimenIVA::all());
    }
}
