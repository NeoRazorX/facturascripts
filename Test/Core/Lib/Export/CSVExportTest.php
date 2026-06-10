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

use FacturaScripts\Core\Lib\Export\CSVExport;
use PHPUnit\Framework\TestCase;

final class CSVExportTest extends TestCase
{
    public function testWriteDataEscapesFormulasAndDelimiters(): void
    {
        $export = new CSVExport();
        $export->writeData([
            [
                '=SUM(1+1)',
                '+SUM(1+1)',
                '-SUM(1+1)',
                '@SUM(1+1)',
                "\tSUM(1+1)",
                "\rSUM(1+1)",
                'a"b',
                'safe',
                123,
            ],
        ], ['=formula', 'safe']);

        $expected = implode(PHP_EOL, [
            '"\'=formula";"safe"',
            '"\'=SUM(1+1)";"\'+SUM(1+1)";"\'-SUM(1+1)";"\'@SUM(1+1)";"\''
            . "\t" . 'SUM(1+1)";"\''
            . "\r" . 'SUM(1+1)";"a""b";"safe";123',
        ]);

        $this->assertSame($expected, $export->getDoc());
    }
}
