<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\ExportManager;
use FacturaScripts\Core\Tools;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class ExportManagerTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // el motor PDF guarda ahí las fuentes cacheadas
        Tools::folderCheckOrCreate(Tools::folder('MyFiles', 'Cache'));
    }

    public function testPdfOnePage(): void
    {
        $manager = new ExportManager();
        $manager->newDoc('PDF', 'test');
        $manager->addTablePage(['a', 'b'], [['a' => '1', 'b' => '2']]);
        $manager->addTablePage(['a', 'b'], [['a' => '3', 'b' => '4']]);

        $this->assertSame(1, self::countPdfPages($manager->getDoc()));
    }

    public function testPdfNewPage(): void
    {
        $manager = new ExportManager();
        $manager->newDoc('PDF', 'test');
        $manager->addTablePage(['a', 'b'], [['a' => '1', 'b' => '2']]);
        $manager->newPage();
        $manager->addTablePage(['a', 'b'], [['a' => '3', 'b' => '4']]);

        $this->assertSame(2, self::countPdfPages($manager->getDoc()));
    }

    public function testCsvNewPage(): void
    {
        $manager = new ExportManager();
        $manager->newDoc('CSV', 'test');
        $manager->addTablePage(['a', 'b'], [['a' => '1', 'b' => '2']]);
        $manager->newPage();
        $manager->addTablePage(['a', 'b'], [['a' => '3', 'b' => '4']]);

        $doc = $manager->getDoc();
        $this->assertStringContainsString('"1";"2"', $doc);
        $this->assertStringContainsString('"3";"4"', $doc);
    }

    public function testXlsNewPage(): void
    {
        $manager = new ExportManager();
        $manager->newDoc('XLS', 'test');
        $manager->addTablePage(['a', 'b'], [['a' => 'uno', 'b' => 'dos']]);
        $manager->newPage();
        $manager->addTablePage(['a', 'b'], [['a' => 'tres', 'b' => 'cuatro']]);

        $path = tempnam(sys_get_temp_dir(), 'fs-export-manager-');
        file_put_contents($path, $manager->getDoc());

        try {
            $zip = new ZipArchive();
            $this->assertTrue($zip->open($path));
            $strings = $zip->getFromName('xl/sharedStrings.xml');
            $sheets = $zip->getFromName('xl/worksheets/sheet1.xml') . $zip->getFromName('xl/worksheets/sheet2.xml');
            $zip->close();
        } finally {
            unlink($path);
        }

        $content = $strings . $sheets;
        $this->assertStringContainsString('uno', $content);
        $this->assertStringContainsString('cuatro', $content);
    }

    private static function countPdfPages(string $pdf): int
    {
        // cuenta los objetos /Type /Page, sin contar el nodo /Type /Pages
        return preg_match_all('/\/Type\s*\/Page(?![a-zA-Z])/', $pdf);
    }
}
