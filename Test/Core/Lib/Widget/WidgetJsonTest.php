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

use FacturaScripts\Core\Lib\Widget\WidgetJson;
use FacturaScripts\Core\Request;
use PHPUnit\Framework\TestCase;

final class WidgetJsonTest extends TestCase
{
    public function testInvalidJsonIsEditedInTextarea(): void
    {
        $model = (object)['test' => 'not json'];

        $html = $this->getWidget()->edit($model);
        $this->assertStringContainsString('<textarea', $html);
        $this->assertStringNotContainsString('type="hidden"', $html);
    }

    public function testValidJsonIsKeptWhenSaving(): void
    {
        $json = '{"user":"admin","note":"<b>\"quoted\"</b>","data":{"code":15}}';
        $model = (object)['test' => $json];

        $widget = $this->getWidget();
        $html = $widget->edit($model);
        $this->assertStringContainsString('widgetjsonkeyvalue', $html);
        $this->assertStringNotContainsString('<b>', $html);

        // el formulario debe enviar el valor en un campo oculto
        $found = preg_match('/<input type="hidden" name="test" value="([^"]*)"\/>/', $html, $matches);
        $this->assertSame(1, $found);

        // simulamos el envío del formulario: el navegador decodifica las entidades del atributo
        $posted = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
        $model->test = null;
        $widget->processFormData($model, new Request(['request' => ['test' => $posted]]));
        $this->assertSame($json, $model->test);
    }

    private function getWidget(): WidgetJson
    {
        return new WidgetJson(['fieldname' => 'test', 'type' => 'json', 'children' => []]);
    }
}
