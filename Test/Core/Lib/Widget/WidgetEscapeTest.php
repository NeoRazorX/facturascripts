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

use FacturaScripts\Core\Lib\Widget\WidgetAutocomplete;
use FacturaScripts\Core\Lib\Widget\WidgetDatalist;
use FacturaScripts\Core\Lib\Widget\WidgetFile;
use FacturaScripts\Core\Lib\Widget\WidgetPassword;
use FacturaScripts\Core\Lib\Widget\WidgetSelect;
use FacturaScripts\Core\Lib\Widget\WidgetText;
use FacturaScripts\Core\Lib\Widget\WidgetTextarea;
use PHPUnit\Framework\TestCase;

/**
 * Comprueba que los widgets escapan el valor al pintarlo, aunque llegue sin limpiar
 * (por ejemplo, desde la URL de un formulario de edición).
 */
final class WidgetEscapeTest extends TestCase
{
    const PAYLOAD = '"><img src=x onerror=alert(1)></textarea><script>alert(2)</script>';

    public function testAutocompleteEscapesValue(): void
    {
        $widget = new WidgetAutocomplete($this->widgetData('autocomplete', [
            ['tag' => 'values', 'source' => 'paises', 'fieldcode' => 'codpais', 'fieldtitle' => 'nombre'],
        ]));
        $this->assertEscaped($widget->edit($this->model()));
    }

    public function testDatalistEscapesValue(): void
    {
        $widget = new WidgetDatalist($this->widgetData('datalist'));
        $this->assertEscaped($widget->edit($this->model()));
    }

    public function testFileEscapesValue(): void
    {
        $widget = new WidgetFile($this->widgetData('file'));
        $this->assertEscaped($widget->edit($this->model()));

        $readonly = new WidgetFile($this->widgetData('file', [], ['readonly' => 'true']));
        $this->assertEscaped($readonly->edit($this->model()));
    }

    public function testHiddenInputEscapesValue(): void
    {
        $widget = new WidgetText($this->widgetData('text'));
        $this->assertEscaped($widget->inputHidden($this->model()));
    }

    public function testPasswordEscapesValue(): void
    {
        $widget = new WidgetPassword($this->widgetData('password'));
        $this->assertEscaped($widget->edit($this->model()));
    }

    public function testSelectEscapesValueNotInOptions(): void
    {
        $widget = new WidgetSelect($this->widgetData('select', [
            ['tag' => 'values', 'source' => 'paises', 'fieldcode' => 'codpais', 'fieldtitle' => 'nombre'],
        ]));
        $this->assertEscaped($widget->edit($this->model()));

        $readonly = new WidgetSelect($this->widgetData('select', [], ['readonly' => 'true']));
        $this->assertEscaped($readonly->edit($this->model()));
    }

    public function testStoredValueIsNotDoubleEscaped(): void
    {
        // los modelos guardan el texto ya codificado con Tools::noHtml()
        $model = (object)['test' => 'Tom &amp; Jerry &quot;S.L.&quot;'];

        $widget = new WidgetText($this->widgetData('text'));
        $this->assertStringContainsString('value="Tom &amp; Jerry &quot;S.L.&quot;"', $widget->edit($model));
    }

    public function testTextEscapesValue(): void
    {
        $widget = new WidgetText($this->widgetData('text'));
        $this->assertEscaped($widget->edit($this->model()));
    }

    public function testTextareaEscapesValue(): void
    {
        $widget = new WidgetTextarea($this->widgetData('textarea'));
        $this->assertEscaped($widget->edit($this->model()));
    }

    private function assertEscaped(string $html): void
    {
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
    }

    private function model(): object
    {
        return (object)['test' => self::PAYLOAD];
    }

    private function widgetData(string $type, array $children = [], array $extra = []): array
    {
        return array_merge([
            'children' => $children,
            'fieldname' => 'test',
            'type' => $type,
        ], $extra);
    }
}
