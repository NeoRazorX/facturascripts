<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Test\Core\Base\Utils;

use FacturaScripts\Core\Base\FileManager;
use PHPUnit\Framework\TestCase;

/**
 * Class to test common methods to manipulate files and folders.
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class FileManagerTest extends TestCase
{

    /**
     * @var FileManager
     */
    protected $object;

    public function testCreateWritableFolder()
    {
        $this::assertTrue(
            @mkdir(\FS_FOLDER . '/MyFiles/TestWritable/Test1/Test2/Test3', 0775, true), 'Recursive folder creation fails.'
        );
    }

    /**
     * @covers \FacturaScripts\Core\Base\FileManager::recurseCopy
     */
    public function testRecurseCopy()
    {
        $this::assertTrue(
            $this->object::recurseCopy(\FS_FOLDER . '/MyFiles/TestWritable', \FS_FOLDER . '/MyFiles/TestWritable2')
        );
    }

    public function testCreateNonWriteableFolder()
    {
        $this::assertFalse(
            @mkdir(\FS_FOLDER . '/MyFiles/TestNonWritable/Test1/Test2/Test3', 0555, true), 'Recursive folder creation fails.'
        );
    }

    /**
     * @covers \FacturaScripts\Core\Base\FileManager::scanFolder
     */
    public function testScanFolder()
    {
        $this::assertEquals(
            $this->object::scanFolder(\FS_FOLDER . '/MyFiles/TestWritable'), $this->object::scanFolder(\FS_FOLDER . '/MyFiles/TestWritable2'), 'Folder not equals'
        );
        $this::assertNotEquals(
            $this->object::scanFolder(\FS_FOLDER . '/MyFiles/TestNonWritable'), $this->object::scanFolder(\FS_FOLDER . '/MyFiles/TestWritable2'), 'Folder are equals'
        );
    }

    /**
     * @covers \FacturaScripts\Core\Base\FileManager::delTree
     */
    public function testDelTreeWritableFolder()
    {
        $this::assertTrue($this->object::delTree(\FS_FOLDER . '/MyFiles/TestWritable'), 'Recursive delete dir fails.');
        $this::assertTrue($this->object::delTree(\FS_FOLDER . '/MyFiles/TestWritable2'), 'Recursive delete dir fails.');
    }

    /**
     * @covers \FacturaScripts\Core\Base\FileManager::delTree
     */
    public function testDelTreeNonWritableFolder()
    {
        $this::assertTrue(
            $this->object::delTree(\FS_FOLDER . '/MyFiles/TestNonWritable'), 'Recursive delete dir fails.'
        );
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
