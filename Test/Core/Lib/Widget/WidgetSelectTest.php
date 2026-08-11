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

use FacturaScripts\Core\Lib\Widget\WidgetSelect;
use PHPUnit\Framework\TestCase;

class WidgetSelectTest extends TestCase
{
    public function testSharedListIsRenderedOnlyOnce(): void
    {
        $widget = new WidgetSelectForTest($this->widgetData(['shared' => 'true']));
        $widget->setValuesFromArray([
            ['value' => 1, 'title' => 'Small', 'group' => 'Size'],
            ['value' => 2, 'title' => 'Large', 'group' => 'Size'],
        ], false, true, 'value', 'title', 'group');

        $first = $widget->renderInput(2);
        $second = $widget->renderInput(1);
        $empty = $widget->renderInput(null);

        $this->assertSame(3, substr_count($first, '<option'));
        $this->assertStringContainsString('<optgroup label="Size">', $first);
        $this->assertStringContainsString('data-shared-source="true"', $first);
        $this->assertSame(1, substr_count($second, '<option'));
        $this->assertStringContainsString('<option value="1" selected>Small</option>', $second);
        $this->assertStringNotContainsString('data-shared-source', $second);
        $this->assertSame(1, substr_count($empty, '<option'));
        $this->assertStringContainsString('<option value="" selected>------</option>', $empty);

        preg_match('/data-shared-list="([^"]+)"/', $first, $firstList);
        preg_match('/data-shared-list="([^"]+)"/', $second, $secondList);
        $this->assertSame($firstList[1], $secondList[1]);
    }

    public function testParentSelectDoesNotShareItsOptions(): void
    {
        $widget = new WidgetSelectForTest($this->widgetData([
            'parent' => 'category',
            'shared' => 'true',
        ]));
        $widget->setValuesFromArrayKeys([1 => 'One', 2 => 'Two']);

        $first = $widget->renderInput(1);
        $second = $widget->renderInput(2);

        $this->assertSame(2, substr_count($first, '<option'));
        $this->assertSame(2, substr_count($second, '<option'));
        $this->assertStringNotContainsString('data-shared-list', $first);
        $this->assertStringNotContainsString('data-shared-list', $second);
    }

    public function testSharedMultipleSelectKeepsEverySelectedOption(): void
    {
        $widget = new WidgetSelectForTest($this->widgetData([
            'multiple' => 'true',
            'shared' => 'true',
        ]));
        $widget->setValuesFromArrayKeys([1 => 'One', 2 => 'Two', 3 => 'Three']);

        $widget->renderInput('1');
        $second = $widget->renderInput('1,3');

        $this->assertSame(2, substr_count($second, '<option'));
        $this->assertStringContainsString('<option value="1" selected>One</option>', $second);
        $this->assertStringContainsString('<option value="3" selected>Three</option>', $second);
    }

    private function widgetData(array $extra = []): array
    {
        return array_merge([
            'children' => [],
            'fieldname' => 'test',
            'type' => 'select',
        ], $extra);
    }
}

class WidgetSelectForTest extends WidgetSelect
{
    public function renderInput($value): string
    {
        $this->setCustomValue($value);
        return $this->inputHtml();
    }
}
