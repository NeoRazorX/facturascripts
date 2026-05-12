<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Lib\Export;

use FacturaScripts\Core\Lib\Export\XLSExport;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class XLSExportTest extends TestCase
{
    public function testAddTablePageEscapesFormulas(): void
    {
        $export = new XLSExport();
        $export->newDoc('test', 0, 'es_ES');
        $export->addTablePage(['=formula'], [['=SUM(1+1)']]);

        $path = tempnam(sys_get_temp_dir(), 'fs-xls-export-');
        file_put_contents($path, $export->getDoc());

        try {
            $zip = new ZipArchive();
            $this->assertTrue($zip->open($path));

            $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
            $zip->close();
        } finally {
            unlink($path);
        }

        $this->assertIsString($sheet);
        $this->assertStringNotContainsString('<f>', $sheet);
        $this->assertStringContainsString('<t>&apos;=formula</t>', $sheet);
        $this->assertStringContainsString('<t>&apos;=SUM(1+1)</t>', $sheet);
    }
}
