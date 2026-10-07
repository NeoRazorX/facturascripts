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

namespace FacturaScripts\Test\Core\Lib\Widget;

use FacturaScripts\Core\Lib\Widget\WidgetNumber;
use FacturaScripts\Core\Lib\Widget\WidgetPercentage;
use FacturaScripts\Core\Tools;
use PHPUnit\Framework\TestCase;

final class WidgetNumberTest extends TestCase
{
    public function testNonNumericStringsAreShownAsEmpty(): void
    {
        foreach (['', 'abc'] as $value) {
            $model = (object)['test' => $value];

            $html = $this->getWidget(WidgetNumber::class)->tableCell($model);
            $this->assertStringContainsString('>-</td>', $html);

            $html = $this->getWidget(WidgetPercentage::class)->tableCell($model);
            $this->assertStringContainsString('>-</td>', $html);
        }
    }

    public function testNumericStringsAreFormatted(): void
    {
        $model = (object)['test' => '12.5'];

        $html = $this->getWidget(WidgetNumber::class)->tableCell($model);
        $this->assertStringContainsString('>' . Tools::number(12.5, 2) . '</td>', $html);

        $html = $this->getWidget(WidgetPercentage::class)->tableCell($model);
        $this->assertStringContainsString('>' . Tools::number(12.5, 2) . '%</td>', $html);
    }

    private function getWidget(string $class): WidgetNumber
    {
        return new $class(['fieldname' => 'test', 'decimal' => 2, 'type' => 'number', 'children' => []]);
    }
}
