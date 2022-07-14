<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Class to verify that all JSON files from translation are correct.
 *
 * @author Carlos Carlos Garcia Gomez <carlos@facturascripts.com>
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

    protected function setUp(): void
    {
        $this->basePath = FS_FOLDER . '/Core/Translation/';
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
}
