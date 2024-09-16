<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Internal\Plugin;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class PluginsTest extends TestCase
{
    use LogErrorsTrait;

    public static function setUpBeforeClass(): void
    {
        MiniLog::clear();
    }

    public function testFolder(): void
    {
        // si no existe la carpeta Plugins, la creamos
        if (!is_dir(Plugins::folder())) {
            mkdir(Plugins::folder());
        }

        $this->assertDirectoryExists(Plugins::folder());
    }

    public function testList(): void
    {
        $list = Plugins::list();
        $this->assertIsArray($list);

        // comprobamos que es el mismo número de directorios que de plugins
        $this->assertCount(count($list), glob(Plugins::folder() . '/*', GLOB_ONLYDIR));
    }

    public function testDisableAllPlugins(): void
    {
        foreach (Plugins::enabled() as $pluginName) {
            $this->assertTrue(Plugins::disable($pluginName));
        }

        $this->assertEmpty(Plugins::enabled());
    }

    public function testNoPluginFile(): void
    {
        // obtenemos la lista de plugin
        $initialList = Plugins::list();

        $this->assertFalse(Plugins::add(__DIR__ . '/../__files/NoPluginFile.zip'));

        // comprobamos que no se ha añadido ningún plugin
        $this->assertEquals($initialList, Plugins::list());
    }

    public function testBadPluginStructure(): void
    {
        // obtenemos la lista de plugin
        $initialList = Plugins::list();

        $this->assertFalse(Plugins::add(__DIR__ . '/../__files/BadPluginStructure.zip'));

        // comprobamos que no se ha añadido ningún plugin
        $this->assertEquals($initialList, Plugins::list());
    }

    public function testEmptyPlugin(): void
    {
        // obtenemos la lista de plugin
        $initialList = Plugins::list();

        $this->assertFalse(Plugins::add(__DIR__ . '/../__files/EmptyPlugin.zip'));

        // comprobamos que no se ha añadido ningún plugin
        $this->assertEquals($initialList, Plugins::list());
    }

    public function testPlugin1(): void
    {
        // obtenemos la lista de plugin
        $initialList = Plugins::list();

        $zipPath = __DIR__ . '/../__files/TestPlugin1.zip';
        $this->assertFalse(Plugins::add($zipPath));

        // comprobamos que no se ha añadido ningún plugin
        $this->assertEquals($initialList, Plugins::list());

        // cargamos los datos
        $plugin = Plugin::getFromZip($zipPath);
        $this->assertEquals('TestPlugin1', $plugin->name);
        $this->assertFalse($plugin->enabled);
        $this->assertFalse($plugin->installed);
    }

    public function testPlugin2(): void
    {
        // obtenemos la lista de plugin
        $initialList = Plugins::list();

        $this->assertTrue(Plugins::add(__DIR__ . '/../__files/TestPlugin2.zip'));

        // comprobamos que se ha añadido un plugin
        $this->assertCount(count($initialList) + 1, Plugins::list());

        // comprobamos que se ha creado el directorio del plugin
        $this->assertDirectoryExists(Plugins::folder() . '/TestPlugin2');

        // comprobamos que se han copiado los archivos del plugin
        $this->assertFileExists(Plugins::folder() . '/TestPlugin2/facturascripts.ini');
        $this->assertFileExists(Plugins::folder() . '/TestPlugin2/XMLView/TestFile.xml');

        // comprobamos la información del plugin
        $plugin = Plugins::get('TestPlugin2');
        $this->assertEquals('TestPlugin2', $plugin->name);
        $this->assertEquals('Description Test Plugin 2', $plugin->description);
        $this->assertEquals(1, $plugin->version);
        $this->assertEquals(2023, $plugin->min_version);
        $this->assertTrue($plugin->compatible);
        $this->assertTrue($plugin->installed);
        $this->assertFalse($plugin->enabled);

        // activamos el plugin
        $this->assertTrue(Plugins::enable('TestPlugin2'));

        // comprobamos que se ha activado el plugin
        $plugin = Plugins::get('TestPlugin2');
        $this->assertTrue($plugin->enabled);
        $this->assertTrue($plugin->post_enable);
        $this->assertFalse($plugin->post_disable);
        $this->assertContains('TestPlugin2', Plugins::enabled());
        $this->assertTrue(Plugins::isEnabled('TestPlugin2'));

        // comprobamos que el archivo se ha copiado a Dinamic
        $this->assertFileExists(Tools::folder() . '/Dinamic/XMLView/TestFile.xml');

        // comprobamos que no podemos eliminar sin desactivar
        $this->assertFalse(Plugins::remove('TestPlugin2'));

        // desactivamos el plugin
        $this->assertTrue(Plugins::disable('TestPlugin2'));

        // comprobamos que se ha desactivado el plugin
        $plugin = Plugins::get('TestPlugin2');
        $this->assertFalse($plugin->enabled);
        $this->assertFalse($plugin->post_enable);
        $this->assertTrue($plugin->post_disable);
        $this->assertNotContains('TestPlugin2', Plugins::enabled());
        $this->assertFalse(Plugins::isEnabled('TestPlugin2'));

        // eliminamos el plugin
        $this->assertTrue(Plugins::remove('TestPlugin2'));

        // comprobamos que se ha eliminado el plugin
        $this->assertNull(Plugins::get('TestPlugin2'));
        $this->assertEquals($initialList, Plugins::list());
    }

    public function testUpdatePlugin2(): void
    {
        $zipPath = __DIR__ . '/../__files/TestPlugin2.zip';

        // añadimos el plugin
        $this->assertTrue(Plugins::add($zipPath));

        // añadimos un archivo al plugin
        $filePath = Plugins::folder() . '/TestPlugin2/README.md';
        $this->assertTrue(file_put_contents($filePath, 'Test') !== false);

        // añadimos test = 1 al final del archivo facturascripts.ini
        $iniPath = Plugins::folder() . '/TestPlugin2/facturascripts.ini';
        $this->assertTrue(file_put_contents($iniPath, PHP_EOL . 'test = 1', FILE_APPEND) !== false);

        // actualizamos el plugin
        $this->assertTrue(Plugins::add($zipPath));

        // comprobamos que el archivo se ha eliminado
        $this->assertFileDoesNotExist($filePath);

        // comprobamos que el archivo facturascripts.ini se ha restaurado
        $this->assertFileExists($iniPath);
        $this->assertStringNotContainsString('test = 1', file_get_contents($iniPath));

        // comprobamos que sigue instalado
        $plugin = Plugins::get('TestPlugin2');
        $this->assertTrue($plugin->installed);
        $this->assertFalse($plugin->enabled);

        // eliminamos el plugin
        $this->assertTrue(Plugins::remove('TestPlugin2'));

        // comprobamos que se ha eliminado el plugin
        $this->assertNull(Plugins::get('TestPlugin2'));
    }

    public function testUpdateEnabledPlugin2(): void
    {
        $zipPath = __DIR__ . '/../__files/TestPlugin2.zip';

        // añadimos el plugin
        $this->assertTrue(Plugins::add($zipPath));

        // activamos el plugin
        $this->assertTrue(Plugins::enable('TestPlugin2'));
        $this->assertTrue(Plugins::get('TestPlugin2')->post_enable);
        Plugins::init();
        $this->assertFalse(Plugins::get('TestPlugin2')->post_enable);

        // añadimos un archivo al plugin
        $filePath = Plugins::folder() . '/TestPlugin2/README.md';
        $this->assertTrue(file_put_contents($filePath, 'Test') !== false);

        // añadimos test = 2 al final del archivo facturascripts.ini
        $iniPath = Plugins::folder() . '/TestPlugin2/facturascripts.ini';
        $this->assertTrue(file_put_contents($iniPath, PHP_EOL . 'test = 2', FILE_APPEND) !== false);

        // actualizamos el plugin
        $this->assertTrue(Plugins::add($zipPath));

        // comprobamos que el archivo se ha eliminado
        $this->assertFileDoesNotExist($filePath);

        // comprobamos que el archivo facturascripts.ini se ha restaurado
        $this->assertFileExists($iniPath);
        $this->assertStringNotContainsString('test = 2', file_get_contents($iniPath));

        // comprobamos que sigue instalado
        $plugin = Plugins::get('TestPlugin2');
        $this->assertTrue($plugin->installed);
        $this->assertTrue($plugin->enabled);

        // comprobamos que se ha marcado ejecutar el post_enable
        $this->assertTrue($plugin->post_enable);

        // desactivamos el plugin
        $this->assertTrue(Plugins::disable('TestPlugin2'));

        // eliminamos el plugin
        $this->assertTrue(Plugins::remove('TestPlugin2'));

        // comprobamos que se ha eliminado el plugin
        $this->assertNull(Plugins::get('TestPlugin2'));
    }

    public function testPlugin3(): void
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
        $this->assertIsArray($plugin->require);
        $this->assertContains('TestPlugin2', $plugin->require);

        // comprobamos que no podemos activar el plugin porque no tenemos activado el plugin TestPlugin2
        $this->assertFalse(Plugins::enable('TestPlugin3'));

        // añadimos y activamos el plugin TestPlugin2
        $this->assertTrue(Plugins::add(__DIR__ . '/../__files/TestPlugin2.zip'));
        $this->assertTrue(Plugins::enable('TestPlugin2'));

        // comprobamos que ahora podemos activar el plugin TestPlugin3
        $this->assertTrue(Plugins::enable('TestPlugin3'));

        // comprobamos que se han activado los dos plugins
        $this->assertContains('TestPlugin2', Plugins::enabled());
        $this->assertContains('TestPlugin3', Plugins::enabled());

        // comprobamos el hash md5 del archivo Dinamic/XMLView/TestFile.xml
        // para ver que es el del plugin TestPlugin3 y no el del plugin TestPlugin2
        $this->assertEquals('a0e5e1f1959a473c5f125d425f240ca2', md5_file(Tools::folder() . '/Dinamic/XMLView/TestFile.xml'));

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

    public function testPluginMinVersion2028(): void
    {
        // comprobamos que no podemos añadir el plugin
        $zipPath = __DIR__ . '/../__files/PluginMinVersion2028.zip';
        $this->assertFalse(Plugins::add($zipPath));

        // leemos los datos del plugin
        $plugin = Plugin::getFromZip($zipPath);
        $this->assertEquals('PluginMinVersion2028', $plugin->name);
        $this->assertEquals('Plugin with min_version = 2028', $plugin->description);
        $this->assertEquals(2, $plugin->version);
        $this->assertEquals(2028, $plugin->min_version);
    }

    public function testPluginMinPHP8(): void
    {
        $zipPath = __DIR__ . '/../__files/PluginMinPHP8.zip';

        // si la versión de PHP es menor que 8, no podemos añadir el plugin
        if (version_compare(PHP_VERSION, '8.0.0') < 0) {
            $this->assertFalse(Plugins::add($zipPath));

            // leemos los datos del plugin
            $plugin = Plugin::getFromZip($zipPath);
            $this->assertEquals('PluginMinPHP8', $plugin->name);
            $this->assertEquals('Plugin with min_php = 8', $plugin->description);
            $this->assertEquals(3, $plugin->version);
            $this->assertEquals(2023, $plugin->min_version);
            $this->assertEquals('8', $plugin->min_php);
            return;
        }

        // la versión de PHP es mayor o igual que 8, podemos añadir el plugin
        $this->assertTrue(Plugins::add($zipPath));

        // comprobamos que podemos activar el plugin
        $this->assertTrue(Plugins::enable('PluginMinPHP8'));

        // comprobamos que podemos eliminar el plugin
        $this->assertTrue(Plugins::disable('PluginMinPHP8'));
        $this->assertTrue(Plugins::remove('PluginMinPHP8'));
    }

    public function testPluginRequirePHP(): void
    {
        // comprobamos que podemos añadir el plugin
        $zipPath = __DIR__ . '/../__files/PluginRequirePHP.zip';
        $this->assertTrue(Plugins::add($zipPath));

        // comprobamos los datos del plugin
        $plugin = Plugins::get('PluginRequirePHP');
        $this->assertEquals('PluginRequirePHP', $plugin->name);
        $this->assertEquals("Plugin with require_php = 'yolo,yolo2'", $plugin->description);
        $this->assertEquals(1.3, $plugin->version);
        $this->assertEquals(2023, $plugin->min_version);
        $this->assertIsArray($plugin->require_php);
        $this->assertContains('yolo', $plugin->require_php);
        $this->assertContains('yolo2', $plugin->require_php);

        // comprobamos que no podemos activar el plugin
        $this->assertFalse(Plugins::enable('PluginRequirePHP'));

        // comprobamos que podemos eliminar el plugin
        $this->assertTrue(Plugins::remove('PluginRequirePHP'));
    }

    public function testPluginRenameFolder(): void
    {
        // comprobamos que podemos añadir el plugin
        $zipPath = __DIR__ . '/../__files/RenameFolder.zip';
        $this->assertTrue(Plugins::add($zipPath));

        // comprobamos los datos del plugin
        $plugin = Plugins::get('RenameFolder');
        $this->assertEquals('RenameFolder', $plugin->name);
        $this->assertEquals('RenameFolder', $plugin->folder);

        // comprobamos que la carpeta del plugin se ha renombrado
        $this->assertTrue(is_dir(Plugins::folder() . '/RenameFolder'));

        // comprobamos que podemos activar el plugin
        $this->assertTrue(Plugins::enable('RenameFolder'));

        // comprobamos que podemos desactivar el plugin
        $this->assertTrue(Plugins::disable('RenameFolder'));

        // comprobamos que podemos eliminar el plugin
        $this->assertTrue(Plugins::remove('RenameFolder'));

        // comprobamos que la carpeta del plugin se ha eliminado
        $this->assertFalse(is_dir(Plugins::folder() . '/RenameFolder'));

        // comprobamos que ya no podemos obtener los datos del plugin
        $this->assertNull(Plugins::get('RenameFolder'));
    }

    public function testPluginsOrder(): void
    {
        // añadimos los plugins TestPlugin3, TestPlugin2 y TestPlugin4
        $this->assertTrue(Plugins::add(__DIR__ . '/../__files/TestPlugin3.zip'));
        $this->assertTrue(Plugins::add(__DIR__ . '/../__files/TestPlugin2.zip'));
        $this->assertTrue(Plugins::add(__DIR__ . '/../__files/TestPlugin4.zip'));

        // activamos los plugins TestPlugin2, TestPlugin4 y TestPlugin3
        $this->assertTrue(Plugins::enable('TestPlugin2'));
        $this->assertTrue(Plugins::enable('TestPlugin4'));
        $this->assertTrue(Plugins::enable('TestPlugin3'));

        // comprobamos que los plugins están en el orden correcto
        $this->assertEquals(['TestPlugin2', 'TestPlugin4', 'TestPlugin3'], Plugins::enabled());

        $lstByOrder = Plugins::list(true, 'order');
        $count = count($lstByOrder);
        $this->assertEquals('TestPlugin2', $lstByOrder[$count - 3]->name);
        $this->assertEquals('TestPlugin4', $lstByOrder[$count - 2]->name);
        $this->assertEquals('TestPlugin3', $lstByOrder[$count - 1]->name);

        // desactivamos todos los plugins
        foreach (Plugins::enabled() as $pluginName) {
            $this->assertTrue(Plugins::disable($pluginName));
        }

        // activamos los plugins TestPlugin4, TestPlugin2 y TestPlugin3
        $this->assertTrue(Plugins::enable('TestPlugin4'));
        $this->assertTrue(Plugins::enable('TestPlugin2'));
        $this->assertTrue(Plugins::enable('TestPlugin3'));

        // comprobamos que los plugins están en el orden correcto
        $this->assertEquals(['TestPlugin4', 'TestPlugin2', 'TestPlugin3'], Plugins::enabled());

        // desactivamos todos los plugins
        foreach (Plugins::enabled() as $pluginName) {
            $this->assertTrue(Plugins::disable($pluginName));
        }

        // eliminamos los plugins
        $this->assertTrue(Plugins::remove('TestPlugin2'));
        $this->assertTrue(Plugins::remove('TestPlugin3'));
        $this->assertTrue(Plugins::remove('TestPlugin4'));
    }

    public function testEnableMissingPlugin(): void
    {
        // activamos un plugin que no existe
        $this->assertFalse(Plugins::enable('MissingPlugin'));
    }

    public function testDisableMissingPlugin(): void
    {
        // desactivamos un plugin que no existe
        $this->assertFalse(Plugins::disable('MissingPlugin'));
    }

    public function testIsInstalled(): void
    {
        // Instalamos un plugin
        Plugins::add(__DIR__ . '/../__files/TestPlugin2.zip');

        // Comprobamos que se encuentra instalado
        $result = Plugins::isInstalled('TestPlugin2');
        $this->assertTrue($result);

        // Desinstalamos el plugin
        Plugins::remove('TestPlugin2');

        // Comprobamos que no se encuentra instalado
        $result = Plugins::isInstalled('TestPlugin2');
        $this->assertFalse($result);
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
