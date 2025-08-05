<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class SettingsTest extends TestCase
{
    use LogErrorsTrait;

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

    public function testProperties(): void
    {
        // creamos un registro
        $settings = new Settings();
        $settings->name = 'test_properties';
        $settings->setProperty('property1', 'value1');
        $settings->setProperty('property2', 'value2');
        $this->assertEquals('value1', $settings->getProperty('property1'));
        $this->assertEquals('value2', $settings->getProperty('property2'));
        $this->assertTrue($settings->save());

        // comprobamos que se han guardado las propiedades
        $this->assertTrue($settings->exists());
        $this->assertEquals('value1', $settings->getProperty('property1'));
        $this->assertEquals('value2', $settings->getProperty('property2'));
        $this->assertEquals(['property1' => 'value1', 'property2' => 'value2'], $settings->getProperties());

        // comprobamos que no se pueden obtener propiedades no definidas
        $this->assertNull($settings->getProperty('property3'));

        // recargamos el registro
        $this->assertTrue($settings->reload());

        // comprobamos que se han recuperado las propiedades
        $this->assertEquals('value1', $settings->getProperty('property1'));
        $this->assertEquals('value2', $settings->getProperty('property2'));

        // comprobamos que se han recuperado todas las propiedades
        $this->assertCount(2, $settings->getProperties());

        // comprobamos que se pueden eliminar propiedades
        $settings->removeProperty('property1');
        $this->assertNull($settings->getProperty('property1'));

        // comprobamos que sigue existiendo la otra propiedad
        $this->assertEquals('value2', $settings->getProperty('property2'));
        $this->assertCount(1, $settings->getProperties());

        // eliminamos
        $this->assertTrue($settings->delete());
    }

    public function testGetSet(): void
    {
        // creamos un registro
        $settings = new Settings();
        $settings->name = 'test_get_set';
        $settings->setProperty('property1', 'value1');
        $settings->setProperty('property2', 'value2');
        $this->assertTrue($settings->save());

        // comprobamos que se han guardado las propiedades
        $this->assertTrue($settings->exists());
        $this->assertEquals('value1', $settings->getProperty('property1'));
        $this->assertEquals('value2', $settings->getProperty('property2'));

        // comprobamos que se pueden consultar las propiedades directamente
        $this->assertEquals('value1', $settings->property1);
        $this->assertEquals('value2', $settings->property2);

        // comprobamos que se pueden modificar las propiedades directamente
        $settings->property1 = 'new_value1';
        $settings->property2 = 'new_value2';
        $this->assertEquals('new_value1', $settings->getProperty('property1'));
        $this->assertEquals('new_value2', $settings->getProperty('property2'));
        $this->assertTrue($settings->save());

        // recargamos el registro
        $this->assertTrue($settings->reload());

        // comprobamos que se han recuperado las propiedades modificadas
        $this->assertEquals('new_value1', $settings->getProperty('property1'));
        $this->assertEquals('new_value2', $settings->getProperty('property2'));

        // eliminamos
        $this->assertTrue($settings->delete());
    }

    public function testEscapeHtml(): void
    {
        // creamos un registro con valores que contienen código html
        $settings = new Settings();
        $settings->name = '"> <img/src=x onerror=alert(1)>';
        $settings->setProperty('property1', '<script>alert("test1");</script>');
        $settings->setProperty('property2', '<script>alert("test2");</script>');
        $this->assertTrue($settings->save());

        // recargamos el registro
        $this->assertTrue($settings->load($settings->name));

        // comprobamos que se han escapado los valores
        $this->assertEquals('&quot;&gt; &lt;img/src=x onerror=alert(1)&gt;', $settings->name);
        $this->assertEquals('&lt;script&gt;alert(&quot;test1&quot;);&lt;/script&gt;', $settings->getProperty('property1'));
        $this->assertEquals('&lt;script&gt;alert(&quot;test2&quot;);&lt;/script&gt;', $settings->getProperty('property2'));

        // lo eliminamos
        $this->assertTrue($settings->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
