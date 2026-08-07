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

namespace FacturaScripts\Test\Core\Lib\AjaxForms;

use FacturaScripts\Core\Contract\AccountingModInterface;
use FacturaScripts\Core\Lib\AjaxForms\AccountingHeaderHTML;
use FacturaScripts\Dinamic\Model\Asiento;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

/**
 * @covers AccountingHeaderHTML
 */
final class AccountingHeaderHTMLTest extends TestCase
{
    protected function setUp(): void
    {
        $this->resetMods();
    }

    protected function tearDown(): void
    {
        $this->resetMods();
    }

    public function testRenderIncludesModButtons(): void
    {
        AccountingHeaderHTML::addMod(new class implements AccountingModInterface {
            public function apply(Asiento &$model, array $formData): void
            {
            }

            public function applyBefore(Asiento &$model, array $formData): void
            {
            }

            public function assets(): void
            {
            }

            public function newBtnFields(): array
            {
                return ['test-button'];
            }

            public function newFields(): array
            {
                return [];
            }

            public function renderField(Asiento $model, string $field): ?string
            {
                return $field === 'test-button' ? '<button id="test-button"></button>' : '';
            }
        });

        $model = (new ReflectionClass(Asiento::class))->newInstanceWithoutConstructor();
        $html = AccountingHeaderHTML::render($model);

        $this->assertStringContainsString('<button id="test-button"></button>', $html);
    }

    private function resetMods(): void
    {
        $property = new ReflectionProperty(AccountingHeaderHTML::class, 'mods');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }
}
