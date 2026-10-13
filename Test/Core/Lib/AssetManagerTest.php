<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Lib;

use FacturaScripts\Core\Lib\AssetManager;
use FacturaScripts\Core\Tools;
use PHPUnit\Framework\TestCase;

class AssetManagerTest extends TestCase
{
    public function testClear(): void
    {
        AssetManager::clear();
        $this->assertEmpty(AssetManager::get('css'));
        $this->assertEmpty(AssetManager::getCss());
        $this->assertEmpty(AssetManager::get('js'));
        $this->assertEmpty(AssetManager::getJs());
    }

    public function testAdd(): void
    {
        AssetManager::clear();

        AssetManager::add('css', 'test.css');
        $this->assertCount(1, AssetManager::get('css'));
        $this->assertCount(1, AssetManager::getCss());
        $this->assertEmpty(AssetManager::get('js'));
        $this->assertEmpty(AssetManager::getJs());

        AssetManager::addCss('test2.css');
        $this->assertCount(2, AssetManager::get('css'));
        $this->assertCount(2, AssetManager::getCss());
        $this->assertEmpty(AssetManager::get('js'));
        $this->assertEmpty(AssetManager::getJs());

        AssetManager::addJs('test.js');
        $this->assertCount(1, AssetManager::get('js'));
        $this->assertCount(1, AssetManager::getJs());
        $this->assertCount(2, AssetManager::get('css'));
        $this->assertCount(2, AssetManager::getCss());

        AssetManager::add('js', 'test2.js');
        $this->assertCount(2, AssetManager::get('js'));
        $this->assertCount(2, AssetManager::getJs());
        $this->assertCount(2, AssetManager::get('css'));
        $this->assertCount(2, AssetManager::getCss());
    }

    public function testPriorities(): void
    {
        AssetManager::clear();

        $version = AssetManager::version();

        AssetManager::add('css', 'test.css', 10);
        AssetManager::add('css', 'test2.css', 20);
        $this->assertEquals('test2.css?v=' . $version, AssetManager::getCss()[0]);
        $this->assertEquals('test.css?v=' . $version, AssetManager::getCss()[1]);

        AssetManager::add('js', 'test.js', 10);
        AssetManager::add('js', 'test2.js', 20);
        $this->assertEquals('test2.js?v=' . $version, AssetManager::getJs()[0]);
        $this->assertEquals('test.js?v=' . $version, AssetManager::getJs()[1]);
    }

    public function testCombinedCssChangesAfterDeploy(): void
    {
        AssetManager::clear();
        Tools::folderCheckOrCreate(Tools::folder('Dinamic', 'Assets', 'CSS'));
        Tools::folderCheckOrCreate(Tools::folder('MyFiles', 'Tmp'));
        $source = Tools::folder('MyFiles', 'Tmp', 'asset-manager-test.css');

        // combinamos un archivo
        file_put_contents($source, '.old{color:red}');
        AssetManager::addCss($source);
        $firstUrl = AssetManager::combinedCss();
        $firstFile = Tools::folder('Dinamic', 'Assets', 'CSS', basename($firstUrl));
        $this->assertStringContainsString('.old{color:red}', file_get_contents($firstFile));

        // modificamos el archivo el mismo día y desplegamos
        file_put_contents($source, '.new{color:blue}');
        $this->assertTrue(AssetManager::updateVersion());
        AssetManager::addCss($source);
        $secondUrl = AssetManager::combinedCss();
        $secondFile = Tools::folder('Dinamic', 'Assets', 'CSS', basename($secondUrl));
        $this->assertNotEquals($firstUrl, $secondUrl);
        $this->assertStringContainsString('.new{color:blue}', file_get_contents($secondFile));

        unlink($source);
        unlink($firstFile);
        unlink($secondFile);
    }

    public function testDuplicatesWithDifferentVersion(): void
    {
        AssetManager::clear();

        AssetManager::addJs('Dinamic/Assets/JS/Test.js');
        AssetManager::addJs('Dinamic/Assets/JS/Test.js?v=01-01-2026');
        $this->assertCount(1, AssetManager::getJs());
    }

    public function testExternalAssetsAreNotVersioned(): void
    {
        AssetManager::clear();

        AssetManager::addJs('https://example.com/test.js?v=1');
        AssetManager::addJs('//example.com/test2.js');
        AssetManager::addCss('http://example.com/test.css');
        $this->assertEquals(['https://example.com/test.js?v=1', '//example.com/test2.js'], AssetManager::getJs());
        $this->assertEquals(['http://example.com/test.css'], AssetManager::getCss());
    }

    public function testLocalAssetsUseDeployVersion(): void
    {
        AssetManager::clear();
        $version = AssetManager::version();
        $this->assertNotEmpty($version);

        // la versión anterior se sustituye y se conservan los demás parámetros
        AssetManager::addJs('/Dinamic/Assets/JS/Test.js?v=01-01-2026');
        AssetManager::addJs('/Dinamic/Assets/JS/Test2.js?lang=es&v=3&debug');
        AssetManager::addJsModule('/Dinamic/Assets/JS/Test.mjs');
        $this->assertEquals([
            '/Dinamic/Assets/JS/Test.js?v=' . $version,
            '/Dinamic/Assets/JS/Test2.js?lang=es&debug&v=' . $version,
        ], AssetManager::getJs());
        $this->assertEquals(['/Dinamic/Assets/JS/Test.mjs?v=' . $version], AssetManager::getJsModules());
    }

    public function testUpdateVersion(): void
    {
        $oldVersion = AssetManager::version();

        $this->assertTrue(AssetManager::updateVersion());
        $newVersion = AssetManager::version();
        $this->assertNotEquals($oldVersion, $newVersion);

        // la versión se guarda para las siguientes peticiones
        $filePath = Tools::folder('MyFiles', AssetManager::VERSION_FILE);
        $this->assertEquals($newVersion, file_get_contents($filePath));

        AssetManager::clear();
        AssetManager::addCss('/Dinamic/Assets/CSS/Test.css');
        $this->assertEquals(['/Dinamic/Assets/CSS/Test.css?v=' . $newVersion], AssetManager::getCss());
    }
}
