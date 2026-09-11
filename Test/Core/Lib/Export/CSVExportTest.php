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
use FacturaScripts\Core\Lib\Widget\ColumnItem;
use PHPUnit\Framework\TestCase;

final class CSVExportTest extends TestCase
{
    public function testAddMultipleBusinessDocsWritesHeaderOnlyOnce(): void
    {
        $firstLine = self::businessDocLine('Producto A', 2);
        $secondLine = self::businessDocLine('Producto B', 3);
        $firstInvoice = self::businessDoc('FAC001', 20.0, [$firstLine]);
        $secondInvoice = self::businessDoc('FAC002', 30.0, [$secondLine]);

        $export = new CSVExport();
        $export->newDoc('test', 0, 'es_ES');
        $export->addBusinessDocPage($firstInvoice);
        $export->addBusinessDocPage($secondInvoice);

        $expected = "\xEF\xBB\xBF" . implode(PHP_EOL, [
            '"descripcion";"cantidad";"codigo";"total"',
            '"Producto A";2;"FAC001";20',
            '"Producto B";3;"FAC002";30',
        ]);

        $this->assertSame($expected, $export->getDoc());
    }

    public function testAddBusinessDocWithoutLinesKeepsSameColumns(): void
    {
        $emptyInvoice = self::businessDoc('FAC001', 0.0, []);
        $line = self::businessDocLine('Producto B', 3);
        $secondInvoice = self::businessDoc('FAC002', 30.0, [$line]);

        $export = new CSVExport();
        $export->newDoc('test', 0, 'es_ES');
        $export->addBusinessDocPage($emptyInvoice);
        $export->addBusinessDocPage($secondInvoice);

        $expected = "\xEF\xBB\xBF" . implode(PHP_EOL, [
            '"descripcion";"cantidad";"codigo";"total"',
            '"";"";"FAC001";0',
            '"Producto B";3;"FAC002";30',
        ]);

        $this->assertSame($expected, $export->getDoc());
    }

    public function testAddListModelPageUsesViewColumns(): void
    {
        // modelo falso que devuelve dos registros en la primera página
        $model = new class {
            public function all($where, $order, $offset, $limit): array
            {
                return $offset > 0 ? [] : [
                    (object)['referencia' => 'A1', 'precio' => 10.5, 'stock' => 3],
                    (object)['referencia' => 'B2', 'precio' => null, 'stock' => 0],
                ];
            }
        };

        $columns = [
            self::column('referencia', 'text'),
            self::column('precio', 'money'),
            self::column('stock', 'number', ['decimal' => '0']),
        ];

        $export = new CSVExport();
        $export->newDoc('test', 0, 'es_ES');
        $export->addListModelPage($model, [], [], 0, $columns, 'test');

        $expected = "\xEF\xBB\xBF" . implode(PHP_EOL, [
            '"test-referencia";"test-precio";"test-stock"',
            '"A1";10.5;3',
            '"B2";;0',
        ]);

        $this->assertSame($expected, $export->getDoc());
    }

    public function testAddModelPageExportsProcessableValues(): void
    {
        $model = (object)[
            'total' => 1234.56,
            'cantidad' => 5,
            'activo' => true,
            'bloqueado' => false,
            'fecha' => '28-07-2026',
            'observaciones' => null,
            'descripcion' => '=SUM(A1)',
        ];

        $columns = [
            self::column('total', 'money'),
            self::column('cantidad', 'number', ['decimal' => '0']),
            self::column('activo', 'checkbox'),
            self::column('bloqueado', 'checkbox'),
            self::column('fecha', 'date'),
            self::column('observaciones', 'text'),
            self::column('descripcion', 'text'),
        ];

        $export = new CSVExport();
        $export->newDoc('test', 0, 'es_ES');
        $export->addModelPage($model, $columns, 'test');

        // números y moneda en crudo, checkbox como 1/0, fechas en ISO,
        // null como celda vacía y texto escapado contra fórmulas
        $expected = "\xEF\xBB\xBF" . implode(PHP_EOL, [
            '"test-total";"test-cantidad";"test-activo";"test-bloqueado";"test-fecha";"test-observaciones";"test-descripcion"',
            '1234.56;5;1;0;"2026-07-28";;"\'=SUM(A1)"',
        ]);

        $this->assertSame($expected, $export->getDoc());
    }

    public function testAddMultipleModelsWritesHeaderOnlyOnce(): void
    {
        $columns = [
            self::column('referencia', 'text'),
            self::column('precio', 'money'),
        ];

        $export = new CSVExport();
        $export->newDoc('test', 0, 'es_ES');
        $export->addModelPage((object)['referencia' => 'A1', 'precio' => 10.5], $columns, 'test');
        $export->addModelPage((object)['referencia' => 'B2', 'precio' => 20.0], $columns, 'test');

        $expected = "\xEF\xBB\xBF" . implode(PHP_EOL, [
            '"test-referencia";"test-precio"',
            '"A1";10.5',
            '"B2";20',
        ]);

        $this->assertSame($expected, $export->getDoc());
    }

    public function testWriteDataEscapesFormulasAndDelimiters(): void
    {
        $export = new CSVExport();
        $export->writeData([
            [
                '=SUM(1+1)',
                '+SUM(1+1)',
                '-SUM(1+1)',
                '@SUM(1+1)',
                "\tSUM(1+1)", // fixHtml() recorta los espacios en blanco
                "\rSUM(1+1)", // fixHtml() recorta los espacios en blanco
                'a"b',
                'a&quot;b', // se revierten las entidades html
                '-5', // los strings numéricos no se escapan
                'safe',
                123,
            ],
        ], ['=formula', 'safe']);

        // el documento empieza con el BOM UTF-8
        $expected = "\xEF\xBB\xBF" . implode(PHP_EOL, [
            '"\'=formula";"safe"',
            '"\'=SUM(1+1)";"\'+SUM(1+1)";"\'-SUM(1+1)";"\'@SUM(1+1)"'
            . ';"SUM(1+1)";"SUM(1+1)";"a""b";"a""b";"-5";"safe";123',
        ]);

        $this->assertSame($expected, $export->getDoc());
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

    private static function businessDoc(string $codigo, float $total, array $lines): object
    {
        return new class ($codigo, $total, $lines) {
            public $codigo;
            public $total;
            private $lines;

            public function __construct(string $codigo, float $total, array $lines)
            {
                $this->codigo = $codigo;
                $this->total = $total;
                $this->lines = $lines;
            }

            public function getLines(): array
            {
                return $this->lines;
            }

            public function getNewLine(): object
            {
                return CSVExportTest::businessDocLine('', 0);
            }

            public function getModelFields(): array
            {
                return ['codigo' => [], 'total' => []];
            }
        };
    }

    public static function businessDocLine(string $descripcion, float $cantidad): object
    {
        return new class ($descripcion, $cantidad) {
            public $cantidad;
            public $descripcion;

            public function __construct(string $descripcion, float $cantidad)
            {
                $this->descripcion = $descripcion;
                $this->cantidad = $cantidad;
            }

            public function getModelFields(): array
            {
                return ['descripcion' => [], 'cantidad' => []];
            }
        };
    }
}
