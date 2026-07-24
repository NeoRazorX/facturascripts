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

namespace FacturaScripts\Test\Core\DataSrc;

use FacturaScripts\Core\DataSrc\Familias;
use FacturaScripts\Core\Lib\AjaxForms\SalesModalHTML;
use FacturaScripts\Dinamic\Model\Familia;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

final class FamiliasTest extends TestCase
{
    public function testZeroCodeIsNotTreatedAsRootLookup(): void
    {
        $zero = $this->family('0');
        $root = $this->family('ROOT');
        $child = $this->family('CHILD', '0');
        $this->setFamilies([$zero, $root, $child]);

        $this->assertSame([$zero, $root], Familias::children());
        $this->assertSame([$child], Familias::children('0'));
    }

    public function testRecursiveRenderingStopsOnCycle(): void
    {
        $familyA = $this->family('A', 'B');
        $familyB = $this->family('B', 'A');
        $this->setFamilies([$familyA, $familyB]);

        $method = new ReflectionMethod(SalesModalHTML::class, 'subfamilias');
        $method->setAccessible(true);
        $html = $method->invoke(null, $familyA);

        $this->assertStringContainsString('value="B"', $html);
        $this->assertStringNotContainsString('value="A"', $html);
        $this->assertSame(1, substr_count($html, 'value="B"'));
    }

    protected function tearDown(): void
    {
        Familias::clear();
    }

    private function family(string $code, ?string $mother = null): Familia
    {
        $reflection = new ReflectionClass(Familia::class);
        $family = $reflection->newInstanceWithoutConstructor();
        $family->codfamilia = $code;
        $family->descripcion = $code;
        $family->madre = $mother;

        return $family;
    }

    private function setFamilies(array $families): void
    {
        $property = new ReflectionProperty(Familias::class, 'list');
        $property->setAccessible(true);
        $property->setValue(null, $families);
    }
}
