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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Test\Core\Base\Utils;

use FacturaScripts\Core\Base\IntegrityCheck;
use PHPUnit\Framework\TestCase;

/**
 * Class to test integrity files of FacturaScripts Core
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class IntegrityCheckTest extends TestCase
{

    /**
     * @var IntegrityCheck
     */
    protected $object;

    /**
     * Generate the integrity.json.
     *
     * @covers \FacturaScripts\Core\Base\IntegrityCheck::saveIntegrity()
     */
    public function testSaveIntegrity()
    {
        $this->assertTrue($this->object::saveIntegrity());
        $this->assertFileExists($this->object::INTEGRITY_FILE, 'File not exists');
    }

    /**
     * Generate the integrity.json.
     *
     * @covers \FacturaScripts\Core\Base\IntegrityCheck::saveIntegrity()
     */
    public function testSaveUserIntegrity()
    {
        $this->assertTrue($this->object::saveIntegrity($this->object::INTEGRITY_USER_FILE));
        $this->assertFileExists($this->object::INTEGRITY_USER_FILE, 'File not exists');
    }

    /**
     * Read the integrity.json.
     *
     * @covers \FacturaScripts\Core\Base\IntegrityCheck::getIntegrityFiles()
     */
    public function testGetIntegrityFiles()
    {
        $this->assertNotEmpty($this->object::getIntegrityFiles(), 'integrity.json is empty');
    }

    /**
     * Load the integrity.json file.
     *
     * @covers \FacturaScripts\Core\Base\IntegrityCheck::loadIntegrity()
     */
    public function testLoadIntegrityFiles()
    {
        $this->assertNotEmpty($this->object::loadIntegrity(), 'Is empty');
    }

    /**
     * Try to load and generate the user integrity file.
     */
    public function testUserLoadIntegrityFiles()
    {
        $this->assertNotEmpty($this->object::loadIntegrity($this->object::INTEGRITY_USER_FILE), 'Is empty');
    }

    /**
     * Test that hash file are the same, without reading the content.
     *
     * @covers \FacturaScripts\Core\Base\IntegrityCheck::getFileHash()
     */
    public function testGetFileHash()
    {
        $this->assertNotEmpty(
            $this->object::getFileHash(), 'Is not a string "' . print_r($this->object::getFileHash() . '"', true)
        );
        $this->assertNotEmpty(
            $this->object::getFileHash($this->object::INTEGRITY_USER_FILE), 'Is not a string "' . print_r($this->object::getFileHash($this->object::INTEGRITY_USER_FILE) . '"', true)
        );
    }

    /**
     * Compare the diff between integrity files.
     *
     * @covers \FacturaScripts\Core\Base\IntegrityCheck::compareIntegrity()
     */
    public function testCompareIntegrity()
    {
        $list = $this->object::compareIntegrity();
        $excludes = ['.idea', '.vscode'];
        foreach ($excludes as $exclude) {
            if (isset($list[$exclude])) {
                unset($list[$exclude]);
            }
        }

        // Must be equals
        $this->assertEmpty(
            $list, 'Integrity check test not passed. A false positive on development width IDE files? '
            . print_r($this->object::compareIntegrity(), true)
        );

        // Must be different, because were adding a dummy file
        touch(\FS_FOLDER . '/DummyFile');
        if (\file_exists(\FS_FOLDER . '/MyFiles/integrity-validation.json')) {
            unlink(\FS_FOLDER . '/MyFiles/integrity-validation.json');
        }
        $this->assertNotEmpty(
            $this->object::compareIntegrity(), 'Integrity check test not passed: "' . print_r($this->object::compareIntegrity(), true) . '"'
        );
        if (\file_exists(\FS_FOLDER . '/DummyFile')) {
            unlink(\FS_FOLDER . '/DummyFile');
        }
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new IntegrityCheck();

        // Remove it because if asserts fails don't unlink it
        if (\file_exists(\FS_FOLDER . '/DummyFile')) {
            unlink(\FS_FOLDER . '/DummyFile');
        }
    }
}
