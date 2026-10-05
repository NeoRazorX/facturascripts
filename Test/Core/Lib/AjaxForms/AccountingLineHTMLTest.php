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

use FacturaScripts\Core\Contract\AccountingLineModInterface;
use FacturaScripts\Core\Lib\AjaxForms\AccountingLineHTML;
use FacturaScripts\Core\Model\Asiento;
use FacturaScripts\Core\Model\Partida;
use FacturaScripts\Dinamic\Model\Asiento as DinamicAsiento;
use FacturaScripts\Dinamic\Model\Partida as DinamicPartida;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

/**
 * @covers AccountingLineHTML
 */
final class AccountingLineHTMLTest extends TestCase
{
    protected function setUp(): void
    {
        $this->resetMods();
    }

    protected function tearDown(): void
    {
        $this->resetMods();
    }

    public function testApplyRecalculatesTotalsAfterMods(): void
    {
        AccountingLineHTML::addMod(new class implements AccountingLineModInterface {
            public function apply(Asiento &$model, array &$lines, array $formData): void
            {
                $lines[0]->debe = 175.0;
                $lines[0]->haber = 25.0;
            }

            public function applyToLine(array $formData, Partida &$line, string $id): void
            {
            }

            public function assets(): void
            {
            }

            public function newFields(): array
            {
                return [];
            }

            public function newModalFields(): array
            {
                return [];
            }

            public function renderField(string $idlinea, Partida $line, Asiento $model, string $field): ?string
            {
                return null;
            }
        });

        $model = (new ReflectionClass(DinamicAsiento::class))->newInstanceWithoutConstructor();
        $line = (new ReflectionClass(DinamicPartida::class))->newInstanceWithoutConstructor();
        $line->idpartida = 1;
        $lines = [$line];
        $formData = [
            'action' => 'save',
            'codsubcuenta_1' => '4300000000',
            'debe_1' => '100',
            'haber_1' => '0',
            'iva_1' => '',
        ];

        AccountingLineHTML::apply($model, $lines, $formData);

        $this->assertSame(175.0, $model->debe);
        $this->assertSame(25.0, $model->haber);
        $this->assertSame(175.0, $model->importe);
    }

    private function resetMods(): void
    {
        $property = new ReflectionProperty(AccountingLineHTML::class, 'mods');
        $property->setValue(null, []);
    }
}
