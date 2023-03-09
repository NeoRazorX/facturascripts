<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core;

use FacturaScripts\Core\Plugins;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class PluginsTest extends TestCase
{
    use LogErrorsTrait;

    public function testFolder()
    {
        // si no existe la carpeta Plugins, la creamos
        if (!is_dir(Plugins::folder())) {
            mkdir(Plugins::folder());
        }

        $this->assertDirectoryExists(Plugins::folder());
    }

    public function testList()
    {
        $list = Plugins::list();
        $this->assertIsArray($list);

        // comprobamos que es el mismo número de directorios que de plugins
        $this->assertCount(count($list), glob(Plugins::folder() . '/*', GLOB_ONLYDIR));
    }

    public function testDisableAllPlugins()
    {
        foreach (Plugins::enabled() as $pluginName) {
            $this->assertTrue(Plugins::disable($pluginName));
        }

        $this->assertEmpty(Plugins::enabled());
    }

    public function testNoPluginFile()
    {
        // obtenemos la lista de plugin
        $initialList = Plugins::list();

        $this->assertFalse(Plugins::add(__DIR__ . '/../__files/NoPluginFile.zip'));

        // comprobamos que no se ha añadido ningún plugin
        $this->assertEquals($initialList, Plugins::list());
    }

    public function testBadPluginStructure()
    {
        // obtenemos la lista de plugin
        $initialList = Plugins::list();

        $this->assertFalse(Plugins::add(__DIR__ . '/../__files/BadPluginStructure.zip'));

        // comprobamos que no se ha añadido ningún plugin
        $this->assertEquals($initialList, Plugins::list());
    }

    public function testEmptyPlugin()
    {
        // obtenemos la lista de plugin
        $initialList = Plugins::list();

        $this->assertFalse(Plugins::add(__DIR__ . '/../__files/EmptyPlugin.zip'));

        // comprobamos que no se ha añadido ningún plugin
        $this->assertEquals($initialList, Plugins::list());
    }

    public function testPlugin1()
    {
        // obtenemos la lista de plugin
        $initialList = Plugins::list();

        $this->assertFalse(Plugins::add(__DIR__ . '/../__files/TestPlugin1.zip'));

        // comprobamos que no se ha añadido ningún plugin
        $this->assertEquals($initialList, Plugins::list());
    }

    public function testPlugin2()
    {
        // obtenemos la lista de plugin
        $initialList = Plugins::list();

        $this->assertTrue(Plugins::add(__DIR__ . '/../__files/TestPlugin2.zip'));

        // comprobamos que se ha añadido un plugin
        $this->assertCount(count($initialList) + 1, Plugins::list());

        // comprobamos que se ha creado el directorio del plugin
        $this->assertDirectoryExists(Plugins::folder() . '/TestPlugin2');

        // comprobamos que se ha creado el archivo del plugin
        $this->assertFileExists(Plugins::folder() . '/TestPlugin2/facturascripts.ini');

        // comprobamos la información del plugin
        $plugin = Plugins::get('TestPlugin2');
        $this->assertEquals('TestPlugin2', $plugin->name);
        $this->assertEquals('Description Test Plugin 2', $plugin->description);
        $this->assertEquals(1, $plugin->version);
        $this->assertEquals(2023, $plugin->min_version);
        $this->assertTrue($plugin->compatible);
        $this->assertFalse($plugin->enabled);

        // activamos el plugin
        $this->assertTrue(Plugins::enable('TestPlugin2'));

        // comprobamos que se ha activado el plugin
        $plugin = Plugins::get('TestPlugin2');
        $this->assertTrue($plugin->enabled);
        $this->assertContains('TestPlugin2', Plugins::enabled());
        $this->assertTrue(Plugins::isEnabled('TestPlugin2'));

        // desactivamos el plugin
        $this->assertTrue(Plugins::disable('TestPlugin2'));

        // comprobamos que se ha desactivado el plugin
        $plugin = Plugins::get('TestPlugin2');
        $this->assertFalse($plugin->enabled);
        $this->assertNotContains('TestPlugin2', Plugins::enabled());
        $this->assertFalse(Plugins::isEnabled('TestPlugin2'));

        // eliminamos el plugin
        $this->assertTrue(Plugins::remove('TestPlugin2'));

        // comprobamos que se ha eliminado el plugin
        $this->assertNull(Plugins::get('TestPlugin2'));
        $this->assertEquals($initialList, Plugins::list());
    }

    public function testPlugin3()
    {
        // obtenemos la lista de plugin
        $initialList = Plugins::list();

        $this->assertTrue(Plugins::add(__DIR__ . '/../__files/TestPlugin3.zip'));

        // comprobamos que se ha añadido un plugin
        $this->assertCount(count($initialList) + 1, Plugins::list());

        // comprobamos la información del plugin
        $plugin = Plugins::get('TestPlugin3');
        $this->assertEquals('TestPlugin3', $plugin->name);
        $this->assertEquals('Test Plugin 3 description', $plugin->description);
        $this->assertEquals(1.1, $plugin->version);
        $this->assertEquals(2023, $plugin->min_version);
        $this->assertContains('TestPlugin2', $plugin->require);

        // comprobamos que no podemos activar el plugin porque no tenemos activado el plugin TestPlugin2
        $this->assertFalse(Plugins::enable('TestPlugin3'));

        // añadimos y activamos el plugin TestPlugin2
        $this->assertTrue(Plugins::add(__DIR__ . '/../__files/TestPlugin2.zip'));
        $this->assertTrue(Plugins::enable('TestPlugin2'));

        // comprobamos que ahora podemos activar el plugin TestPlugin3
        $this->assertTrue(Plugins::enable('TestPlugin3'));

        // comprobamos que se has activado los dos plugins
        $this->assertContains('TestPlugin2', Plugins::enabled());
        $this->assertContains('TestPlugin3', Plugins::enabled());

        // desactivamos el plugin TestPlugin2
        $this->assertTrue(Plugins::disable('TestPlugin2'));

        // comprobamos que se ha desactivado el plugin TestPlugin3
        $this->assertFalse(Plugins::isEnabled('TestPlugin3'));

        // eliminamos los dos plugins
        $this->assertTrue(Plugins::remove('TestPlugin2'));
        $this->assertTrue(Plugins::remove('TestPlugin3'));

        // comprobamos que se han eliminado los dos plugins
        $this->assertNull(Plugins::get('TestPlugin2'));
        $this->assertNull(Plugins::get('TestPlugin3'));
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
