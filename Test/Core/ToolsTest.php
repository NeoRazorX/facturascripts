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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Tools;
use PHPUnit\Framework\TestCase;

final class ToolsTest extends TestCase
{
    public function testAscii()
    {
        $this->assertEquals(' aeiou aeiou aeiou aeiou ao cn ', Tools::ascii(' aeiou áéíóú àèìòù âêîôû ãõ çñ '));
    }

    public function testConfig()
    {
        $this->assertEquals(FS_FOLDER, Tools::config('folder'));
        $this->assertEquals(FS_FOLDER, Tools::config('FOLDER'));
        $this->assertEquals(FS_FOLDER, Tools::config('FS_FOLDER'));
        $this->assertEquals(FS_LANG, Tools::config('lang'));

        // comprobamos el default
        $this->assertEquals('default', Tools::config('test123', 'default'));
        $this->assertNull(Tools::config('test1234'));
    }

    public function testFolderFunctions()
    {
        $this->assertEquals(FS_FOLDER, Tools::folder());
        $this->assertEquals(FS_FOLDER . '/Test', Tools::folder('Test'));

        // creamos la carpeta MyFiles/Test/Folder1
        $folder1 = Tools::folder('MyFiles', 'Test', 'Folder1');
        if (false === file_exists($folder1)) {
            mkdir($folder1, 0777, true);
        }

        // creamos 3 archivos en la carpeta MyFiles/Test
        file_put_contents(Tools::folder('MyFiles', 'Test', 'file1.txt'), 'test');
        file_put_contents(Tools::folder('MyFiles', 'Test', 'file2.txt'), 'test');
        file_put_contents(Tools::folder('MyFiles', 'Test', 'file3.txt'), 'test');

        // creamos un archivo en la carpeta MyFiles/Test/Folder1
        file_put_contents(Tools::folder('MyFiles', 'Test', 'Folder1', 'file4.txt'), 'test');

        // comprobamos que existen los archivos
        $fileListRecursive = ['Folder1', 'Folder1/file4.txt', 'file1.txt', 'file2.txt', 'file3.txt'];
        $this->assertEquals($fileListRecursive, Tools::folderScan('MyFiles/Test'));

        // sin recursividad
        $fileList = ['Folder1', 'file1.txt', 'file2.txt', 'file3.txt'];
        $results1 = Tools::folderScan('MyFiles/Test', false);
        $this->assertEquals($fileList, array_values($results1));

        // excluyendo file1.txt
        $fileList = ['Folder1', 'file2.txt', 'file3.txt'];
        $results2 = Tools::folderScan('MyFiles/Test', false, ['file1.txt']);
        $this->assertEquals($fileList, array_values($results2));

        // eliminamos la carpeta MyFiles/Test
        $this->assertTrue(Tools::folderDelete('MyFiles/Test'));

        // comprobamos que no existen los archivos
        $this->assertFalse(file_exists(Tools::folder('MyFiles', 'Test')));
    }

    public function testHtmlFunctions()
    {
        $html = '<p class=\'test\'>Test</p><script>alert("test");</script>';
        $noHtml = '&lt;p class=&#39;test&#39;&gt;Test&lt;/p&gt;&lt;script&gt;alert(&quot;test&quot;);&lt;/script&gt;';
        $this->assertEquals($noHtml, Tools::noHtml($html));
        $this->assertEquals($html, Tools::fixHtml($noHtml));
    }

    public function testSettings()
    {
        $this->assertEquals(AppSettings::get('default', 'codpais'), Tools::settings('default', 'codpais'));
    }

    public function testSlug()
    {
        $text = ' aeiou áéíóú--àèìòù  âêîôû ãõ çñ ';
        $this->assertEquals('aeiou-aeiou-aeiou-aeiou-ao-cn', Tools::slug($text));
        $this->assertEquals('aeiou_aeiou_aeiou_aeiou_ao_cn', Tools::slug($text, '_'));
        $this->assertEquals('aeiou_aeio', Tools::slug($text, '_', 10));
    }

    public function testTextBreak()
    {
        $text = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam auctor, nisl nec ultricies aliquet, "
            . "nisl nisl aliquam nisl, nec aliquet nisl nisl nec nisl. Nullam auctor, nisl nec ultricies aliquet, "
            . "nisl nisl aliquam nisl, nec aliquet nisl nisl nec nisl. Nullam auctor, nisl nec ultricies aliquet, "
            . "nisl nisl aliquam nisl, nec aliquet nisl nisl nec nisl. Nullam auctor, nisl nec ultricies aliquet, "
            . "nisl nisl aliquam nisl, nec aliquet nisl nisl nec nisl. Nullam auctor, nisl nec ultricies aliquet, "
            . "nisl nisl aliquam nisl, nec aliquet nisl nisl nec nisl. Nullam auctor, nisl nec ultricies aliquet, "
            . "nisl nisl aliquam nisl, nec aliquet nisl nisl nec nisl. Nullam auctor, nisl nec ultricies aliquet, "
            . "nisl nisl aliquam nisl, nec aliquet nisl nisl nec nisl. Nullam auctor, nisl nec ultricies aliquet, "
            . "nisl nisl aliquam nisl, nec aliquet nisl nisl nec nisl. Nullam auctor, nisl nec ultricies aliquet, "
            . "nisl nisl aliquam nisl, nec aliquet nisl nisl nec nisl. Nullam auctor, nisl nec ultricies aliquet, "
            . "nisl nisl aliquam nisl, nec aliquet nisl nisl nec nisl. Nullam auctor, nisl nec ultricies aliquet, "
            . "nisl nisl aliquam nisl, nec aliquet nisl nisl nec nisl. Nullam auctor, nisl nec ultricies aliquet, "
            . "nisl nisl aliquam nisl, nec aliquet nisl nisl nec nisl. Nullam auctor, nisl nec ultricies aliquet";

        $this->assertEquals("Lorem ipsum dolor sit amet, consectetur...", Tools::textBreak($text, 42));
        $this->assertEquals("Lorem ipsum dolor sit amet, consectetur...", Tools::textBreak($text, 43));
        $this->assertEquals("Lorem ipsum dolor sit amet, consectetur...", Tools::textBreak($text, 44));
        $this->assertEquals("Lorem ipsum dolor sit amet,...", Tools::textBreak($text, 30));
        $this->assertEquals("Lorem ipsum dolor sit amet,(...)", Tools::textBreak($text, 32, '(...)'));
    }
}
