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

use FacturaScripts\Core\Model\Settings;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Divisa;
use PHPUnit\Framework\TestCase;

final class ToolsTest extends TestCase
{
    public function testAscii(): void
    {
        $this->assertEquals(' aeiou aeiou aeiou aeiou ao cn ', Tools::ascii(' aeiou áéíóú àèìòù âêîôû ãõ çñ '));
    }

    public function testConfig(): void
    {
        $this->assertEquals(FS_FOLDER, Tools::config('folder'));
        $this->assertEquals(FS_FOLDER, Tools::config('FOLDER'));
        $this->assertEquals(FS_FOLDER, Tools::config('FS_FOLDER'));
        $this->assertEquals(FS_LANG, Tools::config('lang'));

        // comprobamos el default
        $this->assertEquals('default', Tools::config('test123', 'default'));
        $this->assertNull(Tools::config('test1234'));
    }

    public function testDateFunctions(): void
    {
        $date = '01-01-2019';
        $time = strtotime($date);

        $dateTime = '01-01-2019 12:00:00';
        $time2 = strtotime($dateTime);

        $date3 = '2020-10-07';
        $tim3 = strtotime($date3);

        $dateTime2 = '2020-05-17 12:00:00';
        $time4 = strtotime($dateTime2);

        $this->assertEquals($date, Tools::date($date));
        $this->assertEquals($date, Tools::timeToDate($time));
        $this->assertEquals($date, Tools::date($dateTime));
        $this->assertEquals($date, Tools::timeToDate($time2));
        $this->assertEquals('07-10-2020', Tools::date($date3));
        $this->assertEquals('07-10-2020', Tools::timeToDate($tim3));
        $this->assertEquals('09-10-2020', Tools::dateOperation($date3, '+2 days'));

        $this->assertEquals($dateTime, Tools::dateTime($dateTime));
        $this->assertEquals($dateTime, Tools::timeToDateTime($time2));
        $this->assertEquals('17-05-2020 12:00:00', Tools::dateTime($dateTime2));
        $this->assertEquals('17-05-2020 12:00:00', Tools::timeToDateTime($time4));
        $this->assertEquals('19-05-2020 13:00:00', Tools::dateTimeOperation($dateTime2, '+2 days +1 hour'));
    }

    public function testFolderFunctions(): void
    {
        $this->assertEquals(FS_FOLDER, Tools::folder());
        $this->assertEquals(FS_FOLDER . DIRECTORY_SEPARATOR . 'Test', Tools::folder('Test'));

        // comprobamos que elimina barras al inicio y al final
        $this->assertEquals(FS_FOLDER . DIRECTORY_SEPARATOR . 'Test', Tools::folder('/Test'));
        $this->assertEquals(FS_FOLDER . DIRECTORY_SEPARATOR . 'Test', Tools::folder('/Test/'));
        $this->assertEquals(FS_FOLDER . DIRECTORY_SEPARATOR . 'Test', Tools::folder('Test/'));
        $this->assertEquals(FS_FOLDER . DIRECTORY_SEPARATOR . 'Test', Tools::folder('\\Test'));
        $this->assertEquals(FS_FOLDER . DIRECTORY_SEPARATOR . 'Test', Tools::folder('\\Test\\'));
        $this->assertEquals(FS_FOLDER . DIRECTORY_SEPARATOR . 'Test', Tools::folder('Test\\'));
        $expected = FS_FOLDER . DIRECTORY_SEPARATOR . 'Test1' . DIRECTORY_SEPARATOR . 'test2';
        $this->assertEquals($expected, Tools::folder('/Test1/', '/test2'));

        // creamos la carpeta MyFiles/Test/Folder1
        $folder1 = Tools::folder('MyFiles', 'Test', 'Folder1');
        $this->assertTrue(Tools::folderCheckOrCreate($folder1));

        // creamos 3 archivos en la carpeta MyFiles/Test
        file_put_contents(Tools::folder('MyFiles', 'Test', 'file1.txt'), 'test');
        file_put_contents(Tools::folder('MyFiles', 'Test', 'file2.txt'), 'test');
        file_put_contents(Tools::folder('MyFiles', 'Test', 'file3.txt'), 'test');

        // creamos un archivo en la carpeta MyFiles/Test/Folder1
        file_put_contents(Tools::folder('MyFiles', 'Test', 'Folder1', 'file4.txt'), 'test');

        // comprobamos que existen los archivos
        $fileListRecursive = ['Folder1', 'Folder1' . DIRECTORY_SEPARATOR . 'file4.txt', 'file1.txt', 'file2.txt', 'file3.txt'];
        $this->assertEquals($fileListRecursive, Tools::folderScan('MyFiles/Test', true));

        // sin recursividad
        $fileList = ['Folder1', 'file1.txt', 'file2.txt', 'file3.txt'];
        $results1 = Tools::folderScan('MyFiles/Test');
        $this->assertEquals($fileList, array_values($results1));

        // excluyendo file1.txt
        $fileList = ['Folder1', 'file2.txt', 'file3.txt'];
        $results2 = Tools::folderScan('MyFiles/Test', false, ['file1.txt']);
        $this->assertEquals($fileList, array_values($results2));

        // copiamos la carpeta Test a Test2
        $this->assertTrue(Tools::folderCopy('MyFiles/Test', 'MyFiles/Test2'));

        // comprobamos que existen los archivos
        $this->assertEquals($fileListRecursive, Tools::folderScan('MyFiles/Test2', true));

        // eliminamos la carpeta MyFiles/Test
        $this->assertTrue(Tools::folderDelete('MyFiles/Test'));

        // comprobamos que no existen los archivos
        $this->assertFalse(file_exists(Tools::folder('MyFiles', 'Test')));
    }

    public function testHtmlFunctions(): void
    {
        // escapamos y des-escapamos el html
        $html = '<p class=\'test\'>Test</p><script>alert("test");</script>';
        $noHtml = '&lt;p class=&#39;test&#39;&gt;Test&lt;/p&gt;&lt;script&gt;alert(&quot;test&quot;);&lt;/script&gt;';
        $this->assertEquals($noHtml, Tools::noHtml($html));
        $this->assertEquals($html, Tools::fixHtml($noHtml));

        // comprobamos que podemos pasar un null a las funciones
        $this->assertNull(Tools::noHtml(null));
        $this->assertNull(Tools::fixHtml(null));
    }

    public function testRandomString(): void
    {
        $this->assertEquals(10, strlen(Tools::randomString(10)));
        $this->assertEquals(20, strlen(Tools::randomString(20)));
        $this->assertEquals(30, strlen(Tools::randomString(30)));
        $this->assertEquals(40, strlen(Tools::randomString(40)));
        $this->assertEquals(50, strlen(Tools::randomString(50)));
    }

    public function testSettings(): void
    {
        $this->assertEquals(Tools::settings('default', 'codpais'), Tools::settings('default', 'codpais'));

        // nos guardamos el valor actual
        $value = Tools::settings('default', 'codpais');

        // cambiamos el valor
        Tools::settingsSet('default', 'codpais', '222');

        // comprobamos que se ha cambiado
        $this->assertEquals('222', Tools::settings('default', 'codpais'));

        // guardamos los cambios
        $this->assertTrue(Tools::settingsSave());

        // comprobamos que se ha cambiado
        $settings = new Settings();
        $this->assertTrue($settings->loadFromCode('default'));
        $this->assertEquals('222', $settings->properties['codpais']);

        // volvemos a poner el valor original
        Tools::settingsSet('default', 'codpais', $value);

        // guardamos los cambios
        $this->assertTrue(Tools::settingsSave());

        // comprobamos que se ha cambiado
        $settings->loadFromCode('default');
        $this->assertEquals($value, $settings->properties['codpais']);
    }

    public function testSettingsClear(): void
    {
        // nos guardamos el valor actual
        $value = Tools::settings('default', 'codpais');

        // cambiamos el valor
        Tools::settingsSet('default', 'codpais', '666');

        // comprobamos que se ha cambiado
        $this->assertEquals('666', Tools::settings('default', 'codpais'));
        $this->assertNotEquals($value, Tools::settings('default', 'codpais'));

        // limpiamos la cache
        Tools::settingsClear();

        // comprobamos que vuelve a ser el valor original
        $this->assertEquals($value, Tools::settings('default', 'codpais'));
    }

    public function testSlug(): void
    {
        $text = ' aeiou áéíóú--àèìòù  âêîôû ãõ çñ ';
        $this->assertEquals('aeiou-aeiou-aeiou-aeiou-ao-cn', Tools::slug($text));
        $this->assertEquals('aeiou_aeiou_aeiou_aeiou_ao_cn', Tools::slug($text, '_'));
        $this->assertEquals('aeiou_aeio', Tools::slug($text, '_', 10));
    }

    public function testTextBreak(): void
    {
        // acortamos el texto de varias formas
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

        // comprobamos que podemos pasar un null a la función
        $this->assertEquals('', Tools::textBreak(null));
    }

    public function testBytes(): void
    {
        Tools::settingsSet('default', 'decimal_separator', '.');
        Tools::settingsSet('default', 'thousands_separator', '_');

        $this->assertEquals('0 bytes', Tools::bytes(0, 0));
        $this->assertEquals('1.0 byte', Tools::bytes(1, 1));
        $this->assertEquals('2.00 bytes', Tools::bytes(2));
        $this->assertEquals('1.0 KB', Tools::bytes(1025, 1));
        $this->assertEquals('1 MB', Tools::bytes(1048577, 0));
        $this->assertEquals('1.00 GB', Tools::bytes(1073741825));
        $this->assertEquals('1_024.00 GB', Tools::bytes(1099511627776));

        $this->assertEquals('0 bytes', Tools::bytes(null, 0));
    }

    public function testMoney(): void
    {
        // ponemos el número de decimales a 2 y la divisa EUR
        Tools::settingsSet('default', 'decimals', 2);
        Tools::settingsSet('default', 'coddivisa', 'EUR');
        Tools::settingsSet('default', 'decimal_separator', '.');
        Tools::settingsSet('default', 'thousands_separator', ' ');
        Tools::settingsSet('default', 'currency_position', 'right');

        $this->assertEquals('0.00 €', Tools::money(0));
        $this->assertEquals('1.00 €', Tools::money(1));
        $this->assertEquals('1.23 €', Tools::money(1.23));
        $this->assertEquals('1.23 €', Tools::money(1.234));
        $this->assertEquals('1 234.56 €', Tools::money(1234.56));
        $this->assertEquals('0.00 €', Tools::money(null));
        $this->assertEquals('-1.00 €', Tools::money(-1));
        $this->assertEquals('-1.23 €', Tools::money(-1.23));
        $this->assertEquals('-1.23 €', Tools::money(-1.234));
        $this->assertEquals('-1 234.56 €', Tools::money(-1234.56));
        $this->assertEquals('-1 234.4 €', Tools::money(-1234.40, '', 1));
        $this->assertEquals('-1 234 €', Tools::money(-1234.40, '', 0));

        // probamos con otra divisa
        $this->assertEquals('1.23 ?', Tools::money(1.234, 'TES'));

        // creamos una divisa nueva
        $divisa = new Divisa();
        $divisa->coddivisa = 'TES';
        $divisa->descripcion = 'Test';
        $divisa->simbolo = 'X';
        $this->assertTrue($divisa->save());

        // probamos con la divisa
        $this->assertEquals('1.23 X', Tools::money(1.234, 'TES'));

        // eliminamos la divisa
        $this->assertTrue($divisa->delete());
    }

    public function testNumber(): void
    {
        $this->assertEquals('0.00', Tools::number(0, 2));
        $this->assertEquals('1.00', Tools::number(1, 2));
        $this->assertEquals('1.0', Tools::number(1.00, 1));
        $this->assertEquals('1', Tools::number(1.00, 0));
        $this->assertEquals('0.00', Tools::number(null, 2));

        $this->assertEquals('1.23', Tools::number(1.234, 2));
        $this->assertEquals('1.234', Tools::number(1.234, 3));
        $this->assertEquals('1.23', Tools::number(1.234, 2));
    }
}
