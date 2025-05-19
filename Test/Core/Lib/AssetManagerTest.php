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

namespace FacturaScripts\Test\Core\Lib;

use FacturaScripts\Core\Lib\AssetManager;
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

        AssetManager::add('css', 'test.css', 10);
        AssetManager::add('css', 'test2.css', 20);
        $this->assertEquals('test2.css', AssetManager::getCss()[0]);
        $this->assertEquals('test.css', AssetManager::getCss()[1]);

        AssetManager::add('js', 'test.js', 10);
        AssetManager::add('js', 'test2.js', 20);
        $this->assertEquals('test2.js', AssetManager::getJs()[0]);
        $this->assertEquals('test.js', AssetManager::getJs()[1]);
    }
}
