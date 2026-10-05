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
use FacturaScripts\Core\Lib\Widget\ColumnItem;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class XLSExportTest extends TestCase
{
    public function testAddModelPageWritesProcessableCells(): void
    {
        $model = (object)['total' => 1234.56, 'fecha' => '28-07-2026'];
        $columns = [
            self::column('total', 'money'),
            self::column('fecha', 'date'),
        ];

        $export = new XLSExport();
        $export->newDoc('test', 0, 'es_ES');
        $export->addModelPage($model, $columns, 'test');

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

        // el importe es una celda numérica, no texto
        $this->assertStringContainsString('<v>1234.56</v>', $sheet);
        $this->assertStringNotContainsString('<t>1234.56</t>', $sheet);

        // la fecha se convierte a fecha nativa de Excel (número de serie), no texto
        $this->assertStringNotContainsString('28-07-2026', $sheet);
        $this->assertStringNotContainsString('2026-07-28', $sheet);
    }

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

    public function testColumnHeadersUseWidgetTypes(): void
    {
        $columns = [
            self::column('total', 'money'),
            self::column('cantidad', 'number', ['decimal' => '0']),
            self::column('activo', 'checkbox'),
            self::column('fecha', 'date'),
            self::column('creacion', 'datetime'),
            self::column('hora', 'time'),
            self::column('nombre', 'text'),
        ];

        $expected = [
            'test-total' => 'price',
            'test-cantidad' => 'integer',
            'test-activo' => 'integer',
            'test-fecha' => 'date',
            'test-creacion' => 'datetime',
            'test-hora' => 'time',
            'test-nombre' => 'string',
        ];

        $this->assertSame($expected, self::exposedExport()->exposeColumnHeaders($columns));
    }

    public function testRowsExportProcessableValues(): void
    {
        $model = (object)[
            'total' => 1234.56,
            'cantidad' => 5,
            'activo' => true,
            'bloqueado' => false,
            'fecha' => '28-07-2026',
            'creacion' => '28-07-2026 12:30:45',
            'observaciones' => null,
            'descripcion' => '=SUM(A1)',
        ];

        $columns = [
            self::column('total', 'money'),
            self::column('cantidad', 'number', ['decimal' => '0']),
            self::column('activo', 'checkbox'),
            self::column('bloqueado', 'checkbox'),
            self::column('fecha', 'date'),
            self::column('creacion', 'datetime'),
            self::column('observaciones', 'text'),
            self::column('descripcion', 'text'),
        ];

        $rows = self::exposedExport()->exposeRows([$model], $columns);

        // números y moneda en crudo, checkbox como 1/0, fechas en ISO,
        // null como celda vacía y texto escapado contra fórmulas
        $expected = [
            'total' => 1234.56,
            'cantidad' => 5,
            'activo' => 1,
            'bloqueado' => 0,
            'fecha' => '2026-07-28',
            'creacion' => '2026-07-28 12:30:45',
            'observaciones' => null,
            'descripcion' => "'=SUM(A1)",
        ];

        $this->assertCount(1, $rows);
        $this->assertSame($expected, $rows[0]);
    }

    /**
     * Construye una columna de vista real con su widget, como las que
     * genera VisualItemLoadEngine a partir del XMLView.
     */
    private static function column(string $fieldname, string $type, array $extra = []): ColumnItem
    {
        $widget = array_merge([
            'tag' => 'widget',
            'type' => $type,
            'fieldname' => $fieldname,
            'children' => [],
        ], $extra);

        return new ColumnItem([
            'name' => $fieldname,
            'title' => 'test-' . $fieldname, // clave sin traducción, se devuelve tal cual
            'children' => [$widget],
        ]);
    }

    /**
     * Devuelve un XLSExport que expone los métodos protegidos a probar.
     */
    private static function exposedExport()
    {
        return new class extends XLSExport {
            public function exposeColumnHeaders(array $columns): array
            {
                return $this->getColumnHeaders($columns);
            }

            public function exposeRows(array $cursor, array $columns): array
            {
                return $this->getRows($cursor, $columns);
            }
        };
    }
}
