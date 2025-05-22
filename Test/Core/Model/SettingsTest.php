<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Model;

use FacturaScripts\Core\Model\Settings;
use PHPUnit\Framework\TestCase;

final class SettingsTest extends TestCase
{
    public function testCreate(): void
    {
        // creamos un registro
        $settings = new Settings();
        $settings->name = 'test';
        $this->assertTrue($settings->save());

        // comprobamos que se ha creado
        $this->assertTrue($settings->exists());

        // lo eliminamos
        $this->assertTrue($settings->delete());
    }

    public function testEscapeHtml(): void
    {
        // creamos un registro con valores que contienen código html
        $settings = new Settings();
        $settings->name = '"> <img/src=x onerror=alert(1)>';
        $settings->property1 = '<script>alert("test1");</script>';
        $settings->property2 = '<script>alert("test2");</script>';
        $this->assertTrue($settings->save());

        // recargamos el registro
        $settings->loadFromCode($settings->name);

        // comprobamos que se han escapado los valores
        $this->assertEquals('&quot;&gt; &lt;img/src=x onerror=alert(1)&gt;', $settings->name);
        $this->assertEquals('&lt;script&gt;alert(&quot;test1&quot;);&lt;/script&gt;', $settings->property1);
        $this->assertEquals('&lt;script&gt;alert(&quot;test2&quot;);&lt;/script&gt;', $settings->property2);

        // lo eliminamos
        $this->assertTrue($settings->delete());
    }
}
