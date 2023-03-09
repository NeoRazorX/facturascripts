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
use PHPUnit\Framework\TestCase;

final class PluginsTest extends TestCase
{
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

        $this->assertFalse(Plugins::add(__DIR__ . '/../__files/Plugin1.zip'));

        // comprobamos que no se ha añadido ningún plugin
        $this->assertEquals($initialList, Plugins::list());
    }
}
