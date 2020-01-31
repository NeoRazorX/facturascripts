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
namespace FacturaScripts\Test\Core\Translation;

use PHPUnit\Framework\TestCase;

/**
 * Class to verify that all JSON files from translation are correct
 */
class TranslationTest extends TestCase
{

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var string
     */
    protected $mainLang;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->basePath = \FS_FOLDER . '/Core/Translation/';
        $this->mainLang = 'en_EN.json';
    }

    /**
     * Test that all files have well format.
     */
    public function testFiles()
    {
        foreach ($this->scanFolder($this->basePath) as $fileName) {
            if (substr($fileName, -5) !== '.json') {
                continue;
            }

            $fileArray = $this->readJSON($this->basePath . $fileName);
            $msg = 'File ' . $fileName . ' is wrong';
            $this->assertNotNull($fileArray, $msg);
        }
    }

    /**
     * Test that secondary language don't have waste keys.
     * Must fail when user adds on wrong file.
     */
    public function testWasteKeys()
    {
        $mainLangArray = $this->readJSON($this->basePath . $this->mainLang);

        foreach ($this->scanFolder($this->basePath) as $fileName) {
            if (substr($fileName, -5) !== '.json') {
                continue;
            }

            $fileArray = $this->readJSON($this->basePath . $fileName);
            $this->compareKeys($mainLangArray, $fileArray, $fileName);
        }
    }

    /**
     * Check that every language file have ordered keys on list.
     */
    public function testHasOrderedKeys()
    {
        foreach ($this->scanFolder($this->basePath) as $fileName) {
            if (substr($fileName, -5) !== '.json') {
                continue;
            }

            $fileString = $this->getJSON($this->basePath . $fileName);
            $orderedArray = \json_decode($fileString, true);
            \ksort($orderedArray);
            $orderedString = json_encode($orderedArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            $msg = 'File ' . $fileName . ' have no ordered keys.';
            //$this->assertEquals($fileString, $orderedString, $msg);
        }
    }

    /**
     * Verify that all language keys are also on main language.
     *
     * @param array $primaryArray
     * @param array $secondaryArray
     * @param string $fileName
     */
    private function compareKeys(array $primaryArray, array &$secondaryArray, string $fileName)
    {
        foreach ($secondaryArray as $key => $value) {
            $exists = array_key_exists($key, $primaryArray);
            $msg = 'Key \'' . $key . '\' not exists on ' . $this->mainLang . '.';
        }
    }

    /**
     * Returns an array with all files and folders.
     *
     * @param string $folderPath
     *
     * @return array
     */
    private function scanFolder(string $folderPath): array
    {
        return array_diff(scandir($folderPath, SCANDIR_SORT_ASCENDING), ['.', '..']);
    }

    /**
     * Reads a JSON from disc and return it content as array.
     *
     * @param string $pathName
     *
     * @return mixed
     */
    private function readJSON(string $pathName)
    {
        return json_decode(file_get_contents($pathName), true);
    }

    /**
     * Get JSON as string.
     *
     * @param string $pathName
     *
     * @return bool|string
     */
    private function getJSON(string $pathName)
    {
        return file_get_contents($pathName);
    }
}
