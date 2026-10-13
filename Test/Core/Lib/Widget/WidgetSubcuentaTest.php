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

use FacturaScripts\Core\Lib\Widget\WidgetSubcuenta;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class WidgetSubcuentaTest extends TestCase
{
    use LogErrorsTrait;

    public function testModalListIsNotRenderedOnEdit(): void
    {
        $widget = new WidgetSubcuenta([
            'children' => [],
            'fieldname' => 'test',
            'type' => 'subcuenta',
        ]);

        $model = (object)['test' => '4720000000'];
        $html = $widget->edit($model);

        $this->assertStringContainsString('widget-subaccount-modal', $html);
        $this->assertStringContainsString('4720000000', $html);
        $this->assertStringNotContainsString('widget-subaccount-option', $html);
        $this->assertMatchesRegularExpression('/<tbody id="list_\d+"><\/tbody>/', $html);
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
