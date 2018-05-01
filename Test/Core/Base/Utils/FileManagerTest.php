<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018    Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Test\Core\Base\Utils;

use FacturaScripts\Core\Base\Utils\FileManager;

/**
 * Class to test common methods to manipulate files and folders.
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class FileManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FileManager
     */
    protected $object;

    /**
     * @covers \FacturaScripts\Core\Base\Utils\FileManager::getFrom
     */
    public function testGetFrom()
    {
        $this->assertNotEmpty($this->object->getFrom(\FS_FOLDER));
    }

    /**
     * @covers \FacturaScripts\Core\Base\Utils\FileManager::getFilesFrom
     */
    public function testGetFilesFrom()
    {
        $this->assertNotEmpty($this->object->getFrom(\FS_FOLDER . '/MyFiles'));
    }

    /**
     * @covers \FacturaScripts\Core\Base\Utils\FileManager::getAllFrom
     */
    public function testGetAllFrom()
    {
        $this->assertNotEmpty($this->object->getFrom(\FS_FOLDER . '/MyFiles'));
    }

    /**
     * @covers \FacturaScripts\Core\Base\Utils\FileManager::createFolder
     */
    public function testCreateFolder()
    {
        $this->assertTrue($this->object->createFolder(\FS_FOLDER . '/MyFiles/Test1/Test2/Test3'), 'Recursive folder creation fails.');
    }

    /**
     * @covers \FacturaScripts\Core\Base\Utils\FileManager::deleteDirectory
     */
    public function testDeleteDirectory()
    {
        $this->assertTrue($this->object->deleteDirectory(\FS_FOLDER . '/MyFiles/Test1'), 'Recursive delete dir fails.');
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new FileManager();
    }
}
